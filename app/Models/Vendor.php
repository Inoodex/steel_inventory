<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'address',
        'bin_number',
        'tin_number',
        'opening_balance',
        'status',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'vendor_id');
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function getNetBalanceAttribute(): float
    {
        $opening = (float) ($this->opening_balance ?? 0);
        $purchasesTotal = (float) $this->purchases()->sum('total_price');
        $paymentsTotal = (float) $this->payments()->sum('amount');

        return $opening + $purchasesTotal - $paymentsTotal;
    }

    public function getAdvanceBalanceAttribute(): float
    {
        $net = $this->net_balance;
        return $net < 0 ? abs($net) : 0.00;
    }

    public function getEffectivePayableAttribute(): float
    {
        $net = $this->net_balance;
        return $net > 0 ? $net : 0.00;
    }

    public function calculateFinancials(?float $purchasesTotal = null, ?float $paymentsTotal = null): array
    {
        $opening = (float) ($this->opening_balance ?? 0);
        $purchases = $purchasesTotal !== null ? $purchasesTotal : (float) $this->purchases()->sum('total_price');
        $payments = $paymentsTotal !== null ? $paymentsTotal : (float) $this->payments()->sum('amount');

        $net = $opening + $purchases - $payments;

        return [
            'opening_balance' => $opening,
            'purchases_total' => $purchases,
            'payments_total'  => $payments,
            'net_balance'     => $net,
            'advance_balance' => $net < 0 ? abs($net) : 0.00,
            'payable_due'     => $net > 0 ? $net : 0.00,
        ];
    }

    /**
     * Automatically reconcile and allocate unallocated vendor disbursements (where purchase_id is null)
     * to unpaid purchases in FIFO order (oldest unpaid PO first).
     */
    public static function reconcileUnallocatedPayments(?int $vendorId = null): void
    {
        $vendorQuery = static::query();
        if ($vendorId) {
            $vendorQuery->where('id', $vendorId);
        } else {
            $vendorQuery->whereHas('payments', function ($pq) {
                $pq->whereNull('purchase_id')->where('payment_for', 3)->where('amount', '>', 0);
            })->whereHas('purchases', function ($sq) {
                $sq->where('due', '>', 0)->whereNull('deleted_at');
            });
        }

        $vendors = $vendorQuery->get();

        foreach ($vendors as $vendor) {
            $unallocatedPayments = Payment::where('vendor_id', $vendor->id)
                ->whereNull('purchase_id')
                ->where('payment_for', 3)
                ->where('amount', '>', 0)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($unallocatedPayments->isEmpty()) {
                continue;
            }

            foreach ($unallocatedPayments as $payment) {
                // Do not allocate payments dedicated to opening balance settlements
                if (stripos($payment->remarks ?? '', 'opening balance') !== false || stripos($payment->remarks ?? '', 'opening due') !== false) {
                    continue;
                }

                $unpaidPurchases = Purchase::where('vendor_id', $vendor->id)
                    ->where('due', '>', 0)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($unpaidPurchases->isEmpty()) {
                    break;
                }

                $remainingAmt = (float) $payment->amount;

                foreach ($unpaidPurchases as $purchase) {
                    if ($remainingAmt <= 0.0001) {
                        break;
                    }

                    $currentDue = (float) $purchase->due;
                    if ($currentDue <= 0.0001) continue;

                    $alloc = min($remainingAmt, $currentDue);

                    $purchase->payment = (float)$purchase->payment + $alloc;
                    $purchase->due = max(0, (float)$purchase->total_price - (float)$purchase->payment);
                    $purchase->save();

                    if (abs($alloc - (float)$payment->amount) < 0.0001) {
                        // Entire payment is consumed by this purchase
                        $payment->purchase_id = $purchase->id;
                        $prefix = $payment->remarks ? $payment->remarks . ' — ' : '';
                        $payment->remarks = $prefix . 'Applied to PO #' . $purchase->id;
                        $payment->saveQuietly();
                        $remainingAmt = 0;
                        break;
                    } else {
                        // Partially consumed: create the allocated portion for this purchase
                        Payment::create([
                            'vendor_id'      => $vendor->id,
                            'purchase_id'    => $purchase->id,
                            'payment_for'    => 3,
                            'payment_method' => $payment->payment_method,
                            'bank_detail_id' => $payment->bank_detail_id,
                            'transaction_ref'=> $payment->transaction_ref,
                            'payment_date'   => $payment->payment_date ?: now()->toDateString(),
                            'amount'         => $alloc,
                            'remarks'        => 'Applied to PO #' . $purchase->id,
                            'status'         => 1,
                            'created_by'     => $payment->created_by,
                        ]);

                        // Reduce remaining unallocated amount
                        $payment->amount = (float)$payment->amount - $alloc;
                        $payment->saveQuietly();
                        $remainingAmt -= $alloc;
                    }
                }
            }
        }
    }
}

