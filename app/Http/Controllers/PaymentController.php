<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;

class PaymentController extends Controller
{
    public function payments(Request $request, $id, $payment_for = 2)
    {
        $payments = Payment::with('bankDetail')->where('sale_id', $id)->latest()->get();
        $bill = Sale::with('customer')->where('id', $id)->firstOrFail();
        $bankAccounts = \App\Models\BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        
        return view('frontend.pages.payment.bill_payment', compact('payments', 'id', 'payment_for', 'bill', 'bankAccounts'));
    }

    public function addPayment(Request $request)
    {
        $request->validate([
            'id'             => 'required|exists:sales,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $bill = Sale::where('id', $request->id)->first();
        if (!$bill) {
            return redirect()->back()->with(['error' => 'Sale not found.']);
        }

        $payment = new Payment;
        $payment->payment_for = $request->payment_for ?? 2;
        $payment->customer_id = $bill->customer_id;
        $payment->sale_id = $bill->id;
        $payment->payment_method = $request->payment_method ?? ($request->payment_method_id ?? 'cash');
        $payment->bank_detail_id = $request->bank_detail_id ?: null;
        $payment->transaction_ref = $request->transaction_ref ?: null;
        $payment->amount = $request->amount;
        $payment->remarks = $request->remarks;
        $payment->created_by = \Illuminate\Support\Facades\Auth::id();
        $payment->status = 1;
        $payment->save();

        $bill->advanced_payment = ($bill->advanced_payment ?? 0) + $request->amount;
        $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
        $bill->status = $bill->due_payment <= 0 ? 'paid' : 'partial';
        $bill->update();

        // Auto-post double-entry journal voucher for due collection
        try {
            $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
            $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();
            
            $collectionAcc = $cashAcc;
            if ($payment->payment_method !== 'cash') {
                if (!empty($payment->bank_detail_id)) {
                    $bank = \App\Models\BankDetail::find($payment->bank_detail_id);
                    $collectionAcc = $bank?->resolveChartOfAccount() 
                        ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                        ?? $cashAcc;
                } else {
                    $collectionAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                }
            }

            if ($collectionAcc && $arAcc && (float) $payment->amount > 0) {
                $methodLabel = match($payment->payment_method) {
                    'bank' => 'Bank Transfer',
                    'cheque' => 'Cheque',
                    'mobile_banking' => 'Mobile Banking',
                    default => 'Cash'
                };
                $refText = $payment->transaction_ref ? " [Ref/Cheque: {$payment->transaction_ref}]" : "";

                postJournalEntry([
                    'entry_date' => date('Y-m-d'),
                    'reference_type' => 'sale',
                    'reference_id' => $bill->id,
                    'description' => "Due collection for {$bill->order_no} via {$methodLabel}{$refText}",
                    'items' => [
                        [
                            'account_id' => $collectionAcc->id,
                            'debit' => (float) $payment->amount,
                            'credit' => 0.00,
                            'description' => "Due collected for {$bill->order_no} via {$methodLabel}{$refText}"
                        ],
                        [
                            'account_id' => $arAcc->id,
                            'debit' => 0.00,
                            'credit' => (float) $payment->amount,
                            'description' => "Accounts Receivable credit for {$bill->order_no}"
                        ]
                    ]
                ]);
            }
        } catch (\Throwable $e) {}

        return redirect()->back()->with(['success' => 'Payment added successfully.']);
    }

    public function updatePayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Payment not found.']);
        }

        $bill = Sale::where('id', $payment->sale_id)->first();
        if (!$bill) {
            return redirect()->back()->with(['error' => 'Sale not found.']);
        }

        $bill->advanced_payment = max(0, ($bill->advanced_payment ?? 0) - $payment->amount + $request->amount);
        $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
        $bill->status = $bill->due_payment <= 0 ? 'paid' : 'partial';
        $bill->update();

        $payment->payment_method = $request->payment_method_id ?? $payment->payment_method;
        $payment->amount = $request->amount;
        $payment->remarks = $request->remarks;
        $payment->update();

        return redirect()->back()->with(['success' => 'Payment updated successfully.']);
    }

