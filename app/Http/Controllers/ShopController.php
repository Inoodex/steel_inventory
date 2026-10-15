<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Coil;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\ProductReturn;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Lot;
use App\Models\BankDetail;
use App\Models\User;
use App\Models\ReturnItem;
use App\Models\SalesItem;
use App\Http\Requests\StoreReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\PdfService;

class ShopController extends Controller
{
    /**
     * Get or create the single primary retail shop.
     */
    public function getShop(): Warehouse
    {
        $shop = Warehouse::shops()->first();
        if (!$shop) {
            $shop = Warehouse::create([
                'name' => 'Main Retail Shop',
                'type' => 'shop',
                'code' => 'SH-01',
                'location' => 'Main Outlet',
                'status' => 'active',
                'notes' => 'Central Retail Outlet',
            ]);
        }
        return $shop;
    }

    /**
     * Display a listing of all shops/retail outlets.
     */
    public function index(Request $request)
    {
        $shop = $this->getShop();
        return redirect()->route('shops.sales.index');
    }

    /**
     * Show dedicated POS/Retail side-by-side Shop Sale creation page.
     */
    public function createSale(Request $request)
    {
        $shop = $request->filled('warehouse_id')
            ? (Warehouse::find($request->warehouse_id) ?? $this->getShop())
            : $this->getShop();

        $coils = Coil::where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->with(['lot.vendor', 'warehouse'])
            ->latest()
            ->get();

        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::with(['vendor'])->where('status', 'active')->orderBy('id', 'desc')->get();

        $existingClients = Customer::select('id', 'name', 'phone', 'address', 'opening_balance')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due_payment')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'payble')
            ->withSum('payments', 'amount')
            ->withSum(['returns' => function($q) {
                $q->where('status', '!=', 'rejected');
            }], 'total_refund_amount')
            ->orderBy('name')
            ->get()
            ->map(function ($c) {
                $opening = (float)($c->opening_balance ?? 0);
                $salesTotal = (float)($c->sales_sum_payble ?? 0);
                $paymentsTotal = (float)($c->payments_sum_amount ?? 0);
                $returnsTotal = (float)($c->returns_sum_total_refund_amount ?? 0);
                $net = $opening + $salesTotal - $paymentsTotal - $returnsTotal;
                $c->advance_credit = $net < 0 ? abs($net) : 0.00;
                $c->net_due = $net > 0 ? $net : 0.00;
                return $c;
            });

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        
        $todayCount = Sale::whereDate('created_at', Carbon::today())->count() + 1;
        $orderNo = 'ORD-' . date('Ymd') . '-' . str_pad($todayCount, 3, '0', STR_PAD_LEFT);

        return view('frontend.pages.shops.sales_create', compact(
            'shop',
            'coils',
            'existingClients',
            'warehouses',
            'lots',
            'bankAccounts',
            'orderNo'
        ));
    }

    /**
     * Show dedicated POS/Retail side-by-side Shop Purchase Intake creation page.
     */
    public function createPurchase(Request $request)
    {
        $shop = $this->getShop();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();
        // Only load lots associated with / created under this specific shop
        $lots = Lot::where('status', 'active')
            ->where(function ($q) use ($shop) {
                $q->whereHas('purchases', function ($pq) use ($shop) {
                    $pq->where('warehouse_id', $shop->id);
                })->orWhereHas('coils', function ($cq) use ($shop) {
                    $cq->where('warehouse_id', $shop->id);
                });
            })
            ->with(['vendor', 'purchases.vendor'])
            ->orderBy('id', 'desc')
            ->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        
        $todayCount = Purchase::whereDate('created_at', Carbon::today())->count() + 1;
        $suggestedLotNumber = 'LOT-' . date('Ymd') . '-' . str_pad($todayCount, 2, '0', STR_PAD_LEFT);

        return view('frontend.pages.shops.purchases_create', compact(
            'shop',
            'vendors',
            'lots',
            'bankAccounts',
            'suggestedLotNumber'
        ));
    }

