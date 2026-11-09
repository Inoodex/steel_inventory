<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\Lot;

class PaymentController extends Controller
{
    public function addPayment(Request $request)
    {
        $request->validate([
            'id'             => 'required|exists:sales,id',
            'amount'         => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $bill = Sale::where('id', $request->id)->first();
        if (!$bill) {
            return redirect()->back()->with(['error' => 'Sale not found.']);
        }

        $amount = max(0, (float) ($request->amount ?? 0));
        $discount = max(0, (float) ($request->discount ?? 0));
        $totalSettling = $amount + $discount;

        if ($totalSettling <= 0.0001) {
            return redirect()->back()->withInput()->with(['error' => 'Please enter a valid payment amount or discount.']);
        }

        $dueOnBill = (float) $bill->due_payment;
        if ($totalSettling > ($dueOnBill + 0.01)) {
            return redirect()->back()->withInput()->with(['error' => 'Total settlement (৳' . number_format($totalSettling, 2) . ') cannot exceed invoice due amount (৳' . number_format($dueOnBill, 2) . ').']);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $paymentMethod = $request->payment_method ?? ($request->payment_method_id ?? 'cash');
            $isBank = ($paymentMethod !== 'cash');

            if ($discount > 0) {
                $bill->discount = (float)($bill->discount ?? 0) + $discount;
                $bill->payble = max(0, (float)($bill->payble ?? $bill->total) - $discount);
            }

            $payment = null;
            if ($amount > 0) {
                $payment = new Payment;
                $payment->payment_for = $request->payment_for ?? 2;
                $payment->customer_id = $bill->customer_id;
                $payment->sale_id = $bill->id;
                $payment->payment_method = $paymentMethod;
                $payment->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                $payment->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                $payment->amount = $amount;
                $payment->remarks = ($request->remarks ?: 'Invoice Due Payment') . ($discount > 0 ? " (Discount: ৳" . number_format($discount, 2) . ")" : "");
                $payment->created_by = \Illuminate\Support\Facades\Auth::id();
                $payment->status = 1;
                $payment->save();

                $bill->advanced_payment = ($bill->advanced_payment ?? 0) + $amount;
            }

            $bill->due_payment = max(0, ($bill->payble ?? $bill->total) - $bill->advanced_payment);
            $bill->status = $bill->due_payment <= 0 ? 'paid' : 'partial';
            $bill->update();

            // Auto-post double-entry journal voucher for due collection
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();
                $discountAcc = \App\Models\ChartOfAccount::where('account_code', '5120')->first();

                $collectionAcc = $cashAcc;
                if ($paymentMethod !== 'cash') {
                    if (!empty($request->bank_detail_id)) {
                        $bank = \App\Models\BankDetail::find($request->bank_detail_id);
                        $collectionAcc = $bank?->resolveChartOfAccount() 
                            ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                            ?? $cashAcc;
                    } else {
                        $collectionAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                    }
                }

                if ($arAcc && $totalSettling > 0 && function_exists('postJournalEntry')) {
                    $methodLabel = match($paymentMethod) {
                        'bank' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash'
                    };
                    $refText = $request->transaction_ref ? " [Ref/Cheque: {$request->transaction_ref}]" : "";

                    $journalItems = [];
                    if ($amount > 0 && $collectionAcc) {
                        $journalItems[] = [
                            'account_id' => $collectionAcc->id,
                            'debit' => $amount,
                            'credit' => 0.00,
                            'description' => "Due collected for {$bill->order_no} via {$methodLabel}{$refText}"
                        ];
                    }
                    if ($discount > 0 && $discountAcc) {
                        $journalItems[] = [
                            'account_id' => $discountAcc->id,
                            'debit' => $discount,
                            'credit' => 0.00,
                            'description' => "Discount allowed for {$bill->order_no}"
                        ];
                    }
                    $journalItems[] = [
                        'account_id' => $arAcc->id,
                        'debit' => 0.00,
                        'credit' => $amount + ($discountAcc ? $discount : 0),
                        'description' => "Accounts Receivable credit for {$bill->order_no}"
                    ];

                    postJournalEntry([
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'sale',
                        'reference_id' => $bill->id,
                        'description' => "Due collection for {$bill->order_no} via {$methodLabel}{$refText}" . ($discount > 0 ? " (Discount: ৳" . number_format($discount, 2) . ")" : ""),
                        'items' => $journalItems
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Customer sale payment journal posting failed for sale #{$bill->id}: " . $e->getMessage());
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with(['success' => 'Payment recorded successfully.']);
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
            'amount'         => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'payment_type'   => 'nullable|string|in:advance_deposit,general_payment,opening_due,auto_due_settlement',
            'payment_method' => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'payment_date'   => 'nullable|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $amount = max(0, (float) ($request->amount ?? 0));
        $discount = max(0, (float) ($request->discount ?? 0));
        $totalSettling = $amount + $discount;

        if ($totalSettling <= 0.0001) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid collection amount or discount.');
        }

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

            $remainingCash = $amount;
            $remainingDiscount = $discount;
            $remainingTotal = $totalSettling;
            $createdPayments = [];

            // 1. Settle Opening Balance first if customer has opening due and not an advance_deposit
            if ($paymentType !== 'advance_deposit' && (float)($customer->opening_balance ?? 0) > 0) {
                $openingDue = (float) $customer->opening_balance;
                $openingSettle = min($remainingTotal, $openingDue);

                $openingCash = min($remainingCash, $openingSettle);
                $openingDisc = min($remainingDiscount, $openingSettle - $openingCash);

                $customer->opening_balance = max(0, $openingDue - $openingSettle);
                $customer->save();

                $remainingTotal -= $openingSettle;
                $remainingCash -= $openingCash;
                $remainingDiscount -= $openingDisc;

                if ($openingCash > 0) {
                    $p = new Payment;
                    $p->payment_for = 2;
                    $p->customer_id = $customer->id;
                    $p->sale_id = null;
                    $p->payment_method = $paymentMethod;
                    $p->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                    $p->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                    $p->payment_date = $paymentDate;
                    $p->amount = $openingCash;
                    $p->remarks = 'Customer Opening Due Settlement' . ($openingDisc > 0 ? " (Discount: ৳" . number_format($openingDisc, 2) . ")" : "");
                    $p->created_by = \Illuminate\Support\Facades\Auth::id();
                    $p->status = 1;
                    $p->save();
                    $createdPayments[] = $p;
                }
            }

            // 2. Settle customer's unpaid sales invoices in FIFO order
            if ($paymentType !== 'advance_deposit' && $remainingTotal > 0.0001) {
                $unpaidSales = Sale::where('customer_id', $customer->id)
                    ->where('due_payment', '>', 0)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($unpaidSales as $sale) {
                    if ($remainingTotal <= 0.0001) break;

                    $due = (float) $sale->due_payment;
                    $alloc = min($remainingTotal, $due);

                    $cashAlloc = min($remainingCash, $alloc);
                    $discAlloc = min($remainingDiscount, $alloc - $cashAlloc);

                    if ($discAlloc > 0) {
                        $sale->discount = (float)($sale->discount ?? 0) + $discAlloc;
                        $sale->payble = max(0, (float)($sale->payble ?? $sale->total) - $discAlloc);
                    }

                    if ($cashAlloc > 0) {
                        $sale->advanced_payment = (float)$sale->advanced_payment + $cashAlloc;
                    }

                    $sale->due_payment = max(0, (float)($sale->payble ?? $sale->total) - (float)$sale->advanced_payment);
                    $sale->status = $sale->due_payment <= 0.0001 ? 'paid' : 'partial';
                    $sale->save();

                    if ($cashAlloc > 0) {
                        $p = new Payment;
                        $p->payment_for = 2;
                        $p->customer_id = $customer->id;
                        $p->sale_id = $sale->id;
                        $p->payment_method = $paymentMethod;
                        $p->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                        $p->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                        $p->payment_date = $paymentDate;
                        $p->amount = $cashAlloc;
                        $p->remarks = 'Due collection for ' . $sale->order_no . ($discAlloc > 0 ? " (Discount: ৳" . number_format($discAlloc, 2) . ")" : "");
                        $p->created_by = \Illuminate\Support\Facades\Auth::id();
                        $p->status = 1;
                        $p->save();
                        $createdPayments[] = $p;
                    }

                    $remainingTotal -= $alloc;
                    $remainingCash -= $cashAlloc;
                    $remainingDiscount -= $discAlloc;
                }
            }

            // 3. Excess collection or direct advance prepayment deposit
            if ($remainingCash > 0.0001 || (empty($createdPayments) && $amount > 0)) {
                $advAmt = ($paymentType === 'advance_deposit') ? $amount : $remainingCash;
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
            if ($isBank && $payment && !empty($payment->bank_detail_id)) {
                \App\Models\BankDetail::find($payment->bank_detail_id)?->syncCurrentBalance();
            }

            // Double Entry Journal Post
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();
                $discountAcc = \App\Models\ChartOfAccount::where('account_code', '5120')->first();

                $collectionAcc = $cashAcc;
                if ($payment && $payment->payment_method !== 'cash' && !empty($payment->bank_detail_id)) {
                    $bank = \App\Models\BankDetail::find($payment->bank_detail_id);
                    $collectionAcc = $bank?->resolveChartOfAccount()
                        ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first()
                        ?? $cashAcc;
                }

                if ($arAcc && $totalSettling > 0 && function_exists('postJournalEntry')) {
                    $methodLabel = $payment ? match($payment->payment_method) {
                        'bank' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash'
                    } : 'Settlement';
                    $refText = ($payment && $payment->transaction_ref) ? " [Ref: {$payment->transaction_ref}]" : "";
                    
                    $entryDesc = $paymentType === 'advance_deposit'
                        ? "Advance Prepayment deposit from {$customer->name} via {$methodLabel}{$refText}"
                        : "Due collection from {$customer->name} via {$methodLabel}{$refText}" . ($discount > 0 ? " (Discount: ৳" . number_format($discount, 2) . ")" : "");

                    $journalItems = [];
                    if ($amount > 0 && $collectionAcc) {
                        $journalItems[] = [
                            'account_id' => $collectionAcc->id,
                            'debit' => $amount,
                            'credit' => 0.00,
                            'description' => "Deposit received from {$customer->name} via {$methodLabel}{$refText}"
                        ];
                    }
                    if ($discount > 0 && $discountAcc) {
                        $journalItems[] = [
                            'account_id' => $discountAcc->id,
                            'debit' => $discount,
                            'credit' => 0.00,
                            'description' => "Sales discount/allowance granted to {$customer->name}"
                        ];
                    }
                    $journalItems[] = [
                        'account_id' => $arAcc->id,
                        'debit' => 0.00,
                        'credit' => $amount + ($discountAcc ? $discount : 0),
                        'description' => ($paymentType === 'advance_deposit' ? "Advance credit balance for " : "AR credit for ") . $customer->name
                    ];

                    postJournalEntry([
                        'entry_date' => ($payment ? $payment->payment_date : null) ?: date('Y-m-d'),
                        'reference_type' => 'customer_payment',
                        'reference_id' => $customer->id,
                        'description' => $entryDesc,
                        'items' => $journalItems
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Customer payment collection journal skipped: ' . $e->getMessage());
            }

            \Illuminate\Support\Facades\DB::commit();
            
            $discMsg = $discount > 0 ? " (including ৳" . number_format($discount, 2) . " discount)" : "";
            $successMsg = $paymentType === 'advance_deposit'
                ? "Advance deposit of ৳" . number_format($amount, 2) . " received successfully for {$customer->name}."
                : "Due settlement of ৳" . number_format($totalSettling, 2) . "{$discMsg} recorded successfully for {$customer->name}.";

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
                    if ($diff > ((float) $bill->due_payment + 0.01)) {
                        $maxAllowed = $oldAmount + (float) $bill->due_payment;
                        return redirect()->back()->withInput()->with(['error' => 'Updated payment exceeds invoice total. Maximum allowed payment for this sale is ৳' . number_format($maxAllowed, 2) . '.']);
                    }
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
            'amount'         => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,bank,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref'=> 'nullable|string|max:255',
            'payment_date'   => 'nullable|date',
            'remarks'        => 'nullable|string|max:500',
        ]);

        $vendor = \App\Models\Vendor::findOrFail($request->vendor_id);
        $totalAmount = max(0, (float) ($request->amount ?? 0));
        $discount = max(0, (float) ($request->discount ?? 0));
        $totalSettling = $totalAmount + $discount;

        if ($totalSettling <= 0.0001) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid disbursement amount or discount.');
        }

        $remainingCash = $totalAmount;
        $remainingDiscount = $discount;
        $remainingTotal = $totalSettling;

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
                            if ($remainingTotal <= 0.0001) break;
                            $dueOnThis = (float) $p->due;
                            $allocation = min($remainingTotal, $dueOnThis);

                            $cashAlloc = min($remainingCash, $allocation);
                            $discAlloc = min($remainingDiscount, $allocation - $cashAlloc);

                            if ($discAlloc > 0) {
                                $p->discount = (float)($p->discount ?? 0) + $discAlloc;
                                $p->total_price = max(0, (float)$p->total_price - $discAlloc);
                            }
                            if ($cashAlloc > 0) {
                                $p->payment = (float)$p->payment + $cashAlloc;
                            }
                            $p->due = max(0, (float)$p->total_price - (float)$p->payment);
                            $p->save();

                            if ($p->lot_id) {
                                $lot = Lot::find($p->lot_id);
                                if ($lot) {
                                    $lot->total_amount = $lot->purchases()->sum('total_price');
                                    $lot->save();
                                }
                            }

                            if ($cashAlloc > 0) {
                                $payRec = new Payment;
                                $payRec->payment_for = 3;
                                $payRec->vendor_id = $vendor->id;
                                $payRec->purchase_id = $p->id;
                                $payRec->payment_method = $paymentMethod;
                                $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                                $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                                $payRec->payment_date = $paymentDate;
                                $payRec->amount = $cashAlloc;
                                $payRec->remarks = ($request->remarks ?: ('Due payment for PO #' . $p->id)) . ($discAlloc > 0 ? " (Discount: ৳" . number_format($discAlloc, 2) . ")" : "");
                                $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                                $payRec->status = 1;
                                $payRec->save();
                                $createdVendorPayments[] = $payRec;
                            }

                            $remainingTotal -= $allocation;
                            $remainingCash -= $cashAlloc;
                            $remainingDiscount -= $discAlloc;
                        }
                    } else {
                        $dueOnPurchase = (float) $purchase->due;
                        $allocation = min($remainingTotal, $dueOnPurchase);

                        $cashAlloc = min($remainingCash, $allocation);
                        $discAlloc = min($remainingDiscount, $allocation - $cashAlloc);

                        if ($discAlloc > 0) {
                            $purchase->discount = (float)($purchase->discount ?? 0) + $discAlloc;
                            $purchase->total_price = max(0, (float)$purchase->total_price - $discAlloc);
                        }
                        if ($cashAlloc > 0) {
                            $purchase->payment = (float)$purchase->payment + $cashAlloc;
                        }
                        $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                        $purchase->save();

                        if ($purchase->lot_id) {
                            $lot = Lot::find($purchase->lot_id);
                            if ($lot) {
                                $lot->total_amount = $lot->purchases()->sum('total_price');
                                $lot->save();
                            }
                        }

                        if ($cashAlloc > 0) {
                            $payRec = new Payment;
                            $payRec->payment_for = 3;
                            $payRec->vendor_id = $vendor->id;
                            $payRec->purchase_id = $purchase->id;
                            $payRec->payment_method = $paymentMethod;
                            $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                            $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                            $payRec->payment_date = $paymentDate;
                            $payRec->amount = $cashAlloc;
                            $payRec->remarks = ($request->remarks ?: ('Due payment for PO #' . $purchase->id)) . ($discAlloc > 0 ? " (Discount: ৳" . number_format($discAlloc, 2) . ")" : "");
                            $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                            $payRec->status = 1;
                            $payRec->save();
                            $createdVendorPayments[] = $payRec;
                        }

                        $remainingTotal -= $allocation;
                        $remainingCash -= $cashAlloc;
                        $remainingDiscount -= $discAlloc;
                    }
                }
            } else {
                // Settle against vendor opening balance if present
                if ((float)($vendor->opening_balance ?? 0) > 0) {
                    $openingDue = (float) $vendor->opening_balance;
                    $openingAlloc = min($remainingTotal, $openingDue);

                    $cashAlloc = min($remainingCash, $openingAlloc);
                    $discAlloc = min($remainingDiscount, $openingAlloc - $cashAlloc);

                    $vendor->opening_balance = max(0, $openingDue - $openingAlloc);
                    $vendor->save();

                    if ($cashAlloc > 0) {
                        $payRec = new Payment;
                        $payRec->payment_for = 3;
                        $payRec->vendor_id = $vendor->id;
                        $payRec->purchase_id = null;
                        $payRec->payment_method = $paymentMethod;
                        $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                        $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                        $payRec->payment_date = $paymentDate;
                        $payRec->amount = $cashAlloc;
                        $payRec->remarks = ($request->remarks ?: 'Vendor Opening Balance Settlement') . ($discAlloc > 0 ? " (Discount: ৳" . number_format($discAlloc, 2) . ")" : "");
                        $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                        $payRec->status = 1;
                        $payRec->save();
                        $createdVendorPayments[] = $payRec;
                    }

                    $remainingTotal -= $openingAlloc;
                    $remainingCash -= $cashAlloc;
                    $remainingDiscount -= $discAlloc;
                }

                // Settle remaining against oldest unpaid purchases of this vendor
                $unpaidPurchases = \App\Models\Purchase::where('vendor_id', $vendor->id)
                    ->where('due', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($unpaidPurchases as $p) {
                    if ($remainingTotal <= 0.0001) break;
                    $dueOnThis = (float) $p->due;
                    $allocation = min($remainingTotal, $dueOnThis);

                    $cashAlloc = min($remainingCash, $allocation);
                    $discAlloc = min($remainingDiscount, $allocation - $cashAlloc);

                    if ($discAlloc > 0) {
                        $p->discount = (float)($p->discount ?? 0) + $discAlloc;
                        $p->total_price = max(0, (float)$p->total_price - $discAlloc);
                    }
                    if ($cashAlloc > 0) {
                        $p->payment = (float)$p->payment + $cashAlloc;
                    }
                    $p->due = max(0, (float)$p->total_price - (float)$p->payment);
                    $p->save();

                    if ($p->lot_id) {
                        $lot = Lot::find($p->lot_id);
                        if ($lot) {
                            $lot->total_amount = $lot->purchases()->sum('total_price');
                            $lot->save();
                        }
                    }

                    if ($cashAlloc > 0) {
                        $payRec = new Payment;
                        $payRec->payment_for = 3;
                        $payRec->vendor_id = $vendor->id;
                        $payRec->purchase_id = $p->id;
                        $payRec->payment_method = $paymentMethod;
                        $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                        $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                        $payRec->payment_date = $paymentDate;
                        $payRec->amount = $cashAlloc;
                        $payRec->remarks = ($request->remarks ?: ('Due payment for PO #' . $p->id)) . ($discAlloc > 0 ? " (Discount: ৳" . number_format($discAlloc, 2) . ")" : "");
                        $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                        $payRec->status = 1;
                        $payRec->save();
                        $createdVendorPayments[] = $payRec;
                    }

                    $remainingTotal -= $allocation;
                    $remainingCash -= $cashAlloc;
                    $remainingDiscount -= $discAlloc;
                }
            }

            // Excess amount is vendor advance
            if ($remainingCash > 0.0001 || (empty($createdVendorPayments) && $totalAmount > 0)) {
                $payRec = new Payment;
                $payRec->payment_for = 3;
                $payRec->vendor_id = $vendor->id;
                $payRec->purchase_id = null;
                $payRec->payment_method = $paymentMethod;
                $payRec->bank_detail_id = $isBank ? ($request->bank_detail_id ?: null) : null;
                $payRec->transaction_ref = $isBank ? ($request->transaction_ref ?: null) : null;
                $payRec->payment_date = $paymentDate;
                $payRec->amount = $remainingCash;
                $payRec->remarks = $request->remarks ?: 'Advance Prepayment to Vendor';
                $payRec->created_by = \Illuminate\Support\Facades\Auth::id();
                $payRec->status = 1;
                $payRec->save();
                $createdVendorPayments[] = $payRec;
            }

            $payment = $createdVendorPayments[0] ?? null;

            // Sync bank balance if bank was used
            if ($isBank && $payment && !empty($payment->bank_detail_id)) {
                \App\Models\BankDetail::find($payment->bank_detail_id)?->syncCurrentBalance();
            }

            // Auto-post double-entry journal voucher for vendor due disbursement
            // Debit: 2110 Accounts Payable | Credit: 1110 Cash in Hand or 1120-00X Bank | Credit: 5110 Raw Steel / Discount Received
            try {
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $apAcc = \App\Models\ChartOfAccount::where('account_code', '2110')->first();
                $purchaseAcc = \App\Models\ChartOfAccount::where('account_code', '5110')->first();

                $disbursementAcc = $cashAcc;
                if ($payment && $payment->payment_method !== 'cash') {
                    if (!empty($payment->bank_detail_id)) {
                        $bank = \App\Models\BankDetail::find($payment->bank_detail_id);
                        $disbursementAcc = $bank?->resolveChartOfAccount() 
                            ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                            ?? $cashAcc;
                    } else {
                        $disbursementAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                    }
                }

                if ($apAcc && $totalSettling > 0) {
                    $methodLabel = $payment ? match($payment->payment_method) {
                        'bank' => 'Bank Transfer',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash in Hand'
                    } : 'Settlement';
                    $refText = ($payment && $payment->transaction_ref) ? " [Ref: {$payment->transaction_ref}]" : "";
                    $poText = $purchase ? " for PO #{$purchase->id}" : "";

                    $journalItems = [
                        [
                            'account_id' => $apAcc->id,
                            'debit' => $totalSettling,
                            'credit' => 0.00,
                            'description' => "Accounts Payable settlement to {$vendor->name}{$poText}"
                        ]
                    ];

                    if ($totalAmount > 0 && $disbursementAcc) {
                        $journalItems[] = [
                            'account_id' => $disbursementAcc->id,
                            'debit' => 0.00,
                            'credit' => $totalAmount,
                            'description' => "{$methodLabel} disbursement to {$vendor->name}{$refText}"
                        ];
                    }

                    if ($discount > 0 && $purchaseAcc) {
                        $journalItems[] = [
                            'account_id' => $purchaseAcc->id,
                            'debit' => 0.00,
                            'credit' => $discount,
                            'description' => "Purchase discount/rebate received from {$vendor->name}"
                        ];
                    }

                    postJournalEntry([
                        'entry_date' => $paymentDate,
                        'reference_type' => 'purchase_payment',
                        'reference_id' => $payment ? $payment->id : $vendor->id,
                        'description' => "Vendor Due payment to {$vendor->name}{$poText} via {$methodLabel}{$refText}" . ($discount > 0 ? " (Discount: ৳" . number_format($discount, 2) . ")" : ""),
                        'items' => $journalItems
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Vendor payment journal posting failed for payment #{$payment->id}: " . $e->getMessage());
            }

            \Illuminate\Support\Facades\DB::commit();

            $discMsg = $discount > 0 ? " (including ৳" . number_format($discount, 2) . " discount/rebate)" : "";
            return redirect()->back()->with(['success' => 'Vendor due settlement of ৳' . number_format($totalSettling, 2) . "{$discMsg} recorded successfully."]);
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