    public function deletePayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Payment not found.']);
        }

        $bill = Sale::where('id', $payment->sale_id)->first();
        if ($bill) {
            $bill->advanced_payment = max(0, ($bill->advanced_payment ?? 0) - $payment->amount);
            $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
            $bill->status = $bill->due_payment <= 0 ? 'paid' : ($bill->advanced_payment > 0 ? 'partial' : 'credit');
            $bill->update();
        }

        $payment->delete();

        return redirect()->back()->with(['success' => 'Payment deleted successfully.']);
    }

    /**
     * Record a disbursement / due settlement payment to a Vendor for purchases
     */
    public function addVendorPayment(Request $request)
    {
        $request->validate([
            'vendor_id'      => 'required|exists:vendors,id',
            'purchase_id'    => 'nullable|exists:purchases,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $vendor = \App\Models\Vendor::findOrFail($request->vendor_id);
        $totalAmount = (float) $request->amount;
        $remainingToAllocate = $totalAmount;

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $purchase = null;
            if ($request->filled('purchase_id')) {
                $purchase = \App\Models\Purchase::where('id', $request->purchase_id)->where('vendor_id', $vendor->id)->first();
                if ($purchase) {
                    $purchase->payment = (float)$purchase->payment + $totalAmount;
                    $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                    $purchase->save();
                }
            } else {
                // Settle against oldest unpaid purchases of this vendor
                $unpaidPurchases = \App\Models\Purchase::where('vendor_id', $vendor->id)
                    ->where('due', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($unpaidPurchases as $p) {
                    if ($remainingToAllocate <= 0) break;
                    $dueOnThis = (float) $p->due;
                    $allocation = min($remainingToAllocate, $dueOnThis);
                    $p->payment = (float)$p->payment + $allocation;
                    $p->due = max(0, (float)$p->total_price - (float)$p->payment);
                    $p->save();
                    $remainingToAllocate -= $allocation;
                    if (!$purchase) $purchase = $p; // primary reference
                }
            }

            // Create Payment record for vendor (payment_for = 3: Purchase/Vendor)
            $payment = new Payment;
            $payment->payment_for = 3;
            $payment->vendor_id = $vendor->id;
            $payment->purchase_id = $purchase ? $purchase->id : null;
            $payment->payment_method = $request->payment_method ?? 'cash';
            $payment->bank_detail_id = $request->bank_detail_id ?: null;
            $payment->transaction_ref = $request->transaction_ref ?: null;
            $payment->amount = $totalAmount;
            $payment->remarks = $request->remarks;
            $payment->created_by = \Illuminate\Support\Facades\Auth::id();
            $payment->status = 1;
            $payment->save();

            // Auto-post double-entry journal voucher for vendor due disbursement
            // Debit: 2110 Accounts Payable | Credit: 1110 Cash in Hand or 1120-00X Bank
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $apAcc = \App\Models\ChartOfAccount::where('account_code', '2110')->first();

                $disbursementAcc = $cashAcc;
                if ($payment->payment_method !== 'cash') {
                    if (!empty($payment->bank_detail_id)) {
                        $bank = \App\Models\BankDetail::find($payment->bank_detail_id);
                        $disbursementAcc = $bank?->resolveChartOfAccount() 
                            ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                            ?? $cashAcc;
                    } else {
                        $disbursementAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                    }
                }

                if ($disbursementAcc && $apAcc && $totalAmount > 0) {
                    $methodLabel = match($payment->payment_method) {
                        'bank' => 'Bank Transfer',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash in Hand'
                    };
                    $refText = $payment->transaction_ref ? " [Ref: {$payment->transaction_ref}]" : "";
                    $poText = $purchase ? " for PO #{$purchase->id}" : "";

                    postJournalEntry([
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'purchase_payment',
                        'reference_id' => $payment->id,
                        'description' => "Vendor Due payment to {$vendor->name}{$poText} via {$methodLabel}{$refText}",
                        'items' => [
                            [
                                'account_id' => $apAcc->id,
                                'debit' => $totalAmount,
                                'credit' => 0.00,
                                'description' => "Accounts Payable settlement to {$vendor->name}{$poText}"
                            ],
                            [
                                'account_id' => $disbursementAcc->id,
                                'debit' => 0.00,
                                'credit' => $totalAmount,
                                'description' => "{$methodLabel} disbursement to {$vendor->name}{$refText}"
                            ]
                        ]
                    ]);
                }
            } catch (\Throwable $e) {}

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->back()->with(['success' => 'Vendor due payment of ৳' . number_format($totalAmount, 2) . ' recorded successfully.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to record vendor payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete / revert a vendor payment
     */
    public function deleteVendorPayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->where('payment_for', 3)->firstOrFail();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($payment->purchase_id) {
                $purchase = \App\Models\Purchase::find($payment->purchase_id);
                if ($purchase) {
                    $purchase->payment = max(0, (float)$purchase->payment - (float)$payment->amount);
                    $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                    $purchase->save();
                }
            }

            // Remove journal entry if exists
            \App\Models\JournalEntry::where('reference_type', 'purchase_payment')
                ->where('reference_id', $payment->id)
                ->delete();

            $payment->delete();

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Vendor payment record deleted and due balance reverted.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to delete vendor payment: ' . $e->getMessage()]);
        }
    }
}
