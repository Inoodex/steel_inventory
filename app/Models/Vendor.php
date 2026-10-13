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
}
