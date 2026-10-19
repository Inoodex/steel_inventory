<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Payment;
use App\Models\BankDetail;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PdfService;

class VendorAdvanceController extends Controller
{
    /**
     * Display a listing of vendor advance prepayments and active advance balances.
     */
    public function index(Request $request)
    {
        // 1. All active vendors with financial calculations
        $vendorsQuery = Vendor::select('id', 'name', 'company', 'phone', 'email', 'opening_balance')
            ->withSum('purchases', 'total_price')
            ->withSum('payments', 'amount')
            ->orderBy('name');

        if ($request->filled('search_vendor')) {
            $term = $request->search_vendor;
            $vendorsQuery->where(function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('company', 'like', "%{$term}%");
            });
        }

        $allVendors = $vendorsQuery->get()->map(function ($v) {
            $opening = (float)($v->opening_balance ?? 0);
            $purchasesTotal = (float)($v->purchases_sum_total_price ?? 0);
            $paymentsTotal = (float)($v->payments_sum_amount ?? 0);
            $net = $opening + $purchasesTotal - $paymentsTotal;
            $v->advance_balance = $net < 0 ? abs($net) : 0.00;
            $v->net_due = $net > 0 ? $net : 0.00;
            return $v;
        });

        // Filter vendors with active advance balances (where we overpaid or paid advance deposits)
        $vendorsWithAdvance = $allVendors->filter(function($v) {
            return $v->advance_balance > 0;
        })->sortByDesc('advance_balance')->values();

        $totalActiveVendorAdvance = (float) $vendorsWithAdvance->sum('advance_balance');

        // 2. Advance disbursements transaction history
        $disbursementsQuery = Payment::with(['vendor', 'bankDetail', 'creator'])
            ->where('payment_for', 3)
            ->whereNull('purchase_id')
            ->latest('id');

        if ($request->filled('vendor_id')) {
            $disbursementsQuery->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('date_from')) {
            $disbursementsQuery->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $disbursementsQuery->whereDate('payment_date', '<=', $request->date_to);
        }
        if ($request->filled('payment_method')) {
            $disbursementsQuery->where('payment_method', $request->payment_method);
        }

        $totalDisbursementsAmount = (float) (clone $disbursementsQuery)->sum('amount');
        $advanceDisbursements = $disbursementsQuery->paginate(15)->withQueryString();

        // 3. Bank and MFS Accounts for modal
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        $vendorList = Vendor::where('status', '1')->orderBy('name')->get();

