<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "customers";

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

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function returns()
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function getSalesDueAttribute(): float
    {
        return (float) $this->sales()->whereNull('deleted_at')->sum('due_payment');
    }

    public function getTotalDueAttribute(): float
    {
        return (float)($this->opening_balance ?? 0.00) + $this->sales_due;
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getUnallocatedPaymentsAttribute(): float
    {
        return (float) $this->payments()->whereNull('sale_id')->sum('amount');
    }

    public function getNetBalanceAttribute(): float
    {
        $opening = (float) ($this->opening_balance ?? 0);
        $salesTotal = (float) $this->sales()->whereNull('deleted_at')->sum('payble');
        $paymentsTotal = (float) $this->payments()->sum('amount');
        $returnsTotal = (float) $this->returns()->where('status', '!=', 'rejected')->sum('total_refund_amount');

        return $opening + $salesTotal - $paymentsTotal - $returnsTotal;
    }

    public function getAdvanceCreditAttribute(): float
    {
        $net = $this->net_balance;
        return $net < 0 ? abs($net) : 0.00;
    }

    public function getEffectiveDueAttribute(): float
    {
        $net = $this->net_balance;
        return $net > 0 ? $net : 0.00;
    }

    public function calculateFinancials(?float $salesTotal = null, ?float $paymentsTotal = null, ?float $returnsTotal = null): array
    {
        $opening = (float) ($this->opening_balance ?? 0);
        $sales = $salesTotal !== null ? $salesTotal : (float) $this->sales()->whereNull('deleted_at')->sum('payble');
        $payments = $paymentsTotal !== null ? $paymentsTotal : (float) $this->payments()->sum('amount');
        $returns = $returnsTotal !== null ? $returnsTotal : (float) $this->returns()->where('status', '!=', 'rejected')->sum('total_refund_amount');

        $net = $opening + $sales - $payments - $returns;

        return [
            'opening_balance' => $opening,
            'sales_total'     => $sales,
            'payments_total'  => $payments,
            'returns_total'   => $returns,
            'net_balance'     => $net,
            'advance_credit'  => $net < 0 ? abs($net) : 0.00,
            'effective_due'   => $net > 0 ? $net : 0.00,
        ];
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->total_due;
    }

    /**
     * Automatically reconcile and allocate unallocated customer collections (where sale_id is null)
     * to unpaid sales orders in FIFO order (oldest unpaid invoice first).
     */
    public static function reconcileUnallocatedPayments(?int $customerId = null): void
    {
        $customerQuery = static::query();
        if ($customerId) {
            $customerQuery->where('id', $customerId);
        } else {
            $customerQuery->whereHas('payments', function ($pq) {
                $pq->whereNull('sale_id')->where('payment_for', 2)->where('amount', '>', 0);
            })->whereHas('sales', function ($sq) {
                $sq->where('due_payment', '>', 0)->whereNull('deleted_at');
            });
        }

        $customers = $customerQuery->get();

        foreach ($customers as $cust) {
            $unallocatedPayments = Payment::where('customer_id', $cust->id)
                ->whereNull('sale_id')
                ->where('payment_for', 2)
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

                $unpaidSales = Sale::where('customer_id', $cust->id)
                    ->where('due_payment', '>', 0)
                    ->whereNull('deleted_at')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($unpaidSales->isEmpty()) {
                    break;
                }

                $remainingAmt = (float) $payment->amount;

                foreach ($unpaidSales as $sale) {
                    if ($remainingAmt <= 0.0001) {
                        break;
                    }

                    $currentDue = (float) $sale->due_payment;
                    if ($currentDue <= 0.0001) continue;

                    $alloc = min($remainingAmt, $currentDue);

                    $sale->advanced_payment = (float)$sale->advanced_payment + $alloc;
                    $sale->due_payment = max(0, (float)($sale->payble ?? $sale->total) - (float)$sale->advanced_payment);
                    $sale->status = $sale->due_payment <= 0.0001 ? 'paid' : 'partial';
                    $sale->save();

                    if (abs($alloc - (float)$payment->amount) < 0.0001) {
                        // Entire payment is consumed by this sale
                        $payment->sale_id = $sale->id;
                        $prefix = $payment->remarks ? $payment->remarks . ' — ' : '';
                        $payment->remarks = $prefix . 'Applied to ' . $sale->order_no;
                        $payment->save();
                        $remainingAmt = 0;
                        break;
                    } else {
                        // Partially consumed: create the allocated portion for this sale
                        Payment::create([
                            'customer_id'    => $cust->id,
                            'sale_id'        => $sale->id,
                            'payment_for'    => 2,
                            'payment_method' => $payment->payment_method,
                            'bank_detail_id' => $payment->bank_detail_id,
                            'transaction_ref'=> $payment->transaction_ref,
                            'payment_date'   => $payment->payment_date ?: now()->toDateString(),
                            'amount'         => $alloc,
                            'remarks'        => 'Applied to ' . $sale->order_no,
                            'status'         => 1,
                            'created_by'     => $payment->created_by,
                        ]);

                        // Reduce remaining unallocated amount
                        $payment->amount = (float)$payment->amount - $alloc;
                        $payment->save();
                        $remainingAmt -= $alloc;
                    }
                }
            }
        }
    }
}

