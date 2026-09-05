<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Models\Coil;
use App\Models\BankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePurchaseRequest;
use App\Services\PurchaseService;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService) {}

    /**
     * Display a listing of the resource.
     * Consolidates purchases by Lot into 1 row with expandable item details.
     */
    public function index(Request $request)
    {
        $query = Lot::with(['vendor', 'purchases' => function ($q) {
            $q->with(['warehouse', 'coils'])->latest();
        }])->whereHas('purchases');

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })->orWhereHas('purchases', function ($q) use ($search) {
                      $q->where('thickness', 'like', "%{$search}%")
                        ->orWhere('size', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('coils', function ($q) use ($search) {
                            $q->where('coil_number', 'like', "%{$search}%");
                        });
                  });
            });
        }

        // Filter by lot
        if ($request->filled('lot_id')) {
            $query->where('id', $request->lot_id);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->whereHas('purchases', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Filter by date range
        if ($request->filled('from') && $request->filled('to')) {
            $from = date('Y-m-d 00:00:00', strtotime($request->from));
            $to = date('Y-m-d 23:59:59', strtotime($request->to));
            $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('lot_date', [$from, $to])
                  ->orWhereBetween('created_at', [$from, $to]);
            });
        }

        $lots = $query->latest()->paginate(15)->withQueryString();
        $purchases = $lots; // Alias for backward compatibility

        // Overall summary statistics
        $totalLotsCount  = Lot::whereHas('purchases')->count();
        $totalOrderValue = (float) Purchase::sum('total_price');
        $totalPaid       = (float) Purchase::sum('payment');
        $totalDue        = (float) Purchase::sum('due');
        $totalWeight     = (float) Purchase::sum('total_weight');

        $vendors      = Vendor::where('status', '1')->latest()->get();
        $allLots      = Lot::where('status', 'active')->latest()->get();
        $warehouses   = Warehouse::where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.index', compact(
            'lots',
            'purchases',
            'vendors',
            'allLots',
            'warehouses',
            'bankAccounts',
            'totalLotsCount',
            'totalOrderValue',
            'totalPaid',
            'totalDue',
            'totalWeight'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products   = collect();
        $vendors    = Vendor::where('status', '1')->latest()->get();
        $lots       = Lot::where('status', 'active')->latest()->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        $suggestedLotNumber = Lot::generateLotNumber();

        return view('frontend.pages.purchase.create', compact('products', 'vendors', 'lots', 'warehouses', 'bankAccounts', 'suggestedLotNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        try {
            $data = $request->validated();

            if (!empty($data['items']) && is_array($data['items'])) {
                $purchases = $this->purchaseService->createPurchasesBatch($data);
                $count = count($purchases);
                return redirect()->route('purchase.index')
                    ->with('success', "{$count} purchase items recorded and stock updated successfully.");
            } else {
                $this->purchaseService->createPurchase($data);
                return redirect()->route('purchase.index')
                    ->with('success', 'Purchase created and stock updated successfully.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to save purchase: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'lot.purchases.coils', 'lot.purchases.warehouse', 'warehouse', 'bankDetail', 'coils', 'payments', 'creator', 'updater']);

        // Consignment purchases: all purchases under the same Lot, or just this purchase if standalone
        if ($purchase->lot && $purchase->lot->purchases->isNotEmpty()) {
            $consignmentPurchases = $purchase->lot->purchases;
        } else {
            $consignmentPurchases = collect([$purchase]);
        }

        // Flatten all coils in this consignment
        $allCoils = $consignmentPurchases->flatMap->coils;

        // Consignment aggregate calculations
        $consignmentSubTotal    = (float) $consignmentPurchases->sum(fn($p) => (float)($p->sub_price ?: $p->total_price));
        $consignmentDelivery    = (float) $consignmentPurchases->sum('delivery_charge');
        $consignmentLabour      = (float) $consignmentPurchases->sum('labour_cost');
        $consignmentScale       = (float) $consignmentPurchases->sum('weight_scale_cost');
        $consignmentOther       = (float) $consignmentPurchases->sum('other_charges');
        $consignmentDiscount    = (float) $consignmentPurchases->sum('discount');
        $consignmentExtra       = $consignmentDelivery + $consignmentLabour + $consignmentScale + $consignmentOther;
        $consignmentGrandTotal  = max(0, round($consignmentSubTotal + $consignmentExtra - $consignmentDiscount, 2));
        $consignmentPayment     = (float) $consignmentPurchases->sum('payment');
        $consignmentDue         = max(0, round($consignmentGrandTotal - $consignmentPayment, 2));
        $consignmentTotalWeight = (float) $consignmentPurchases->sum('total_weight');
        $consignmentTotalQty    = (int) $consignmentPurchases->sum('quantity');

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.show', compact(
            'purchase',
            'consignmentPurchases',
            'allCoils',
            'consignmentSubTotal',
            'consignmentDelivery',
            'consignmentLabour',
            'consignmentScale',
            'consignmentOther',
            'consignmentDiscount',
            'consignmentExtra',
            'consignmentGrandTotal',
            'consignmentPayment',
            'consignmentDue',
            'consignmentTotalWeight',
            'consignmentTotalQty',
            'bankAccounts'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load(['vendor', 'lot', 'warehouse', 'coils', 'bankDetail', 'creator']);
        $vendors = Vendor::where('status', '1')->latest()->get();
        $lots = Lot::where('status', 'active')->latest()->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.edit', compact('purchase', 'vendors', 'lots', 'warehouses', 'bankAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'lot_id'          => 'required|exists:lots,id',
            'warehouse_id'    => 'required|exists:warehouses,id',
            'vendor_id'       => 'required|exists:vendors,id',
            'thickness'       => 'nullable|string|max:100',
            'size'            => 'nullable|string|max:100',
            'size_type'       => 'nullable|string|max:50',
            'unit_weight'     => 'required|numeric|min:0.001',
            'total_weight'    => 'nullable|numeric|min:0',
            'quantity'        => 'required|numeric|min:1',
            'unit_price'        => 'required|numeric|min:0',
            'sub_price'         => 'nullable|numeric',
            'delivery_charge'   => 'nullable|numeric|min:0',
            'labour_cost'       => 'nullable|numeric|min:0',
            'weight_scale_cost' => 'nullable|numeric|min:0',
            'other_charges'     => 'nullable|numeric|min:0',
            'discount'          => 'nullable|numeric|min:0',
            'total_price'       => 'nullable|numeric|min:0',
            'payment'           => 'required|numeric|min:0',
            'due'               => 'nullable|numeric|min:0',
            'payment_method'    => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id'    => 'nullable|exists:bank_details,id',
            'transaction_ref'   => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:500',
            'coil_notes'        => 'nullable|string|max:500',
        ]);

        $purchase = Purchase::with('coils')->findOrFail($purchase->id);
        $oldLotId = $purchase->lot_id;
        $coil = $purchase->coils->first();

        $coilQty = max(1, (int) $request->quantity);
        $perCoilWeight = (float) $request->unit_weight;
        $calculatedTotalWeight = $coilQty * $perCoilWeight;
        $totalWeight = (float) ($request->total_weight ?: $calculatedTotalWeight);
        if ($totalWeight <= 0 && $calculatedTotalWeight > 0) {
            $totalWeight = $calculatedTotalWeight;
        }

        // Sold weight protection check
        if ($coil) {
            $soldWeight = max(0, (float)$coil->net_weight - (float)$coil->remaining_weight);
            if ($soldWeight > 0 && $totalWeight < $soldWeight) {
                return redirect()->back()->withInput()->with('error', "Cannot reduce total intake weight below " . number_format($soldWeight, 2) . " kg because this amount has already been sold and dispatched.");
            }
        }

        $rate = (float) $request->unit_price;
        $subPrice = (float) ($request->sub_price ?: ($totalWeight * $rate));

        $deliveryCharge  = (float) ($request->delivery_charge ?? 0);
        $labourCost      = (float) ($request->labour_cost ?? 0);
        $weightScaleCost = (float) ($request->weight_scale_cost ?? 0);
        $otherCharges    = (float) ($request->other_charges ?? 0);
        $discount        = (float) ($request->discount ?? 0);
        $netExtraCharges = ($deliveryCharge + $labourCost + $weightScaleCost + $otherCharges) - $discount;

        $totalPrice = max(0, round($subPrice + $netExtraCharges, 2));
        $payment = (float) $request->payment;
        $due = max(0, round($totalPrice - $payment, 2));

        $purchase->lot_id            = $request->lot_id;
        $purchase->warehouse_id      = $request->warehouse_id;
        $purchase->vendor_id         = $request->vendor_id;
        $purchase->thickness         = $request->thickness;
        $purchase->size              = $request->size;
        $purchase->size_type         = $request->size_type ?: 'ft';
        $purchase->unit_weight       = $perCoilWeight;
        $purchase->total_weight      = $totalWeight;
        $purchase->quantity          = $coilQty;
        $purchase->unit_price        = $rate;
        $purchase->sub_price         = $subPrice;
        $purchase->delivery_charge   = $deliveryCharge;
        $purchase->labour_cost       = $labourCost;
        $purchase->weight_scale_cost = $weightScaleCost;
        $purchase->other_charges     = $otherCharges;
        $purchase->discount          = $discount;
        $purchase->total_price       = $totalPrice;
        $purchase->payment           = $payment;
        $purchase->due               = $due;
        $purchase->payment_method    = $request->payment_method ?? 'cash';
        $purchase->bank_detail_id    = ($request->payment_method === 'bank') ? $request->bank_detail_id : null;
        $purchase->transaction_ref   = ($request->payment_method === 'bank') ? $request->transaction_ref : null;
        $purchase->notes             = $request->notes;
        $purchase->updated_by        = Auth::id();
        $purchase->save();

        // Synchronize linked Coil record in yard stock
        if ($coil) {
            $prevNetWeight = (float) $coil->net_weight;
            $soldWeight = max(0, $prevNetWeight - (float) $coil->remaining_weight);
            $newRemainingWeight = max(0, $totalWeight - $soldWeight);

            $coil->update([
                'lot_id'           => $request->lot_id,
                'vendor_id'        => $request->vendor_id,
                'warehouse_id'     => $request->warehouse_id,
                'thickness'        => $request->thickness,
                'width'            => $request->size,
                'length'           => $request->size_type ?: 'ft',
                'piece_count'      => $coilQty,
                'gross_weight'     => $totalWeight,
                'net_weight'       => $totalWeight,
                'remaining_weight' => $newRemainingWeight,
                'rate_per_ton'     => $rate,
                'total_price'      => $totalPrice,
                'status'           => ($newRemainingWeight <= 0) ? 'exhausted' : 'in_stock',
                'notes'            => $request->coil_notes ?? $coil->notes,
                'updated_by'       => Auth::id(),
            ]);
        }

        // Update affected lots totals
        foreach (array_unique(array_filter([$oldLotId, $request->lot_id])) as $lId) {
            $lot = Lot::find($lId);
            if ($lot) {
                $lot->total_quantity = $lot->purchases()->sum('total_weight');
                $lot->total_amount   = $lot->purchases()->sum('total_price');
                $lot->save();
            }
        }

        return redirect()->route('purchase.show', $purchase->id)
            ->with('success', 'Purchase order #PO-' . $purchase->id . ' and physical coil specifications updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // Safety check: Prevent deletion if any coil under this purchase has sales records or sold weight
        $hasSalesItems = \App\Models\SalesItem::whereHas('coil', function ($q) use ($purchase) {
            $q->where('purchase_id', $purchase->id);
        })->exists();

        $hasSoldWeight = \App\Models\Coil::where('purchase_id', $purchase->id)
            ->whereColumn('remaining_weight', '<', 'net_weight')
            ->exists();

        if ($hasSalesItems || $hasSoldWeight) {
            return redirect()->back()->with('error', 'Cannot delete purchase: Steel coils from this procurement intake have already been sold.');
        }

        $purchase->delete();
        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }

    public function getLatestPrice($id)
    {
        return response()->json(['price' => 0]);
    }

    public function reportIndex(?Request $request = null)
    {
        $request = $request ?? request();
        $query = Purchase::with(['vendor', 'lot', 'warehouse']);
        $hasFilters = $request->filled('vendor_id') || $request->filled('lot_id') || $request->filled('from') || $request->filled('to');

        if (!$hasFilters) {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]);
        } else {
            $query = $this->applyPurchaseReportFilters($query, $request);
        }

        $purchases = $query->latest()->get();
        $vendors = Vendor::latest()->get();
        $lots = Lot::latest()->get();

        return view('frontend.pages.report.purchase.index', compact('purchases', 'vendors', 'lots', 'request'));
    }

    public function report(Request $request)
    {
        $query = Purchase::with(['vendor', 'lot', 'warehouse']);
        $query = $this->applyPurchaseReportFilters($query, $request);

        $purchases = $query->latest()->get();
        $products = collect();
        $vendors = Vendor::latest()->get();
        $lots = Lot::latest()->get();

        return view('frontend.pages.report.purchase.index', compact('purchases', 'products', 'vendors', 'lots', 'request'));
    }

    public function reportPdf(Request $request)
    {
        $query = Purchase::with(['vendor', 'lot', 'warehouse']);
        $query = $this->applyPurchaseReportFilters($query, $request);

        $purchases = $query->latest()->get();
        $vendors = Vendor::latest()->get();

        $filters = [
            'from' => $request->filled('from') ? $request->from : Carbon::now()->startOfMonth()->format('Y-m-d'),
            'to' => $request->filled('to') ? $request->to : Carbon::now()->endOfMonth()->format('Y-m-d'),
            'vendor' => $request->filled('vendor_id') ? Vendor::find($request->vendor_id)?->name : 'All Vendors',
            'lot' => $request->filled('lot_id') ? Lot::find($request->lot_id)?->lot_number : 'All Lots',
        ];

        $html = view('frontend.pages.report.purchase.pdf', compact('purchases', 'filters'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('purchase-report-' . now()->format('Y-m-d') . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function applyPurchaseReportFilters($query, Request $request)
    {
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return $query;
    }

    /**
     * Display listing of unpaid purchase orders with vendor dues
     */
    public function duePayments()
    {
        $purchases = Purchase::with(['vendor', 'lot', 'warehouse'])
            ->where('due', '>', 0)
            ->latest()
            ->get();

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.due-payments', compact('purchases', 'bankAccounts'));
    }

    /**
     * Export Vendor Due Payments Report as PDF
     */
    public function duePaymentsPdf()
    {
        $purchases = Purchase::with(['vendor', 'lot', 'warehouse'])
            ->where('due', '>', 0)
            ->latest()
            ->get();

        $html = view('pdf.vendor_due_payments', compact('purchases'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        $filename = 'Vendor_Due_Payments_Report_' . now()->format('Y_m_d_His') . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