        return view('frontend.pages.advances.vendor_index', compact(
            'vendorsWithAdvance',
            'totalActiveVendorAdvance',
            'advanceDisbursements',
            'totalDisbursementsAmount',
            'bankAccounts',
            'vendorList'
        ));
    }

    /**
     * Show the dedicated form for paying advance to a vendor.
     */
    public function create(Request $request)
    {
        $vendorList = Vendor::select('id', 'name', 'company', 'phone', 'email', 'address', 'opening_balance')
            ->withSum('purchases', 'total_price')
            ->withSum('payments', 'amount')
            ->where('status', '1')
            ->orderBy('name')
            ->get()
            ->map(function ($v) {
                $opening = (float)($v->opening_balance ?? 0);
                $purchasesTotal = (float)($v->purchases_sum_total_price ?? 0);
                $paymentsTotal = (float)($v->payments_sum_amount ?? 0);
                $net = $opening + $purchasesTotal - $paymentsTotal;
                $v->advance_balance = $net < 0 ? abs($net) : 0.00;
                $v->net_due = $net > 0 ? $net : 0.00;
                return $v;
            });

        $selectedVendorId = $request->query('vendor_id');
        $selectedVendor = $selectedVendorId ? $vendorList->firstWhere('id', $selectedVendorId) : null;

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.advances.vendor_create', compact(
            'vendorList',
            'selectedVendor',
            'selectedVendorId',
            'bankAccounts'
        ));
    }

    /**
     * Store a newly disbursed vendor advance payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'      => 'required|exists:vendors,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,mobile_banking,cheque',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'payment_date'   => 'nullable|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);
        $amount = (float) $validated['amount'];
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        $bankDetailId = ($paymentMethod !== 'cash') ? ($validated['bank_detail_id'] ?? null) : null;
        $trxRef = ($paymentMethod !== 'cash') ? ($validated['transaction_ref'] ?? null) : null;
        $paymentDate = $validated['payment_date'] ?? date('Y-m-d');
        $remarks = $validated['remarks'] ?: 'Vendor Advance Prepayment / Supplier Deposit';

        DB::beginTransaction();
        try {
            // Create payment record (payment_for = 3: Vendor disbursement, purchase_id = null: Advance)
            $payment = Payment::create([
                'payment_for'     => 3,
                'vendor_id'       => $vendor->id,
                'purchase_id'     => null,
                'payment_method'  => $paymentMethod,
                'bank_detail_id'  => $bankDetailId,
                'transaction_ref' => $trxRef,
                'payment_date'    => $paymentDate,
                'amount'          => $amount,
                'remarks'         => $remarks,
                'created_by'      => Auth::id(),
                'status'          => 1,
            ]);

            // Auto-post double-entry journal voucher
            try {
                $cashAcc = ChartOfAccount::where('account_code', '1110')->first()
                        ?? ChartOfAccount::where('account_name', 'like', '%Cash%')->first();
                $apAcc = ChartOfAccount::where('account_code', '2110')->first();

                $disbursingAcc = $cashAcc;
                if ($paymentMethod !== 'cash' && !empty($bankDetailId)) {
                    $bank = BankDetail::find($bankDetailId);
                    $disbursingAcc = $bank?->resolveChartOfAccount()
                        ?? ChartOfAccount::where('account_code', '1120')->first()
                        ?? $cashAcc;
                }

                if ($disbursingAcc && $apAcc && function_exists('postJournalEntry')) {
                    $methodLabel = match($paymentMethod) {
                        'bank'           => 'Bank Transfer',
                        'mobile_banking' => 'Mobile Banking',
                        'cheque'         => 'Cheque',
                        default          => 'Cash'
                    };
                    $refText = $trxRef ? " [Ref: {$trxRef}]" : "";

                    postJournalEntry([
                        'entry_date'     => $paymentDate,
                        'reference_type' => 'vendor_advance',
                        'reference_id'   => $payment->id,
                        'description'    => "Advance Prepayment to Supplier {$vendor->name} via {$methodLabel}{$refText}",
                        'status'         => 'approved',
                        'created_by'     => Auth::id(),
                        'items'          => [
                            [
                                'account_id'  => $apAcc->id,
                                'debit'       => $amount,
                                'credit'      => 0.00,
                                'description' => "Advance deposit paid to {$vendor->name}"
                            ],
                            [
                                'account_id'  => $disbursingAcc->id,
                                'debit'       => 0.00,
                                'credit'      => $amount,
                                'description' => "Disbursement from {$disbursingAcc->account_name} to {$vendor->name} via {$methodLabel}{$refText}"
                            ]
                        ]
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Vendor advance journal voucher skipped: " . $e->getMessage());
            }

            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Advance Paid!',
                'text'  => "Advance payment of ৳" . number_format($amount, 2) . " successfully recorded for {$vendor->name}.",
            ]);

            return back()->with('success', "Advance payment of ৳" . number_format($amount, 2) . " recorded successfully for {$vendor->name}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to record vendor advance: " . $e->getMessage());
            return back()->with('error', "Failed to record advance: " . $e->getMessage())->withInput();
        }
    }

    /**
     * Process a return/refund of vendor advance prepayment back into cash/bank.
     */
    public function refund(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'      => 'required|exists:vendors,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,mobile_banking,cheque',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'refund_date'    => 'nullable|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);
        $financials = $vendor->calculateFinancials();
        $maxReturn = (float) $financials['advance_balance'];
        $amount = (float) $validated['amount'];

        if ($amount > $maxReturn + 0.0001) {
            return back()->with('error', "Return amount (৳" . number_format($amount, 2) . ") exceeds active vendor advance balance (৳" . number_format($maxReturn, 2) . ").")->withInput();
        }

        $paymentMethod = $validated['payment_method'] ?? 'cash';
        $bankDetailId = ($paymentMethod !== 'cash') ? ($validated['bank_detail_id'] ?? null) : null;
        $trxRef = ($paymentMethod !== 'cash') ? ($validated['transaction_ref'] ?? null) : null;
        $refundDate = $validated['refund_date'] ?? date('Y-m-d');
        $remarks = $validated['remarks'] ?: "Advance return received back from supplier {$vendor->name}";

        DB::beginTransaction();
        try {
            // Negative payment record reduces vendor cumulative payments, reducing advance balance
            $payment = Payment::create([
                'payment_for'     => 3,
                'vendor_id'       => $vendor->id,
                'purchase_id'     => null,
                'payment_method'  => $paymentMethod,
                'bank_detail_id'  => $bankDetailId,
                'transaction_ref' => $trxRef,
                'payment_date'    => $refundDate,
                'amount'          => -$amount,
                'remarks'         => $remarks,
                'created_by'      => Auth::id(),
                'status'          => 1,
            ]);

            // Auto-post double-entry journal voucher
            try {
                $cashAcc = ChartOfAccount::where('account_code', '1110')->first()
                        ?? ChartOfAccount::where('account_name', 'like', '%Cash%')->first();
                $apAcc = ChartOfAccount::where('account_code', '2110')->first();

                $receivingAcc = $cashAcc;
                if ($paymentMethod !== 'cash' && !empty($bankDetailId)) {
                    $bank = BankDetail::find($bankDetailId);
                    $receivingAcc = $bank?->resolveChartOfAccount()
                        ?? ChartOfAccount::where('account_code', '1120')->first()
                        ?? $cashAcc;
                }

                if ($receivingAcc && $apAcc && function_exists('postJournalEntry')) {
                    $methodLabel = match($paymentMethod) {
                        'bank'           => 'Bank Transfer',
                        'mobile_banking' => 'Mobile Banking',
                        'cheque'         => 'Cheque',
                        default          => 'Cash'
                    };
                    $refText = $trxRef ? " [Ref: {$trxRef}]" : "";

                    postJournalEntry([
                        'entry_date'     => $refundDate,
                        'reference_type' => 'vendor_advance_refund',
                        'reference_id'   => $payment->id,
                        'description'    => "Advance Refund Received from Supplier {$vendor->name} via {$methodLabel}{$refText}",
                        'status'         => 'approved',
                        'created_by'     => Auth::id(),
                        'items'          => [
                            [
                                'account_id'  => $receivingAcc->id,
                                'debit'       => $amount,
                                'credit'      => 0.00,
                                'description' => "Advance refund received from {$vendor->name} into {$receivingAcc->account_name} via {$methodLabel}{$refText}"
                            ],
                            [
                                'account_id'  => $apAcc->id,
                                'debit'       => 0.00,
                                'credit'      => $amount,
                                'description' => "Reduction of advance balance with supplier {$vendor->name}"
                            ]
                        ]
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Vendor advance refund journal voucher skipped: " . $e->getMessage());
            }

            // Sync bank account balance if received via bank
            if ($bankDetailId) {
                $bank = BankDetail::find($bankDetailId);
                $bank?->syncCurrentBalance();
            }

            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Advance Returned!',
                'text'  => "Advance payment of ৳" . number_format($amount, 2) . " successfully returned from {$vendor->name}.",
            ]);

            return back()->with('success', "Advance payment of ৳" . number_format($amount, 2) . " returned successfully from {$vendor->name}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to return vendor advance: " . $e->getMessage());
            return back()->with('error', "Failed to return advance: " . $e->getMessage())->withInput();
        }
    }

    /**
     * Download vendor advance balances report as PDF (mPDF standard).
     */
    public function pdf(Request $request)
    {
        $vendorsQuery = Vendor::select('id', 'name', 'company', 'phone', 'email', 'opening_balance')
            ->withSum('purchases', 'total_price')
            ->withSum('payments', 'amount')
            ->orderBy('name');

        $vendorsWithAdvance = $vendorsQuery->get()->map(function ($v) {
            $opening = (float)($v->opening_balance ?? 0);
            $purchasesTotal = (float)($v->purchases_sum_total_price ?? 0);
            $paymentsTotal = (float)($v->payments_sum_amount ?? 0);
            $net = $opening + $purchasesTotal - $paymentsTotal;
            $v->advance_balance = $net < 0 ? abs($net) : 0.00;
            return $v;
        })->filter(function($v) {
            return $v->advance_balance > 0;
        })->sortByDesc('advance_balance')->values();

        $totalActiveVendorAdvance = (float) $vendorsWithAdvance->sum('advance_balance');

        return PdfService::viewToResponse(
            'frontend.pages.advances.vendor_pdf',
            compact('vendorsWithAdvance', 'totalActiveVendorAdvance'),
            'Vendor_Advance_Balances_' . date('Ymd') . '.pdf'
        );
    }

    /**
     * Download or view payment voucher PDF for a vendor advance disbursement.
     */
    public function voucherPdf($id)
    {
        $payment = Payment::with(['vendor', 'bankDetail', 'creator'])
            ->where('payment_for', 3)
            ->whereNull('purchase_id')
            ->findOrFail($id);

        return PdfService::viewToResponse(
            'frontend.pages.advances.vendor_voucher_pdf',
            compact('payment'),
            'Vendor_Advance_Voucher_' . $payment->id . '.pdf'
        );
    }
}
