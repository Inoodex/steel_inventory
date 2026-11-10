<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Coil;
use App\Models\BankDetail;
use App\Models\Payment;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['vendor', 'purchase.lot', 'warehouse', 'coil'])
            ->latest('return_date');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('refund_type')) {
            $query->where('refund_type', $request->refund_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('return_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('return_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('purchase', function ($pq) use ($search) {
                      $pq->where('id', $search);
                  });
            });
        }

        $totalReturnsCount = (clone $query)->count();
        $totalReturnWeight = (float) (clone $query)->sum('return_weight');
        $totalRefundValue = (float) (clone $query)->sum('total_refund_amount');

        $returns = $query->paginate(15)->withQueryString();

        $vendors = Vendor::active()->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();

        return view('frontend.pages.purchase_returns.index', compact(
            'returns',
            'vendors',
            'warehouses',
            'totalReturnsCount',
            'totalReturnWeight',
            'totalRefundValue'
        ));
    }

    /**
     * Show the form for creating a new purchase return.
     */
    public function create(Request $request)
    {
        $vendors = Vendor::active()->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        $returnNo = PurchaseReturn::generateReturnNo();

        $selectedPurchase = null;
        if ($request->filled('purchase_id')) {
            $selectedPurchase = Purchase::with(['vendor', 'lot', 'warehouse', 'coils'])->find($request->purchase_id);
        }

        $selectedVendorId = $request->vendor_id ?? ($selectedPurchase?->vendor_id ?? null);

        return view('frontend.pages.purchase_returns.create', compact(
            'vendors',
            'bankAccounts',
            'returnNo',
            'selectedPurchase',
            'selectedVendorId'
        ));
    }

    /**
     * AJAX: Get eligible purchases for vendor that have remaining stock to return.
     */
    public function getVendorPurchases($vendorId)
    {
        $purchases = Purchase::with(['lot', 'warehouse', 'coils'])
            ->where('vendor_id', $vendorId)
            ->latest('id')
            ->get()
            ->map(function ($p) {
                $coil = $p->coils->first();
                $remWeight = $coil ? (float)$coil->remaining_weight : (float)$p->total_weight;
                $lotNumber = $p->lot?->lot_number ?? 'N/A';
                $productName = $p->product_name ?? 'Steel Consignment';
                $spec = trim("{$p->thickness} {$p->size}");

                return [
                    'id'               => $p->id,
                    'lot_id'           => $p->lot_id,
                    'lot_number'       => $lotNumber,
                    'warehouse_id'     => $p->warehouse_id,
                    'warehouse_name'   => $p->warehouse?->name ?? 'Main Yard',
                    'coil_id'          => $coil?->id,
                    'coil_number'      => $coil?->coil_number ?? '',
                    'product_name'     => $productName,
                    'specs'            => $spec,
                    'unit_price'       => (float) $p->unit_price,
                    'total_weight'     => (float) $p->total_weight,
                    'remaining_weight' => max(0, $remWeight),
                    'total_price'      => (float) $p->total_price,
                    'due'              => (float) $p->due,
                ];
            })
            ->filter(function ($item) {
                return $item['remaining_weight'] > 0;
            })
            ->values();

        return response()->json($purchases);
    }

    /**
     * Store a newly created purchase return in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'return_date'         => 'required|date',
            'vendor_id'           => 'required|exists:vendors,id',
            'purchase_id'         => 'required|exists:purchases,id',
            'return_weight'       => 'required|numeric|min:0.01',
            'unit_price'          => 'required|numeric|min:0',
            'refund_type'         => 'required|in:reduce_due,advance_credit,cash_refund,bank_refund',
            'payment_method'      => 'nullable|string|in:cash,bank,mobile_banking',
            'bank_detail_id'      => 'nullable|exists:bank_details,id',
            'transaction_ref'     => 'nullable|string|max:255',
            'reason'              => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::with(['coils', 'warehouse', 'vendor'])->findOrFail($validated['purchase_id']);
            $coil = $purchase->coils->first();

            $returnWeight = (float) $validated['return_weight'];
            $unitPrice = (float) $validated['unit_price'];

            // Protection: verify available in-stock weight
            $availableStock = $coil ? (float)$coil->remaining_weight : (float)$purchase->total_weight;
            if ($returnWeight > ($availableStock + 0.0001)) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', "Cannot return {$returnWeight} kg. Available in-stock weight for this purchase is only " . number_format($availableStock, 2) . " kg (the rest has already been sold/dispatched).");
            }

            // Deduct stock from coil
            if ($coil) {
                $newRemaining = max(0, (float)$coil->remaining_weight - $returnWeight);
                $coil->remaining_weight = $newRemaining;
                if ($newRemaining <= 0) {
                    $coil->status = 'exhausted';
                }
                $coil->save();
            }

            $totalRefundAmount = round($returnWeight * $unitPrice, 2);
            $returnNo = PurchaseReturn::generateReturnNo();

            $pReturn = PurchaseReturn::create([
                'return_no'           => $returnNo,
                'return_date'         => $validated['return_date'],
                'vendor_id'           => $purchase->vendor_id,
                'purchase_id'         => $purchase->id,
                'warehouse_id'        => $purchase->warehouse_id,
                'coil_id'             => $coil?->id,
                'lot_id'              => $purchase->lot_id,
                'return_weight'       => $returnWeight,
                'return_qty'          => 1,
                'unit_price'          => $unitPrice,
                'total_refund_amount' => $totalRefundAmount,
                'refund_type'         => $validated['refund_type'],
                'payment_method'      => $validated['payment_method'] ?? ($validated['refund_type'] === 'cash_refund' ? 'cash' : null),
                'bank_detail_id'      => $validated['bank_detail_id'] ?? null,
                'transaction_ref'     => $validated['transaction_ref'] ?? null,
                'reason'              => $validated['reason'] ?? 'defective',
                'notes'               => $validated['notes'] ?? null,
                'status'              => 'completed',
                'created_by'          => Auth::id(),
            ]);

            // Settlement logic
            if ($validated['refund_type'] === 'reduce_due') {
                $currentDue = (float) $purchase->due;
                if ($currentDue > 0) {
                    $reduceAmount = min($currentDue, $totalRefundAmount);
                    $purchase->due = max(0, $currentDue - $reduceAmount);
                    $purchase->save();
                }
            } elseif (in_array($validated['refund_type'], ['cash_refund', 'bank_refund'])) {
                if (!empty($validated['bank_detail_id'])) {
                    $bank = BankDetail::find($validated['bank_detail_id']);
                    if ($bank) {
                        $bank->syncCurrentBalance();
                    }
                }
            }

            DB::commit();

            return redirect()->route('purchase-returns.show', $pReturn->id)
                ->with('success', "Purchase Return #{$returnNo} processed successfully! {$returnWeight} kg returned to {$purchase->vendor?->name}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error creating purchase return: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase return voucher.
     */
    public function show($id)
    {
        $return = PurchaseReturn::with([
            'vendor',
            'purchase.lot',
            'warehouse',
            'coil',
            'bankDetail',
            'creator'
        ])->findOrFail($id);

        return view('frontend.pages.purchase_returns.show', compact('return'));
    }

    /**
     * Download printable Purchase Return / Gate Pass PDF.
     */
    public function downloadPdf($id)
    {
        $return = PurchaseReturn::with([
            'vendor',
            'purchase.lot',
            'warehouse',
            'coil',
            'bankDetail',
            'creator'
        ])->findOrFail($id);

        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';
        $filename = "Purchase_Return_{$return->return_no}_" . date('Ymd') . '.pdf';

        return PdfService::viewToResponse('pdf.purchase_return_voucher', compact('return', 'padBase64'), $filename);
    }
}

