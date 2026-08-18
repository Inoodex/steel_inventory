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
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['vendor', 'lot', 'warehouse', 'coils']);

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('thickness', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })->orWhereHas('lot', function ($q) use ($search) {
                      $q->where('lot_number', 'like', "%{$search}%");
                  })->orWhereHas('coils', function ($q) use ($search) {
                      $q->where('coil_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by lot
        if ($request->filled('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by date range
        if ($request->filled('from') && $request->filled('to')) {
            $from = date('Y-m-d 00:00:00', strtotime($request->from));
            $to = date('Y-m-d 23:59:59', strtotime($request->to));
            $query->whereBetween('created_at', [$from, $to]);
        }

        $purchases  = $query->latest()->paginate(15)->withQueryString();
        $products   = collect();
        $vendors    = Vendor::latest()->get();
        $lots       = Lot::where('status', 'active')->latest()->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        
        return view('frontend.pages.purchase.index', compact('purchases', 'products', 'vendors', 'lots', 'warehouses', 'bankAccounts'));
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
        return redirect()->route('purchase.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        return redirect()->route('purchase.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'lot_id'       => 'nullable|exists:lots,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'thickness'    => 'nullable|string|max:100',
            'size'         => 'nullable|string|max:100',
            'size_type'    => 'nullable|string|max:50',
            'unit_weight'  => 'nullable|numeric|min:0',
            'total_weight' => 'nullable|numeric|min:0',
            'quantity'     => 'required|numeric|min:0.01',
            'unit_price'   => 'required|numeric|min:0',
            'sub_price'    => 'nullable|numeric',
            'total_price'  => 'required|numeric|min:0',
            'payment'      => 'nullable|numeric|min:0',
            'due'          => 'nullable|numeric|min:0',
            'vendor_id'    => 'required|exists:vendors,id',
        ]);

        $purchase = Purchase::findOrFail($purchase->id);
        $oldLotId = $purchase->lot_id;

        $purchase->lot_id       = $request->lot_id;
        $purchase->warehouse_id = $request->warehouse_id;
        $purchase->thickness    = $request->thickness;
        $purchase->size         = $request->size;
        $purchase->size_type    = $request->size_type;
        $purchase->unit_weight  = $request->unit_weight;
        $purchase->total_weight = $request->total_weight ?? ($request->unit_weight ? ((float)$request->unit_weight * (float)$request->quantity) : null);
        $purchase->quantity     = $request->quantity;
        $purchase->unit_price   = $request->unit_price;
        $purchase->sub_price    = $request->sub_price ?? ($request->quantity * $request->unit_price);
        $purchase->total_price  = $request->total_price;
        $purchase->payment      = $request->payment ?? 0;
        $purchase->due          = $request->due ?? max(0, $purchase->total_price - ($request->payment ?? 0));
        $purchase->vendor_id    = $request->vendor_id;    
        $purchase->updated_by   = Auth::id();

        $purchase->update();

        // Update affected lots totals
        foreach (array_filter([$oldLotId, $request->lot_id]) as $lId) {
            $lot = Lot::find($lId);
            if ($lot) {
                $lot->total_quantity = $lot->purchases()->sum('quantity');
                $lot->total_amount   = $lot->purchases()->sum('total_price');
                $lot->save();
            }
        }

        return redirect()->back()->with('success', 'Purchase updated successfully.');
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
        return response($mpdf->Output('purchase-report-' . now()->format('Y-m-d') . '.pdf', 'I'), 200, [
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
