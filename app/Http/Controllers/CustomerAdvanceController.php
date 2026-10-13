<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\BankDetail;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PdfService;

class CustomerAdvanceController extends Controller
{
    /**
     * Display a listing of customer advances and unallocated prepayments.
     */
    public function index(Request $request)
    {
        // 1. All active customers with their financial balance calculations
        $customersQuery = Customer::select('id', 'name', 'company', 'phone', 'email', 'opening_balance')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'payble')
            ->withSum('payments', 'amount')
            ->withSum(['returns' => function($q) {
                $q->where('status', '!=', 'rejected');
            }], 'total_refund_amount')
            ->orderBy('name');

        if ($request->filled('search_customer')) {
            $term = $request->search_customer;
            $customersQuery->where(function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('company', 'like', "%{$term}%");
            });
        }

        $allCustomers = $customersQuery->get()->map(function ($c) {
            $opening = (float)($c->opening_balance ?? 0);
            $salesTotal = (float)($c->sales_sum_payble ?? 0);
            $paymentsTotal = (float)($c->payments_sum_amount ?? 0);
            $returnsTotal = (float)($c->returns_sum_total_refund_amount ?? 0);
            $net = $opening + $salesTotal - $paymentsTotal - $returnsTotal;
            $c->advance_credit = $net < 0 ? abs($net) : 0.00;
            $c->net_due = $net > 0 ? $net : 0.00;
            return $c;
        });

        // Filter customers with active advance balances
        $customersWithAdvance = $allCustomers->filter(function($c) {
            return $c->advance_credit > 0;
        })->sortByDesc('advance_credit')->values();

        $totalActiveAdvanceCredit = (float) $customersWithAdvance->sum('advance_credit');

        // 2. Advance deposit transactions history
        $depositsQuery = Payment::with(['customer', 'bankDetail', 'creator'])
            ->where('payment_for', 2)
            ->whereNull('sale_id')
            ->latest('id');

        if ($request->filled('customer_id')) {
            $depositsQuery->where('customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $depositsQuery->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $depositsQuery->whereDate('payment_date', '<=', $request->date_to);
        }
        if ($request->filled('payment_method')) {
            $depositsQuery->where('payment_method', $request->payment_method);
        }

        $totalDepositsAmount = (float) (clone $depositsQuery)->sum('amount');
        $advanceDeposits = $depositsQuery->paginate(15)->withQueryString();

        // 3. Bank and MFS Accounts for modal
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        $customerList = Customer::where('status', 'active')->orderBy('name')->get();

        return view('frontend.pages.advances.customer_index', compact(
            'customersWithAdvance',
            'totalActiveAdvanceCredit',
            'advanceDeposits',
            'totalDepositsAmount',
            'bankAccounts',
            'customerList'
        ));
    }

    /**
     * Show the dedicated form for receiving customer advance payment.
     */
    public function create(Request $request)
    {
        $customerList = Customer::select('id', 'name', 'company', 'phone', 'email', 'address', 'opening_balance')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'payble')
            ->withSum('payments', 'amount')
            ->withSum(['returns' => function($q) {
                $q->where('status', '!=', 'rejected');
            }], 'total_refund_amount')
            ->where('status', 'active')
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

        $selectedCustomerId = $request->query('customer_id');
        $selectedCustomer = $selectedCustomerId ? $customerList->firstWhere('id', $selectedCustomerId) : null;

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.advances.customer_create', compact(
            'customerList',
            'selectedCustomer',
            'selectedCustomerId',
            'bankAccounts'
        ));
    }

    /**
     * Store a newly received customer advance deposit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,mobile_banking,cheque',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'payment_date'   => 'nullable|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $amount = (float) $validated['amount'];
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        $bankDetailId = ($paymentMethod !== 'cash') ? ($validated['bank_detail_id'] ?? null) : null;
        $trxRef = ($paymentMethod !== 'cash') ? ($validated['transaction_ref'] ?? null) : null;
        $paymentDate = $validated['payment_date'] ?? date('Y-m-d');
        $remarks = $validated['remarks'] ?: 'Customer Advance Prepayment / Deposit';

        DB::beginTransaction();
        try {
            // Create payment record (payment_for = 2: Customer collection, sale_id = null: Advance)
            $payment = Payment::create([
                'payment_for'     => 2,
                'customer_id'     => $customer->id,
                'sale_id'         => null,
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
                $arAcc = ChartOfAccount::where('account_code', '1130')->first();

                $receivingAcc = $cashAcc;
                if ($paymentMethod !== 'cash' && !empty($bankDetailId)) {
                    $bank = BankDetail::find($bankDetailId);
                    $receivingAcc = $bank?->resolveChartOfAccount()
                        ?? ChartOfAccount::where('account_code', '1120')->first()
                        ?? $cashAcc;
                }

                if ($receivingAcc && $arAcc && function_exists('postJournalEntry')) {
                    $methodLabel = match($paymentMethod) {
                        'bank'           => 'Bank Transfer',
                        'mobile_banking' => 'Mobile Banking',
                        'cheque'         => 'Cheque',
                        default          => 'Cash'
                    };
                    $refText = $trxRef ? " [Ref: {$trxRef}]" : "";

                    postJournalEntry([
                        'entry_date'     => $paymentDate,
                        'reference_type' => 'customer_advance',
                        'reference_id'   => $payment->id,
                        'description'    => "Customer Advance Prepayment from {$customer->name} via {$methodLabel}{$refText}",
                        'status'         => 'approved',
                        'created_by'     => Auth::id(),
                        'items'          => [
                            [
                                'account_id'  => $receivingAcc->id,
                                'debit'       => $amount,
                                'credit'      => 0.00,
                                'description' => "Advance deposit received from {$customer->name} via {$methodLabel}{$refText}"
                            ],
                            [
                                'account_id'  => $arAcc->id,
                                'debit'       => 0.00,
                                'credit'      => $amount,
                                'description' => "Advance credit liability for {$customer->name}"
                            ]
                        ]
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Customer advance journal voucher skipped: " . $e->getMessage());
            }

            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Advance Received!',
                'text'  => "Advance prepayment of ৳" . number_format($amount, 2) . " successfully recorded for {$customer->name}.",
            ]);

            return back()->with('success', "Advance prepayment of ৳" . number_format($amount, 2) . " recorded successfully for {$customer->name}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to record customer advance: " . $e->getMessage());
            return back()->with('error', "Failed to record advance: " . $e->getMessage())->withInput();
        }
    }

    /**
     * Download customer advance balances report as PDF (mPDF standard).
     */
    public function pdf(Request $request)
    {
        $customersQuery = Customer::select('id', 'name', 'company', 'phone', 'email', 'opening_balance')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'payble')
            ->withSum('payments', 'amount')
            ->withSum(['returns' => function($q) {
                $q->where('status', '!=', 'rejected');
            }], 'total_refund_amount')
            ->orderBy('name');

        $customersWithAdvance = $customersQuery->get()->map(function ($c) {
            $opening = (float)($c->opening_balance ?? 0);
            $salesTotal = (float)($c->sales_sum_payble ?? 0);
            $paymentsTotal = (float)($c->payments_sum_amount ?? 0);
            $returnsTotal = (float)($c->returns_sum_total_refund_amount ?? 0);
            $net = $opening + $salesTotal - $paymentsTotal - $returnsTotal;
            $c->advance_credit = $net < 0 ? abs($net) : 0.00;
            return $c;
        })->filter(function($c) {
            return $c->advance_credit > 0;
        })->sortByDesc('advance_credit')->values();

        $totalActiveAdvanceCredit = (float) $customersWithAdvance->sum('advance_credit');

        return PdfService::viewToResponse(
            'frontend.pages.advances.customer_pdf',
            compact('customersWithAdvance', 'totalActiveAdvanceCredit'),
            'Customer_Advance_Balances_' . date('Ymd') . '.pdf'
        );
    }

    /**
     * Download or view money receipt PDF for a customer advance deposit.
     */
    public function receiptPdf($id)
    {
        $payment = Payment::with(['customer', 'bankDetail', 'creator'])
            ->where('payment_for', 2)
            ->whereNull('sale_id')
            ->findOrFail($id);

        return PdfService::viewToResponse(
            'frontend.pages.advances.customer_receipt_pdf',
            compact('payment'),
            'Customer_Advance_Receipt_' . $payment->id . '.pdf'
        );
    }
}
