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
}

