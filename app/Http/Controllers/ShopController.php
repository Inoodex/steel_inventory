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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $shop = $this->getShop();
        $coils = Coil::where('warehouse_id', $shop->id)
            ->where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->with(['lot', 'warehouse'])
            ->latest()
            ->get();

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
        $lots = Lot::with('vendor')->where('status', 'active')->orderBy('id', 'desc')->get();
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
     * Display Live Shop Stock & Inventory Report.
     */
    public function stockReport(Request $request)
    {
        $shop = $this->getShop();
        $query = Coil::where('warehouse_id', $shop->id)->with(['warehouse', 'vendor', 'lot']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['in_stock', 'processing']);
        }

        if ($request->filled('thickness')) {
            $query->where('thickness', $request->thickness);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('coil_no', 'like', "%{$search}%")
                  ->orWhere('thickness', 'like', "%{$search}%")
                  ->orWhere('width', 'like', "%{$search}%")
                  ->orWhere('length', 'like', "%{$search}%");
            });
        }

        $coils = $query->latest()->paginate(20)->withQueryString();

        $allShopCoils = (clone $query)->get();
        $totalItems = $allShopCoils->count();
        $totalWeightKg = (float) $allShopCoils->sum('remaining_weight');
        $totalWeightTon = $totalWeightKg / 1000;
        $totalValuation = (float) $allShopCoils->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
        $totalPieces = (float) $allShopCoils->sum(fn($c) => (float)($c->piece_count ?: 1));

        $thicknessBreakdown = $allShopCoils->groupBy(function ($coil) {
            return trim($coil->thickness) ?: 'Standard';
        })->map(function ($group, $thickness) {
            $count = $group->count();
            $pieces = $group->sum(fn($c) => (float)($c->piece_count ?: 1));
            $weight = (float) $group->sum('remaining_weight');
            $valuation = (float) $group->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
            return [
                'thickness' => $thickness,
                'count' => $count,
                'pieces' => $pieces,
                'weight' => $weight,
                'weight_ton' => $weight / 1000,
                'valuation' => $valuation,
                'avg_rate' => $weight > 0 ? ($valuation / $weight) * 1000 : 0
            ];
        })->sortByDesc('weight');

        $availableThicknesses = Coil::where('warehouse_id', $shop->id)->select('thickness')->distinct()->pluck('thickness')->filter();

        return view('frontend.pages.shops.stock', compact(
            'coils',
            'shop',
            'availableThicknesses',
            'totalItems',
            'totalWeightKg',
            'totalWeightTon',
            'totalValuation',
            'totalPieces',
            'thicknessBreakdown'
        ));
    }

    /**
     * Download Shop Stock Report as mPDF.
     */
    public function stockReportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');

        $shop = $this->getShop();
        $query = Coil::where('warehouse_id', $shop->id)->with(['warehouse', 'vendor', 'lot']);
        $selectedShopName = $shop->name . ' (' . ($shop->code ?? 'SHOP') . ')';

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['in_stock', 'processing']);
        }

        if ($request->filled('thickness')) {
            $query->where('thickness', $request->thickness);
        }

        $coils = $query->latest()->get();

        $totalItems = $coils->count();
        $totalWeightKg = (float) $coils->sum('remaining_weight');
        $totalWeightTon = $totalWeightKg / 1000;
        $totalValuation = (float) $coils->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
        $totalPieces = (float) $coils->sum(fn($c) => (float)($c->piece_count ?: 1));

        $thicknessBreakdown = $coils->groupBy(function ($coil) {
            return trim($coil->thickness) ?: 'Standard';
        })->map(function ($group, $thickness) {
            $weight = (float) $group->sum('remaining_weight');
            $valuation = (float) $group->sum(fn($c) => (float)$c->remaining_weight * (float)$c->rate_per_ton);
            return [
                'thickness' => $thickness,
                'count' => $group->count(),
                'pieces' => $group->sum(fn($c) => (float)($c->piece_count ?: 1)),
                'weight' => $weight,
                'weight_ton' => $weight / 1000,
                'valuation' => $valuation,
            ];
        })->sortByDesc('weight');

        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');

        $html = view('pdf.shop_stock', compact(
            'coils',
            'selectedShopName',
            'totalItems',
            'totalWeightKg',
            'totalWeightTon',
            'totalValuation',
            'totalPieces',
            'thicknessBreakdown',
            'padBase64'
        ))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'Helvetica',
            'margin_top' => 42,
            'margin_bottom' => 20,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('Shop_Stock_Report_' . now()->format('Y_m_d_His') . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