    /**
     * Store a newly created shop.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50|unique:warehouses,code',
            'location'       => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'capacity_ton'   => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,inactive',
            'notes'          => 'nullable|string',
        ]);

        if (empty($validated['contact_phone']) && $request->filled('phone')) {
            $validated['contact_phone'] = $request->input('phone');
        }

        if (empty($validated['code'])) {
            $count = Warehouse::shops()->count() + 1;
            $validated['code'] = 'SH-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $validated['type'] = 'shop';

        Warehouse::create($validated);

        return redirect()->route('shops.index')->with('success', 'Shop / Retail Outlet created successfully.');
    }

    /**
     * Display the specified shop profile & central operations hub.
     */
    public function show(Request $request, $id)
    {
        $shop = Warehouse::shops()->withCount(['purchases', 'sales', 'coils'])->findOrFail($id);

        // Active Stock Coils in this shop
        $coilsQuery = $shop->coils()->with(['vendor', 'lot', 'purchase'])->latest();

        if ($request->filled('coil_status')) {
            $coilsQuery->where('status', $request->coil_status);
        }

        $coils = $coilsQuery->paginate(15, ['*'], 'coils_page')->withQueryString();

        // Shop stock analytics
        $inStockCoilsCount = $shop->coils()->where('status', 'in_stock')->count();
        $inProcessingCoilsCount = $shop->coils()->where('status', 'processing')->count();
        $totalStockWeightKg = (float) $shop->coils()->whereIn('status', ['in_stock', 'processing'])->sum('remaining_weight');
        $totalStockWeightTon = $totalStockWeightKg / 1000;
        
        $totalStockValuation = (float) $shop->coils()->whereIn('status', ['in_stock', 'processing'])->get()->sum(function($c) {
            return (float)$c->remaining_weight * (float)$c->rate_per_ton;
        });

        // Thickness-wise breakdown
        $activeCoils = $shop->coils()
            ->whereIn('status', ['in_stock', 'processing'])
            ->where('remaining_weight', '>', 0)
            ->get();

        $thicknessBreakdown = $activeCoils->groupBy(function ($coil) {
            return trim($coil->thickness) ?: 'Standard';
        })->map(function ($group, $thickness) {
            $totalCoils = $group->count();
            $totalPieces = $group->sum(fn($c) => (float)($c->piece_count ?: 1));
            $totalRemainingWeight = (float) $group->sum('remaining_weight');
            $totalValuation = (float) $group->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
            $avgPricePerKg = $totalRemainingWeight > 0 ? ($totalValuation / $totalRemainingWeight) : 0;
            $avgPricePerTon = $avgPricePerKg * 1000;

            return [
                'thickness' => $thickness,
                'coils_count' => $totalCoils,
                'pieces_count' => $totalPieces,
                'total_weight' => $totalRemainingWeight,
                'total_weight_mt' => $totalRemainingWeight / 1000,
                'total_valuation' => $totalValuation,
                'avg_price_per_kg' => $avgPricePerKg,
                'avg_price_per_ton' => $avgPricePerTon,
            ];
        })->sortByDesc('total_weight');

        // Recent Sales from this Shop
        $recentSales = $shop->sales()->with(['customer', 'salesBy'])->latest()->take(10)->get();
        $totalSalesAmount = (float) $shop->sales()->whereNull('deleted_at')->sum('payble');
        $totalSalesCount = $shop->sales()->whereNull('deleted_at')->count();

        // Recent Purchases into this Shop
        $recentPurchases = $shop->purchases()->with(['vendor', 'lot'])->latest()->take(10)->get();
        $totalPurchasesAmount = (float) $shop->purchases()->whereNull('deleted_at')->sum('total_price');
        $totalPurchasesCount = $shop->purchases()->whereNull('deleted_at')->count();

        // Recent Product Returns for this Shop's sales
        $shopSaleIds = $shop->sales()->pluck('id');
        $recentReturns = ProductReturn::whereIn('sale_id', $shopSaleIds)->with(['sale', 'customer'])->latest()->take(10)->get();
        $totalReturnsAmount = (float) ProductReturn::whereIn('sale_id', $shopSaleIds)->where('status', '!=', 'rejected')->sum('total_refund_amount');

        return view('frontend.pages.shops.show', compact(
            'shop',
            'coils',
            'inStockCoilsCount',
            'inProcessingCoilsCount',
            'totalStockWeightKg',
            'totalStockWeightTon',
            'totalStockValuation',
            'thicknessBreakdown',
            'recentSales',
            'totalSalesAmount',
            'totalSalesCount',
            'recentPurchases',
            'totalPurchasesAmount',
            'totalPurchasesCount',
            'recentReturns',
            'totalReturnsAmount'
        ));
    }

    /**
     * Show the dedicated Shop Profile & Settings page.
     */
    public function settings(Request $request)
    {
        $shop = $this->getShop();
        return view('frontend.pages.shops.edit', compact('shop'));
    }

