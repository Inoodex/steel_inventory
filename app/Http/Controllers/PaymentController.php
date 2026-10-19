<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Purchase;

class PaymentController extends Controller
{
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

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $payment = new Payment;
            $payment->payment_for = $request->payment_for ?? 2;
            $payment->customer_id = $bill->customer_id;
            $payment->sale_id = $bill->id;
            $paymentMethod = $request->payment_method ?? ($request->payment_method_id ?? 'cash');
            $isBank = ($paymentMethod !== 'cash');
            $payment->payment_method = $paymentMethod;
            $payment->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
            $payment->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
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

                if ($collectionAcc && $arAcc && (float) $payment->amount > 0 && function_exists('postJournalEntry')) {
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
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Customer sale payment journal posting failed for sale #{$bill->id}: " . $e->getMessage());
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Payment added successfully.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Failed to add payment for sale #{$bill->id}: " . $e->getMessage());
            return redirect()->back()->with(['error' => 'Failed to add payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Settle opening due, general account payment, or receive advance prepayment before orders exist.
     */
    public function payCustomerDue(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_type'   => 'nullable|string|in:advance_deposit,general_payment,opening_due,auto_due_settlement',
            'payment_method' => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'payment_date'   => 'nullable|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $amount = (float) $request->amount;
        $paymentType = $request->payment_type ?? 'auto_due_settlement';

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $paymentMethod = $request->payment_method ?? 'cash';
            $isBank = ($paymentMethod !== 'cash');
            $paymentDate = $request->filled('payment_date') ? $request->payment_date : date('Y-m-d');
            
            $defaultRemarks = match($paymentType) {
                'advance_deposit' => 'Customer Advance Prepayment / Deposit',
                'opening_due'     => 'Customer Opening Due Settlement',
                default           => 'Customer Due Collection'
            };
            $remarks = $request->remarks ?: $defaultRemarks;

            $remaining = $amount;
            $createdPayments = [];

            // 1. Settle Opening Balance first if customer has opening due and not an advance_deposit
            if ($paymentType !== 'advance_deposit' && (float)($customer->opening_balance ?? 0) > 0) {
                $openingSettle = min($remaining, (float)$customer->opening_balance);
                $customer->opening_balance = max(0, (float)$customer->opening_balance - $openingSettle);
                $customer->save();
                $remaining -= $openingSettle;

                $p = new Payment;
                $p->payment_for = 2;
                $p->customer_id = $customer->id;
                $p->sale_id = null;
                $p->payment_method = $paymentMethod;
                $p->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                $p->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                $p->payment_date = $paymentDate;
                $p->amount = $openingSettle;
                $p->remarks = 'Customer Opening Due Settlement';
                $p->created_by = \Illuminate\Support\Facades\Auth::id();
                $p->status = 1;
                $p->save();
                $createdPayments[] = $p;
            }

            // 2. If remaining amount > 0 and not strictly an advance prepayment deposit:
            // Settle customer's unpaid sales invoices in FIFO order (oldest unpaid invoice first)
            if ($paymentType !== 'advance_deposit' && $remaining > 0.0001) {
                $unpaidSales = Sale::where('customer_id', $customer->id)
                    ->where('due_payment', '>', 0)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($unpaidSales as $sale) {
                    if ($remaining <= 0.0001) break;

                    $due = (float) $sale->due_payment;
                    $alloc = min($remaining, $due);

                    $sale->advanced_payment = (float)$sale->advanced_payment + $alloc;
                    $sale->due_payment = max(0, (float)($sale->payble ?? $sale->total) - (float)$sale->advanced_payment);
                    $sale->status = $sale->due_payment <= 0.0001 ? 'paid' : 'partial';
                    $sale->save();

                    $p = new Payment;
                    $p->payment_for = 2;
                    $p->customer_id = $customer->id;
                    $p->sale_id = $sale->id;
                    $p->payment_method = $paymentMethod;
                    $p->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                    $p->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                    $p->payment_date = $paymentDate;
                    $p->amount = $alloc;
                    $p->remarks = 'Due collection for ' . $sale->order_no;
                    $p->created_by = \Illuminate\Support\Facades\Auth::id();
                    $p->status = 1;
                    $p->save();
                    $createdPayments[] = $p;

                    $remaining -= $alloc;
                }
            }

            // 3. Excess collection or direct advance prepayment deposit
            if ($remaining > 0.0001 || empty($createdPayments)) {
                $advAmt = ($paymentType === 'advance_deposit') ? $amount : $remaining;
                $p = new Payment;
                $p->payment_for = 2;
                $p->customer_id = $customer->id;
                $p->sale_id = null;
                $p->payment_method = $paymentMethod;
                $p->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                $p->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                $p->payment_date = $paymentDate;
                $p->amount = $advAmt;
                $p->remarks = ($paymentType === 'advance_deposit') ? 'Advance Prepayment deposit' : 'Customer Excess Due Collection (Advance Credit)';
                $p->created_by = \Illuminate\Support\Facades\Auth::id();
                $p->status = 1;
                $p->save();
                $createdPayments[] = $p;
            }

            $payment = $createdPayments[0] ?? null;

            // 4. Sync bank balance if bank was used
            if ($isBank && !empty($payment->bank_detail_id)) {
                \App\Models\BankDetail::find($payment->bank_detail_id)?->syncCurrentBalance();
            }

            // Double Entry Journal Post
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();

                $collectionAcc = $cashAcc;
                if ($payment->payment_method !== 'cash' && !empty($payment->bank_detail_id)) {
                    $bank = \App\Models\BankDetail::find($payment->bank_detail_id);
                    $collectionAcc = $bank?->resolveChartOfAccount()
                        ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first()
                        ?? $cashAcc;
                }

                if ($collectionAcc && $arAcc && $amount > 0 && function_exists('postJournalEntry')) {
                    $methodLabel = match($payment->payment_method) {
                        'bank' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash'
                    };
                    $refText = $payment->transaction_ref ? " [Ref: {$payment->transaction_ref}]" : "";
                    
                    $entryDesc = $paymentType === 'advance_deposit'
                        ? "Advance Prepayment deposit from {$customer->name} via {$methodLabel}{$refText}"
                        : "Due collection from {$customer->name} via {$methodLabel}{$refText}";

                    postJournalEntry([
                        'entry_date' => $payment->payment_date ?: date('Y-m-d'),
                        'reference_type' => 'customer_payment',
                        'reference_id' => $customer->id,
                        'description' => $entryDesc,
                        'items' => [
                            [
                                'account_id' => $collectionAcc->id,
                                'debit' => $amount,
                                'credit' => 0.00,
                                'description' => "Deposit received from {$customer->name} via {$methodLabel}{$refText}"
                            ],
                            [
                                'account_id' => $arAcc->id,
                                'debit' => 0.00,
                                'credit' => $amount,
                                'description' => ($paymentType === 'advance_deposit' ? "Advance credit balance for " : "AR credit for ") . $customer->name
                            ]
                        ]
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Customer payment collection journal skipped: ' . $e->getMessage());
            }

            \Illuminate\Support\Facades\DB::commit();
            
            $successMsg = $paymentType === 'advance_deposit'
                ? "Advance deposit of ৳" . number_format($amount, 2) . " received successfully for {$customer->name}."
                : "Payment of ৳" . number_format($amount, 2) . " recorded successfully for {$customer->name}.";

            return redirect()->back()->with('success', $successMsg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,cheque,mobile_banking,2,3',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Payment record not found.']);
        }

        // If this is actually a vendor payment, delegate directly to updateVendorPayment
        if ((int)$payment->payment_for === 3 || (!empty($payment->vendor_id) && empty($payment->customer_id))) {
            return $this->updateVendorPayment($request, $id);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $oldAmount = (float) $payment->amount;
            $newAmount = (float) $request->amount;
            $diff = $newAmount - $oldAmount; // e.g. 1500 - 1600 = -100
            $oldBankId = $payment->bank_detail_id;

            $newMethod = $request->payment_method ?? ($request->payment_method_id ?? $payment->payment_method);
            $isBank = in_array($newMethod, ['bank', 'cheque', 'mobile_banking', '2', '3']);
            $newBankId = $isBank ? ($request->bank_detail_id ?: null) : null;

            // 1. Adjust Sale if linked
            if ($payment->sale_id) {
                $bill = Sale::where('id', $payment->sale_id)->first();
                if ($bill) {
                    $bill->advanced_payment = max(0, (float)($bill->advanced_payment ?? 0) + $diff);
                    $bill->due_payment = max(0, (float)($bill->payble ?? $bill->total) - (float)$bill->advanced_payment);
                    $bill->status = $bill->due_payment <= 0.0001 ? 'paid' : ($bill->advanced_payment > 0 ? 'partial' : 'credit');
                    $bill->save();
                }
            }

            // 2. Update Payment record
            $payment->amount = $newAmount;
            $payment->payment_method = $newMethod;
            $payment->bank_detail_id = $newBankId;
            if ($request->has('transaction_ref')) {
                $payment->transaction_ref = $request->transaction_ref ?: null;
            }
            if ($request->filled('payment_date')) {
                $payment->payment_date = $request->payment_date;
            }
            if ($request->has('remarks')) {
                $payment->remarks = $request->remarks;
            }
            $payment->save();

            // 3. Reconcile Customer unallocated dues if applicable
            if ($payment->customer_id) {
                Customer::reconcileUnallocatedPayments($payment->customer_id);
            }

            // 4. Update or synchronize Journal Entries
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();
                $receivingAcc = $cashAcc;
                if ($newBankId) {
                    $bank = \App\Models\BankDetail::find($newBankId);
                    $receivingAcc = $bank?->resolveChartOfAccount() 
                        ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                        ?? $cashAcc;
                } elseif ($newMethod !== 'cash') {
                    $receivingAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                }

                $journal = \App\Models\JournalEntry::where(function ($q) use ($payment) {
                    $q->where('reference_type', 'customer_payment')->where('reference_id', $payment->id);
                })->orWhere(function ($q) use ($payment, $oldAmount) {
                    if ($payment->sale_id) {
                        $q->where('reference_type', 'sale')
                          ->where('reference_id', $payment->sale_id)
                          ->where('total_debit', $oldAmount);
                    }
                })->first();

                if ($journal) {
                    $journal->total_debit = $newAmount;
                    $journal->total_credit = $newAmount;
                    if ($request->filled('payment_date')) {
                        $journal->entry_date = $request->payment_date;
                    }
                    $journal->save();

                    // Update debit item (Cash/Bank)
                    $debitItem = $journal->items()->where('debit', '>', 0)->first();
                    if ($debitItem) {
                        $debitItem->account_id = $receivingAcc->id;
                        $debitItem->debit = $newAmount;
                        $debitItem->save();
                    }
                    // Update credit item (AR 1130)
                    $creditItem = $journal->items()->where('credit', '>', 0)->first();
                    if ($creditItem) {
                        $creditItem->credit = $newAmount;
                        $creditItem->save();
                    }
                }
            } catch (\Throwable $je) {
                \Illuminate\Support\Facades\Log::warning("Customer journal update for payment #{$payment->id} failed: " . $je->getMessage());
            }

            // 5. Synchronize Bank Account Balances
            if ($oldBankId) {
                \App\Models\BankDetail::find($oldBankId)?->syncCurrentBalance();
            }
            if ($newBankId && $newBankId != $oldBankId) {
                \App\Models\BankDetail::find($newBankId)?->syncCurrentBalance();
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Customer payment record updated successfully and dues adjusted.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to update payment: ' . $e->getMessage()]);
        }
    }

    public function deletePayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Payment not found.']);
        }

        if ((int)$payment->payment_for === 3) {
            return $this->deleteVendorPayment($request, $id);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $bankId = $payment->bank_detail_id;
            $customerId = $payment->customer_id;

            $bill = Sale::where('id', $payment->sale_id)->first();
            if ($bill) {
                $bill->advanced_payment = max(0, (float)($bill->advanced_payment ?? 0) - (float)$payment->amount);
                $bill->due_payment = max(0, (float)($bill->payble ?? $bill->total) - (float)$bill->advanced_payment);
                $bill->status = $bill->due_payment <= 0.0001 ? 'paid' : ($bill->advanced_payment > 0 ? 'partial' : 'credit');
                $bill->save();
            }

            // Remove or clean up linked double-entry journal voucher if exists
            $journals = \App\Models\JournalEntry::where(function ($q) use ($payment) {
                $q->where('reference_type', 'customer_payment')->where('reference_id', $payment->id);
            })->orWhere(function ($q) use ($payment) {
                $q->where('reference_type', 'customer_payment')
                  ->where('reference_id', $payment->customer_id)
                  ->where('total_debit', $payment->amount);
            })->get();

            foreach ($journals as $j) {
                $j->items()->delete();
                $j->delete();
            }

            $payment->delete();

            if ($customerId) {
                Customer::reconcileUnallocatedPayments($customerId);
            }

            if ($bankId) {
                \App\Models\BankDetail::find($bankId)?->syncCurrentBalance();
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Payment deleted successfully and ledger balances reverted.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to delete payment: ' . $e->getMessage()]);
        }
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
            $paymentMethod = $request->payment_method ?? 'cash';
            $isBank = ($paymentMethod !== 'cash');
            $paymentDate = $request->payment_date ?: date('Y-m-d');

            $purchase = null;
            $createdVendorPayments = [];

            if ($request->filled('purchase_id')) {
                $purchase = \App\Models\Purchase::where('id', $request->purchase_id)->where('vendor_id', $vendor->id)->first();
                if ($purchase) {
                    if ($purchase->lot_id) {
                        // Settle across unpaid items in this consignment / lot
                        $consignmentPurchases = \App\Models\Purchase::where('lot_id', $purchase->lot_id)
                            ->where('due', '>', 0)
                            ->orderBy('id', 'asc')
                            ->get();

                        foreach ($consignmentPurchases as $p) {
                            if ($remainingToAllocate <= 0) break;
                            $dueOnThis = (float) $p->due;
                            $allocation = min($remainingToAllocate, $dueOnThis);
                            $p->payment = (float)$p->payment + $allocation;
                            $p->due = max(0, (float)$p->total_price - (float)$p->payment);
                            $p->save();

                            $payRec = new Payment;
                            $payRec->payment_for = 3;
                            $payRec->vendor_id = $vendor->id;
                            $payRec->purchase_id = $p->id;
                            $payRec->payment_method = $paymentMethod;
                            $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                            $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                            $payRec->payment_date = $paymentDate;
                            $payRec->amount = $allocation;
                            $payRec->remarks = $request->remarks ?: ('Due payment for PO #' . $p->id);
                            $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                            $payRec->status = 1;
                            $payRec->save();
                            $createdVendorPayments[] = $payRec;

                            $remainingToAllocate -= $allocation;
                        }

                        if ($remainingToAllocate > 0) {
                            $purchase->payment = (float)$purchase->payment + $remainingToAllocate;
                            $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                            $purchase->save();
                        }
                    } else {
                        $purchase->payment = (float)$purchase->payment + $totalAmount;
                        $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                        $purchase->save();

                        $payRec = new Payment;
                        $payRec->payment_for = 3;
                        $payRec->vendor_id = $vendor->id;
                        $payRec->purchase_id = $purchase->id;
                        $payRec->payment_method = $paymentMethod;
                        $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                        $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                        $payRec->payment_date = $paymentDate;
                        $payRec->amount = $totalAmount;
                        $payRec->remarks = $request->remarks ?: ('Due payment for PO #' . $purchase->id);
                        $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                        $payRec->status = 1;
                        $payRec->save();
                        $createdVendorPayments[] = $payRec;
                        $remainingToAllocate = 0;
                    }
                }
            } else {
                // Settle against vendor opening balance if present
                if ((float)($vendor->opening_balance ?? 0) > 0) {
                    $openingDue = (float) $vendor->opening_balance;
                    $openingAlloc = min($remainingToAllocate, $openingDue);
                    $vendor->opening_balance = max(0, $openingDue - $openingAlloc);
                    $vendor->save();

                    $payRec = new Payment;
                    $payRec->payment_for = 3;
                    $payRec->vendor_id = $vendor->id;
                    $payRec->purchase_id = null;
                    $payRec->payment_method = $paymentMethod;
                    $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                    $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                    $payRec->payment_date = $paymentDate;
                    $payRec->amount = $openingAlloc;
                    $payRec->remarks = $request->remarks ?: 'Vendor Opening Balance Settlement';
                    $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                    $payRec->status = 1;
                    $payRec->save();
                    $createdVendorPayments[] = $payRec;

                    $remainingToAllocate -= $openingAlloc;
                }

                // Settle remaining against oldest unpaid purchases of this vendor
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

                    $payRec = new Payment;
                    $payRec->payment_for = 3;
                    $payRec->vendor_id = $vendor->id;
                    $payRec->purchase_id = $p->id;
                    $payRec->payment_method = $paymentMethod;
                    $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                    $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                    $payRec->payment_date = $paymentDate;
                    $payRec->amount = $allocation;
                    $payRec->remarks = $request->remarks ?: ('Due payment for PO #' . $p->id);
                    $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                    $payRec->status = 1;
                    $payRec->save();
                    $createdVendorPayments[] = $payRec;

                    $remainingToAllocate -= $allocation;
                }
            }

            // Excess amount is vendor advance
            if ($remainingToAllocate > 0.0001 || empty($createdVendorPayments)) {
                $payRec = new Payment;
                $payRec->payment_for = 3;
                $payRec->vendor_id = $vendor->id;
                $payRec->purchase_id = null;
                $payRec->payment_method = $paymentMethod;
                $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                $payRec->payment_date = $paymentDate;
                $payRec->amount = $remainingToAllocate;
                $payRec->remarks = $request->remarks ?: 'Advance Prepayment to Vendor';
                $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                $payRec->status = 1;
                $payRec->save();
                $createdVendorPayments[] = $payRec;
            }

            $payment = $createdVendorPayments[0] ?? null;

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
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Vendor payment journal posting failed for payment #{$payment->id}: " . $e->getMessage());
            }

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->back()->with(['success' => 'Vendor due payment of ৳' . number_format($totalAmount, 2) . ' recorded successfully.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to record vendor payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Edit and adjust an existing Vendor disbursement / due payment
     */
    public function updateVendorPayment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,bank,mobile_banking,2,3',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $payment = Payment::where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with(['error' => 'Vendor payment record not found.']);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $oldAmount = (float) $payment->amount;
            $newAmount = (float) $request->amount;
            $diff = $newAmount - $oldAmount; // e.g. 1500 - 1600 = -100
            $oldBankId = $payment->bank_detail_id;

            $newMethod = $request->payment_method ?? $payment->payment_method;
            $isBank = in_array($newMethod, ['bank', 'mobile_banking', '2', '3']);
            $newBankId = $isBank ? ($request->bank_detail_id ?: null) : null;

            // 1. Adjust Purchase if linked
            if ($payment->purchase_id) {
                $purchase = Purchase::find($payment->purchase_id);
                if ($purchase) {
                    $purchase->payment = max(0, (float)($purchase->payment ?? 0) + $diff);
                    $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                    $purchase->save();
                }
            }

            // 2. Update Payment record
            $payment->amount = $newAmount;
            $payment->payment_method = $newMethod;
            $payment->bank_detail_id = $newBankId;
            if ($request->has('transaction_ref')) {
                $payment->transaction_ref = $request->transaction_ref ?: null;
            }
            if ($request->filled('payment_date')) {
                $payment->payment_date = $request->payment_date;
            }
            if ($request->has('remarks')) {
                $payment->remarks = $request->remarks;
            }
            $payment->save();

            // 3. Reconcile Vendor unallocated disbursements if needed
            if ($payment->vendor_id) {
                Vendor::reconcileUnallocatedPayments($payment->vendor_id);
            }

            // 4. Update Journal Entry
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $apAcc = \App\Models\ChartOfAccount::where('account_code', '2110')->first();
                $disbursementAcc = $cashAcc;
                if ($newBankId) {
                    $bank = \App\Models\BankDetail::find($newBankId);
                    $disbursementAcc = $bank?->resolveChartOfAccount() 
                        ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                        ?? $cashAcc;
                } elseif ($newMethod !== 'cash') {
                    $disbursementAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                }

                $journal = \App\Models\JournalEntry::where('reference_type', 'purchase_payment')
                    ->where('reference_id', $payment->id)
                    ->first();

                if ($journal) {
                    $journal->total_debit = $newAmount;
                    $journal->total_credit = $newAmount;
                    if ($request->filled('payment_date')) {
                        $journal->entry_date = $request->payment_date;
                    }
                    $journal->save();

                    // Update debit item (AP 2110)
                    $debitItem = $journal->items()->where('debit', '>', 0)->first();
                    if ($debitItem) {
                        $debitItem->debit = $newAmount;
                        $debitItem->save();
                    }
                    // Update credit item (Disbursement Cash/Bank)
                    $creditItem = $journal->items()->where('credit', '>', 0)->first();
                    if ($creditItem) {
                        $creditItem->account_id = $disbursementAcc->id;
                        $creditItem->credit = $newAmount;
                        $creditItem->save();
                    }
                }
            } catch (\Throwable $je) {
                \Illuminate\Support\Facades\Log::warning("Vendor journal update for payment #{$payment->id} failed: " . $je->getMessage());
            }

            // 5. Synchronize Bank Account Balances
            if ($oldBankId) {
                \App\Models\BankDetail::find($oldBankId)?->syncCurrentBalance();
            }
            if ($newBankId && $newBankId != $oldBankId) {
                \App\Models\BankDetail::find($newBankId)?->syncCurrentBalance();
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Vendor payment updated successfully and purchase due adjusted.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to update vendor payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete / revert a vendor payment
     */
    public function deleteVendorPayment(Request $request, $id)
    {
        $payment = Payment::where('id', $id)->firstOrFail();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $bankId = $payment->bank_detail_id;
            $vendorId = $payment->vendor_id;

            if ($payment->purchase_id) {
                $purchase = \App\Models\Purchase::find($payment->purchase_id);
                if ($purchase) {
                    $purchase->payment = max(0, (float)$purchase->payment - (float)$payment->amount);
                    $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                    $purchase->save();
                }
            }

            // Remove journal entry if exists
            $journals = \App\Models\JournalEntry::where('reference_type', 'purchase_payment')
                ->where('reference_id', $payment->id)
                ->get();

            foreach ($journals as $j) {
                $j->items()->delete();
                $j->delete();
            }

            $payment->delete();

            if ($vendorId) {
                Vendor::reconcileUnallocatedPayments($vendorId);
            }

            if ($bankId) {
                \App\Models\BankDetail::find($bankId)?->syncCurrentBalance();
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Vendor payment record deleted and due balance reverted.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with(['error' => 'Failed to delete vendor payment: ' . $e->getMessage()]);
        }
    }
}
