<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lot;
use App\Models\Vendor;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

class LotController extends Controller
{
    /**
     * Display a listing of lots.
     */
    public function index(Request $request)
    {
        $query = Lot::with(['vendor', 'purchases']);

        // Search by Lot Number or Vendor Name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date Range
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('lot_date', [$request->from, $request->to]);
        }

        $lots = $query->latest()->paginate(15)->withQueryString();
        $vendors = Vendor::latest()->get();

        // Calculate summary metrics
        $totalLots = Lot::count();
        $activeLots = Lot::where('status', 'active')->count();
        $totalWeight = (float) \App\Models\Purchase::whereNotNull('lot_id')->sum('total_weight');
        $totalValuation = (float) \App\Models\Purchase::whereNotNull('lot_id')->sum('total_price');

        return view('frontend.pages.lots.index', compact('lots', 'vendors', 'totalLots', 'activeLots', 'totalWeight', 'totalValuation'));
    }

    /**
     * Store a newly created lot in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lot_number' => 'nullable|string|max:100|unique:lots,lot_number',
            'vendor_id'  => 'required|exists:vendors,id',
            'lot_date'   => 'required|date',
            'notes'      => 'nullable|string',
            'status'     => 'required|in:active,closed',
        ]);

        if (empty($validated['lot_number'])) {
            $validated['lot_number'] = Lot::generateLotNumber();
        }

        $validated['created_by'] = Auth::id();

        $lot = Lot::create($validated);

        return redirect()->back()->with('success', "Lot '{$lot->lot_number}' created successfully.");
    }

    /**
     * Quick create lot via AJAX (used inside Purchase form modal)
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'lot_number' => 'nullable|string|max:100|unique:lots,lot_number',
            'vendor_id'  => 'required|exists:vendors,id',
            'lot_date'   => 'required|date',
            'notes'      => 'nullable|string',
        ]);

        if (empty($validated['lot_number'])) {
            $validated['lot_number'] = Lot::generateLotNumber();
        }

        $validated['status'] = 'active';
        $validated['created_by'] = Auth::id();

        $lot = Lot::create($validated);
        $lot->load('vendor');

        return response()->json([
            'success' => true,
            'message' => "Lot '{$lot->lot_number}' created successfully.",
            'lot'     => [
                'id'         => $lot->id,
                'lot_number' => $lot->lot_number,
                'vendor_id'  => $lot->vendor_id,
                'vendor_name'=> $lot->vendor ? $lot->vendor->name : '',
            ],
        ]);
    }

    /**
     * Display detailed summary of a specific lot.
     */
    public function show(string $id)
    {
        $lot = Lot::with(['vendor', 'purchases.coils', 'purchases.warehouse', 'purchases.vendor', 'creator'])->findOrFail($id);

        $totalPurchases = $lot->purchases->count();
        $totalCoils     = (int) $lot->purchases->sum('quantity');
        $totalWeight    = (float) $lot->purchases->sum(fn($p) => (float)($p->total_weight ?: $p->quantity));
        $totalQuantity  = $totalWeight;
        $totalAmount    = (float) $lot->purchases->sum('total_price');
        $totalPaid      = (float) $lot->purchases->sum('payment');
        $totalDue       = max(0, round($totalAmount - $totalPaid, 2));

        return view('frontend.pages.lots.show', compact(
            'lot', 'totalPurchases', 'totalCoils', 'totalWeight', 'totalQuantity', 'totalAmount', 'totalPaid', 'totalDue'
        ));
    }

    /**
     * Update the specified lot in storage.
     */
    public function update(Request $request, string $id)
    {
        $lot = Lot::findOrFail($id);

        $validated = $request->validate([
            'lot_number' => 'required|string|max:100|unique:lots,lot_number,' . $lot->id,
            'vendor_id'  => 'required|exists:vendors,id',
            'lot_date'   => 'required|date',
            'notes'      => 'nullable|string',
            'status'     => 'required|in:active,closed',
        ]);

        $validated['updated_by'] = Auth::id();

        $lot->update($validated);

        return redirect()->back()->with('success', "Lot '{$lot->lot_number}' updated successfully.");
    }

    /**
     * Remove the specified lot from storage.
     */
    public function destroy(string $id)
    {
        $lot = Lot::findOrFail($id);
        
        if ($lot->purchases()->count() > 0) {
            return redirect()->back()->with('error', "Cannot delete Lot '{$lot->lot_number}' because it has linked purchases.");
        }

        $lot->delete();

        return redirect()->back()->with('success', "Lot deleted successfully.");
    }

    /**
     * Display the comprehensive Lot Report & Profitability Analytics Dashboard.
     */
    public function report(Request $request)
    {
        $data = $this->getLotReportData($request);

        return view('frontend.pages.report.lots.index', $data);
    }

    /**
     * Export Lot Report & Profitability Analytics as an mPDF Document.
     */
    public function reportPdf(Request $request)
    {
        $data = $this->getLotReportData($request);

        $html = view('frontend.pages.report.lots.pdf', $data)->render();

        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'default_font' => 'Helvetica',
        ]);

        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('lot-report-' . now()->format('Y-m-d') . '.pdf', 'S');

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lot-report-' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    /**
     * Helper to prepare analyzed Lot Report data & calculations.
     */
    private function getLotReportData(Request $request): array
    {
        $query = Lot::with([
            'vendor',
            'purchases.warehouse',
            'coils',
            'salesItems.sale.customer',
        ]);

        // 1. Date Range & Preset Filtering
        $datePreset = $request->input('date_preset');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($datePreset === 'today') {
            $query->whereDate('lot_date', Carbon::today());
        } elseif ($datePreset === 'this_week') {
            $query->whereBetween('lot_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($datePreset === 'this_month') {
            $query->whereBetween('lot_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        } elseif ($datePreset === 'last_month') {
            $query->whereBetween('lot_date', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]);
        } elseif ($datePreset === 'this_year') {
            $query->whereBetween('lot_date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()]);
        } elseif ($fromDate || $toDate) {
            if ($fromDate && $toDate) {
                $query->whereBetween('lot_date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $query->whereDate('lot_date', '>=', $fromDate);
            } elseif ($toDate) {
                $query->whereDate('lot_date', '<=', $toDate);
            }
        }

        // 2. Filter by Vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // 3. Filter by Customer (Lots that were sold to this customer)
        if ($request->filled('customer_id')) {
            $query->whereHas('salesItems.sale', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        // 4. Filter by Warehouse
        if ($request->filled('warehouse_id')) {
            $query->whereHas('purchases', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // 5. Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 6. Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('salesItems.sale.customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $allLots = $query->latest('lot_date')->get();

        // Perform in-depth per-lot financial calculations
        $analyzedLots = $allLots->map(function ($lot) {
            $intakeWeight = (float) $lot->purchases->sum(fn($p) => (float)($p->total_weight ?: $p->quantity));
            $purchaseCost = (float) $lot->purchases->sum('total_price');
            $vendorPaid   = (float) $lot->purchases->sum('payment');
            $vendorDue    = max(0, round($purchaseCost - $vendorPaid, 2));
            $avgPurchaseRate = $intakeWeight > 0 ? round($purchaseCost / $intakeWeight, 2) : 0.00;

            // Sales calculations
            $salesItems = $lot->salesItems;
            $soldWeight = (float) $salesItems->sum('qty');
            $salesRevenue = (float) $salesItems->sum('total_price');
            $avgSellRate = $soldWeight > 0 ? round($salesRevenue / $soldWeight, 2) : 0.00;

            // Cost of Goods Sold & Realized Profit
            $cogs = (float) $salesItems->sum(function ($item) use ($avgPurchaseRate) {
                $itemCostRate = (float)($item->purchase_price ?: $avgPurchaseRate);
                return $itemCostRate * (float)$item->qty;
            });
            if ($cogs == 0 && $soldWeight > 0) {
                $cogs = $soldWeight * $avgPurchaseRate;
            }

            $realizedProfit = (float) $salesItems->sum(function ($item) use ($avgPurchaseRate) {
                if ($item->profit !== null && (float)$item->profit != 0) {
                    return (float)$item->profit;
                }
                $itemCostRate = (float)($item->purchase_price ?: $avgPurchaseRate);
                return (float)$item->total_price - ($itemCostRate * (float)$item->qty);
            });
            if ($realizedProfit == 0 && $salesRevenue > 0) {
                $realizedProfit = $salesRevenue - $cogs;
            }

            $profitMargin = $salesRevenue > 0 ? round(($realizedProfit / $salesRevenue) * 100, 2) : 0.00;

            // Inventory Remaining
            $remainingWeight = max(0, round($intakeWeight - $soldWeight, 2));
            $remainingValuation = round($remainingWeight * $avgPurchaseRate, 2);

            // Customer Breakdown
            $customerMap = collect();
            foreach ($salesItems as $item) {
                $sale = $item->sale;
                if (!$sale || !$sale->customer) continue;
                $cId = $sale->customer_id;
                if (!$customerMap->has($cId)) {
                    $customerMap->put($cId, [
                        'id'           => $sale->customer->id,
                        'name'         => $sale->customer->name,
                        'phone'        => $sale->customer->phone,
                        'orders_count' => 0,
                        'total_qty'    => 0.00,
                        'total_amount' => 0.00,
                        'profit'       => 0.00,
                        'orders'       => [],
                    ]);
                }
                $cData = $customerMap->get($cId);
                $cData['orders_count'] += 1;
                $cData['total_qty'] += (float)$item->qty;
                $cData['total_amount'] += (float)$item->total_price;
                $itemProfit = (float)($item->profit ?: ((float)$item->total_price - ((float)($item->purchase_price ?: $avgPurchaseRate) * (float)$item->qty)));
                $cData['profit'] += $itemProfit;
                $cData['orders'][] = [
                    'sale_id'    => $sale->id,
                    'order_no'   => $sale->order_no,
                    'date'       => $sale->order_date ? $sale->order_date : $sale->created_at->format('Y-m-d'),
                    'qty'        => (float)$item->qty,
                    'unit_price' => (float)$item->unit_price,
                    'total'      => (float)$item->total_price,
                    'profit'     => $itemProfit,
                ];
                $customerMap->put($cId, $cData);
            }

            // Profitability Status
            if ($salesRevenue == 0) {
                $profitStatus = 'unsold'; // Still 100% in stock
            } elseif ($realizedProfit > 0) {
                $profitStatus = 'profitable';
            } elseif ($realizedProfit < 0) {
                $profitStatus = 'loss';
            } else {
                $profitStatus = 'breakeven';
            }

            return (object) [
                'lot'                => $lot,
                'id'                 => $lot->id,
                'lot_number'         => $lot->lot_number,
                'lot_date'           => $lot->lot_date,
                'status'             => $lot->status,
                'vendor_name'        => $lot->vendor?->name ?? 'N/A',
                'vendor_company'     => $lot->vendor?->company_name ?? '',
                'warehouse_name'     => $lot->warehouse?->name ?? 'Default Yard',
                'intake_weight'      => $intakeWeight,
                'purchase_cost'      => $purchaseCost,
                'vendor_paid'        => $vendorPaid,
                'vendor_due'         => $vendorDue,
                'avg_purchase_rate'  => $avgPurchaseRate,
                'sold_weight'        => $soldWeight,
                'sales_revenue'      => $salesRevenue,
                'avg_sell_rate'      => $avgSellRate,
                'cogs'               => $cogs,
                'realized_profit'    => $realizedProfit,
                'profit_margin'      => $profitMargin,
                'remaining_weight'   => $remainingWeight,
                'remaining_valuation'=> $remainingValuation,
                'customers'          => $customerMap->values(),
                'customers_count'    => $customerMap->count(),
                'profit_status'      => $profitStatus,
            ];
        });

        // 7. Profitability Filter (Applied on computed results)
        $profitabilityFilter = $request->input('profitability');
        if ($profitabilityFilter === 'profitable') {
            $analyzedLots = $analyzedLots->where('realized_profit', '>', 0)->values();
        } elseif ($profitabilityFilter === 'loss') {
            $analyzedLots = $analyzedLots->where('realized_profit', '<', 0)->values();
        } elseif ($profitabilityFilter === 'sold_out') {
            $analyzedLots = $analyzedLots->where('remaining_weight', '<=', 0)->where('intake_weight', '>', 0)->values();
        } elseif ($profitabilityFilter === 'in_stock') {
            $analyzedLots = $analyzedLots->where('remaining_weight', '>', 0)->values();
        }

        // Summary KPI Metrics
        $totalLotsCount      = $analyzedLots->count();
        $totalActiveLots     = $analyzedLots->where('status', 'active')->count();
        $totalClosedLots     = $analyzedLots->where('status', 'closed')->count();
        $totalIntakeWeight   = (float) $analyzedLots->sum('intake_weight');
        $totalSoldWeight     = (float) $analyzedLots->sum('sold_weight');
        $totalRemainingWeight= (float) $analyzedLots->sum('remaining_weight');
        $totalPurchaseValuation = (float) $analyzedLots->sum('purchase_cost');
        $totalSalesRevenue   = (float) $analyzedLots->sum('sales_revenue');
        $totalRealizedProfit = (float) $analyzedLots->sum('realized_profit');
        $overallProfitMargin = $totalSalesRevenue > 0 ? round(($totalRealizedProfit / $totalSalesRevenue) * 100, 2) : 0.00;
        $totalVendorDue      = (float) $analyzedLots->sum('vendor_due');

        // Dropdown entities for filters
        $vendors = Vendor::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();

        return compact(
            'analyzedLots',
            'totalLotsCount',
            'totalActiveLots',
            'totalClosedLots',
            'totalIntakeWeight',
            'totalSoldWeight',
            'totalRemainingWeight',
            'totalPurchaseValuation',
            'totalSalesRevenue',
            'totalRealizedProfit',
            'overallProfitMargin',
            'totalVendorDue',
            'vendors',
            'customers',
            'warehouses',
            'request'
        );
    }
}