    /**
     * Show the form for editing a shop.
     */
    public function edit($id)
    {
        $shop = Warehouse::shops()->withCount(['purchases', 'sales', 'coils'])->findOrFail($id);
        return view('frontend.pages.shops.edit', compact('shop'));
    }

    /**
     * Update the specified shop in storage.
     */
    public function update(Request $request, $id)
    {
        $shop = Warehouse::shops()->findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50|unique:warehouses,code,' . $shop->id,
            'location'       => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'capacity_ton'   => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,inactive',
            'notes'          => 'nullable|string',
        ]);

        if (empty($validated['contact_phone']) && $request->filled('phone')) {
            $validated['contact_phone'] = $request->input('phone');
        }

        $shop->update($validated);

        return redirect()->route('shops.settings')->with('success', 'Shop name, address, and contact details updated successfully.');
    }

    /**
     * Remove the specified shop from storage.
     */
    public function destroy($id)
    {
        $shop = Warehouse::shops()->findOrFail($id);

        if ($shop->coils()->where('status', 'in_stock')->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete shop with active stock in inventory.');
        }

        $shop->delete();

        return redirect()->route('shops.index')->with('success', 'Shop removed successfully.');
    }

    /**
     * Display Shop Sales list.
     */
    public function sales(Request $request)
    {
        $shop = $this->getShop();
        $query = Sale::where('warehouse_id', $shop->id)->with(['customer', 'warehouse', 'salesBy'])->whereNull('deleted_at');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('due_payment', '<=', 0);
            } elseif ($request->payment_status === 'due') {
                $query->where('due_payment', '>', 0);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->latest('order_date')->paginate(15)->withQueryString();
        $customers = Customer::orderBy('name')->get();

        $totalSalesCount = (clone $query)->count();
        $totalSalesAmount = (float) (clone $query)->sum('payble');
        $totalPaidAmount = (float) (clone $query)->sum('advanced_payment');
        $totalDueAmount = (float) (clone $query)->sum('due_payment');

        return view('frontend.pages.shops.sales', compact(
            'sales',
            'shop',
            'customers',
            'totalSalesCount',
            'totalSalesAmount',
            'totalPaidAmount',
            'totalDueAmount'
        ));
    }

    /**
     * Display Shop Purchases list.
     */
    public function purchases(Request $request)
    {
        $shop = $this->getShop();
        $query = Purchase::where('warehouse_id', $shop->id)->with(['vendor', 'warehouse', 'lot'])->whereNull('deleted_at');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('thickness', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($v) use ($search) {
                      $v->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->latest()->paginate(15)->withQueryString();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();

        $totalPurchasesCount = (clone $query)->count();
        $totalPurchasesAmount = (float) (clone $query)->sum('total_price');
        $totalPurchasesWeight = (float) (clone $query)->sum('total_weight');
        $totalPurchasesDue = (float) (clone $query)->sum('due');

        return view('frontend.pages.shops.purchases', compact(
            'purchases',
            'shop',
            'vendors',
            'totalPurchasesCount',
            'totalPurchasesAmount',
            'totalPurchasesWeight',
            'totalPurchasesDue'
        ));
    }

    /**
     * Display Shop Sales Returns list.
     */
    public function returns(Request $request)
    {
        $shop = $this->getShop();
        $query = ProductReturn::whereHas('sale', function($q) use ($shop) {
            $q->where('warehouse_id', $shop->id);
        })->with(['sale.warehouse', 'customer', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('return_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('return_date', '<=', $request->to_date);
        }

        $returns = $query->latest('return_date')->paginate(15)->withQueryString();
        $customers = Customer::orderBy('name')->get();

        $totalReturnsCount = (clone $query)->count();
        $totalRefundAmount = (float) (clone $query)->where('status', '!=', 'rejected')->sum('total_refund_amount');

        return view('frontend.pages.shops.returns', compact(
            'returns',
            'shop',
            'customers',
            'totalReturnsCount',
            'totalRefundAmount'
        ));
    }

    /**
     * Show dedicated form to create a new return for Shop Sales.
     */
    public function createReturn(Request $request)
    {
        $shop = $this->getShop();
        $sale = null;
        $saleItems = [];

        if ($request->filled('sale_id')) {
            $sale = Sale::where('warehouse_id', $shop->id)
                ->with(['customer', 'items.coil', 'items.product', 'items.lot'])
                ->where('id', $request->sale_id)
                ->first();
            if ($sale) {
                $saleItems = $sale->items;
            }
        }

        $sales = Sale::where('warehouse_id', $shop->id)
            ->where('status', '!=', 'cancelled')
            ->with(['customer', 'items.coil', 'items.lot'])
            ->latest('order_date')
            ->get();

        return view('frontend.pages.shops.returns_create', compact('shop', 'sale', 'saleItems', 'sales'));
    }

    /**
     * Fetch items for a shop sale order (AJAX endpoint).
     */
    public function getSaleItems($saleId)
    {
        $shop = $this->getShop();
        $sale = Sale::where('warehouse_id', $shop->id)
            ->with(['items.coil', 'items.product', 'items.lot', 'customer'])
            ->where('id', $saleId)
            ->first();

        if (!$sale) {
            return response()->json(['error' => 'Shop sale not found'], 404);
        }

        return response()->json([
            'sale' => $sale,
            'items' => $sale->items->map(function ($item) {
                $coilNo = $item->coil->coil_number ?? $item->product->coil_number ?? null;
                $name = $coilNo ? ('Coil #' . $coilNo) : ($item->lot ? ('Lot #' . $item->lot->lot_number) : ('Item #' . $item->id));
                if ($item->size) {
                    $name .= " ({$item->size}" . ($item->size_type ? " {$item->size_type}" : '') . ")";
                }
                $availQty = (float)($item->qty - ($item->returned_qty ?? 0));
                return [
                    'id'            => $item->id,
                    'sales_item_id' => $item->id,
                    'product_id'    => $item->coil_id ?? $item->product_id,
                    'product_name'  => $name,
                    'quantity'      => (float)$item->qty,
                    'returned_qty'  => (float)($item->returned_qty ?? 0),
                    'available_qty' => max(0, $availQty),
                    'unit_price'    => (float)$item->unit_price,
                    'total_price'   => (float)$item->total_price
                ];
            })
        ]);
    }

    /**
     * Store a newly created return for Shop Sales.
     */
    public function storeReturn(StoreReturnRequest $request)
    {
        $shop = $this->getShop();
        $validated = $request->validated();

        $sale = Sale::where('warehouse_id', $shop->id)->findOrFail($validated['sale_id']);

        DB::beginTransaction();
        try {
            $return = ProductReturn::create([
                'sale_id'             => $sale->id,
                'customer_id'         => $sale->customer_id,
                'return_date'         => $validated['return_date'],
                'status'              => 'pending',
                'reason'              => $validated['reason'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'total_refund_amount' => 0
            ]);

            $hasProductIdCol = \Illuminate\Support\Facades\Schema::hasColumn('return_items', 'product_id');
            $salesItemIds = collect($validated['items'])->pluck('sales_item_id')->filter()->unique();
            $loadedSalesItems = $salesItemIds->isNotEmpty() ? SalesItem::whereIn('id', $salesItemIds)->get()->keyBy('id') : collect();

            foreach ($validated['items'] as $item) {
                $salesItemId = $item['sales_item_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if (!$productId && $salesItemId) {
                    $sItem = $loadedSalesItems->get($salesItemId);
                    if ($sItem) {
                        $productId = $sItem->coil_id ?? $sItem->product_id;
                    }
                }

                $qty = (float)($item['quantity'] ?? 0);
                $unitPrice = (float)($item['unit_price'] ?? 0);

                $itemData = [
                    'return_id'     => $return->id,
                    'sales_item_id' => $salesItemId,
                    'quantity'      => $qty,
                    'unit_price'    => $unitPrice,
                    'total_price'   => $qty * $unitPrice,
                    'return_reason' => $item['return_reason'] ?? 'other',
                    'condition'     => $item['condition'] ?? 'good',
                    'notes'         => $item['notes'] ?? null,
                ];

                if ($hasProductIdCol) {
                    $itemData['product_id'] = $productId;
                }

                ReturnItem::create($itemData);
            }

            $return->total_refund_amount = $return->calculateTotalRefund();
            $return->save();

            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Shop Return Created!',
                'text'  => 'Shop return request created. Status: Pending Approval.',
            ]);

            return redirect()->route('shops.returns.show', $return->id)
                ->with('success', 'Shop return created successfully. Status: Pending Approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating shop return: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified Shop Sales Return.
     */
    public function showReturn($id)
    {
        $shop = $this->getShop();
        $return = ProductReturn::whereHas('sale', function($q) use ($shop) {
            $q->where('warehouse_id', $shop->id);
        })->with(['sale.customer', 'sale.items.coil', 'sale.items.lot', 'customer', 'items.salesItem.coil', 'items.salesItem.lot', 'items.product', 'processedBy'])->findOrFail($id);

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.shops.returns_show', compact('shop', 'return', 'bankAccounts'));
    }

    /**
     * Delete a shop return if pending or rejected.
     */
    public function destroyReturn($id)
    {
        $shop = $this->getShop();
        $return = ProductReturn::whereHas('sale', function($q) use ($shop) {
            $q->where('warehouse_id', $shop->id);
        })->findOrFail($id);

        if (!$return->isPending() && !$return->isRejected()) {
            return back()->with('error', 'Only pending or rejected returns can be deleted.');
        }

        DB::beginTransaction();
        try {
            $return->items()->delete();
            $return->delete();
            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Return Deleted',
                'text'  => 'Shop return record has been removed.',
            ]);

            return redirect()->route('shops.returns.index')->with('success', 'Shop return deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting shop return: ' . $e->getMessage());
        }
    }

    /**
     * Display Live Shop Stock & Inventory Report.
     */
    public function stockReport(Request $request)
    {
        $shop = $this->getShop();

        $query = Coil::where('warehouse_id', $shop->id)
            ->with(['lot.vendor', 'vendor', 'warehouse', 'purchase']);

        // Search filter (coil number, thickness, width, length, vendor name, lot number)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('coil_number', 'like', "%{$search}%")
                  ->orWhere('thickness', 'like', "%{$search}%")
                  ->orWhere('width', 'like', "%{$search}%")
                  ->orWhere('length', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('lot', function ($q) use ($search) {
                      $q->where('lot_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Lot
        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        // Status filter: default to 'in_stock' unless explicitly requested
        $status = $request->input('status', 'in_stock');
        if ($status === 'in_stock') {
            $query->where('status', 'in_stock')->where('remaining_weight', '>', 0);
        } elseif ($status === 'processing' || $status === 'in_processing') {
            $query->where('status', 'processing');
        } elseif ($status === 'reserved') {
            $query->where('status', 'reserved');
        } elseif ($status === 'exhausted') {
            $query->where(function ($q) {
                $q->where('status', 'exhausted')->orWhere('remaining_weight', '<=', 0);
            });
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $coils = $query->latest()->get();

        // Group coils by lot_id for Lot-Wise expandable view
        $lotGroups = $coils->groupBy(function ($c) {
            return $c->lot_id ? (string)$c->lot_id : 'direct_stock';
        })->map(function ($items, $key) {
            $first = $items->first();
            $lot = $key !== 'direct_stock' ? $first->lot : null;
            $vendor = $lot?->vendor ?? $first->vendor;
            $totalIntake = (float) $items->sum('net_weight');
            $totalRemaining = (float) $items->sum('remaining_weight');
            $pctRemaining = $totalIntake > 0 ? round(($totalRemaining / $totalIntake) * 100, 1) : ($totalRemaining > 0 ? 100 : 0);
            $totalValuation = (float) $items->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
            $totalPieces = (int) $items->sum(fn($c) => (float)($c->piece_count ?: 1));
            $inStockPieces = (int) $items->where('remaining_weight', '>', 0)->sum(fn($c) => (float)($c->piece_count ?: 1));
            $warehouses = $items->map(fn($c) => $c->warehouse?->name)->filter()->unique()->values();

            return [
                'lot_key'               => $key,
                'lot_id'                => $lot?->id,
                'lot'                   => $lot,
                'lot_number'            => $lot ? $lot->lot_number : 'Direct Stock / Opening Stock',
                'lot_date'              => $lot?->lot_date ? $lot->lot_date->format('d M Y') : ($first->created_at ? $first->created_at->format('d M Y') : '—'),
                'vendor'                => $vendor,
                'vendor_name'           => $vendor ? $vendor->name : ($lot ? 'Consignment' : 'Direct Stock'),
                'coils'                 => $items,
                'total_coils'           => $items->count(),
                'in_stock_coils'        => $items->where('remaining_weight', '>', 0)->count(),
                'total_pieces'          => $totalPieces,
                'in_stock_pieces'       => $inStockPieces,
                'total_intake_weight'   => $totalIntake,
                'total_remaining_weight'=> $totalRemaining,
                'pct_remaining'         => $pctRemaining,
                'total_valuation'       => $totalValuation,
                'warehouses'            => $warehouses,
            ];
        })->values();

        // Auxiliary data for filters
        $lots = Lot::where('status', 'active')
            ->whereHas('coils', fn($q) => $q->where('warehouse_id', $shop->id))
            ->latest()->get();
        $vendors = Vendor::where('status', '1')->orderBy('name')->get();

        // Top Summary KPI Metrics for this Shop
        $totalInStockWeight = (float) Coil::where('warehouse_id', $shop->id)->where('status', 'in_stock')->sum('remaining_weight');
        $totalIntakeWeight  = (float) Coil::where('warehouse_id', $shop->id)->where('status', 'in_stock')->sum('net_weight');
        $inStockCount       = Coil::where('warehouse_id', $shop->id)->where('status', 'in_stock')->where('remaining_weight', '>', 0)->count();
        $totalCoilsCount    = Coil::where('warehouse_id', $shop->id)->count();
        $totalValuation     = (float) Coil::where('warehouse_id', $shop->id)->where('status', 'in_stock')->where('remaining_weight', '>', 0)
            ->get()
            ->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);

        // Thickness breakdown
        $breakdownQuery = Coil::where('warehouse_id', $shop->id)
            ->whereIn('status', ['in_stock', 'processing'])
            ->where('remaining_weight', '>', 0);

        if ($request->filled('lot_id')) {
            $breakdownQuery->where('lot_id', $request->lot_id);
        }

        $thicknessBreakdown = $breakdownQuery->get()->groupBy(function ($coil) {
            return trim($coil->thickness) ?: 'Standard';
        })->map(function ($group, $thickness) {
            $totalCoils = $group->count();
            $totalPieces = $group->sum(fn($c) => (float)($c->piece_count ?: 1));
            $totalRemainingWeight = (float) $group->sum('remaining_weight');
            $totalValuation = (float) $group->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
            $avgPricePerKg = $totalRemainingWeight > 0 ? ($totalValuation / $totalRemainingWeight) : 0;
            $avgPricePerTon = $avgPricePerKg * 1000;
            $minRate = (float) $group->min('rate_per_ton');
            $maxRate = (float) $group->max('rate_per_ton');
            $sizes = $group->map(function($c) {
                $w = $c->width ?: '';
                $l = ($c->length && $c->length !== 'N/A') ? $c->length : '';
                return trim("{$w} {$l}");
            })->filter()->unique()->values()->all();

            return [
                'thickness' => $thickness,
                'coils_count' => $totalCoils,
                'pieces_count' => $totalPieces,
                'total_weight' => $totalRemainingWeight,
                'total_weight_mt' => $totalRemainingWeight / 1000,
                'total_valuation' => $totalValuation,
                'avg_price_per_kg' => $avgPricePerKg,
                'avg_price_per_ton' => $avgPricePerTon,
                'min_rate' => $minRate,
                'max_rate' => $maxRate,
                'sizes' => $sizes,
            ];
        })->sortByDesc('total_weight');

        return view('frontend.pages.shops.stock', compact(
            'shop',
            'coils',
            'lotGroups',
            'lots',
            'vendors',
            'totalInStockWeight',
            'totalIntakeWeight',
            'inStockCount',
            'totalCoilsCount',
            'totalValuation',
            'thicknessBreakdown'
        ));
    }

    /**
     * Download Shop Stock Report as mPDF matching normal stock inventory standard.
     */
    public function stockReportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');

        $shop = $this->getShop();
        $query = Coil::where('warehouse_id', $shop->id)
            ->where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->with(['lot.vendor', 'warehouse', 'purchase', 'vendor'])
            ->latest();

        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('coil_number', 'like', "%{$search}%")
                  ->orWhere('thickness', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('lot', function ($q) use ($search) {
                      $q->where('lot_number', 'like', "%{$search}%");
                  });
            });
        }

        $coils = $query->get();
        $filename = 'Shop_Stock_Report_' . now()->format('Y_m_d_His') . '.pdf';

        $headerLabel = 'SHOP INVENTORY';
        $reportTitle = 'SHOP STOCK INVENTORY REPORT';
        $scopeNote = $shop->name . ' • Retail Outlet Stock';

        return PdfService::viewToResponse('pdf.inventory', compact('coils', 'headerLabel', 'reportTitle', 'scopeNote'), $filename);
    }
}
