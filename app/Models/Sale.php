<?php

namespace App\Models;

use App\Models\SalesItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Sale extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_no',
        'order_date',
        'customer_id',
        'qty',
        'subtotal',
        'total',
        'payble',
        'bill',
        'advanced_payment',
        'due_payment',
        'payment_method',
        'bank_detail_id',
        'transaction_ref',
        'discount',
        'sales_by',
        'status',
        'warehouse_id',
        'delivery_status',
        'vat', 
        'tax',
        'delivery_charge',
        'labour_cost',
        'weight_scale_cost',
        'other_charges',
        'charges_payout_status',
        'charges_payout_at',
        'charges_payout_by',
        'charges_payout_note',
        'note',
        'notes',
    ];

    public function getNotesAttribute()
    {
        return $this->attributes['note'] ?? $this->attributes['notes'] ?? null;
    }

    public function setNotesAttribute($value)
    {
        $this->attributes['note'] = $value;
    }

    public function getPayableAmountAttribute(): float
    {
        return (float)($this->attributes['payble'] ?? $this->attributes['total'] ?? 0);
    }

    public function getDueAmountAttribute(): float
    {
        return (float)($this->attributes['due_payment'] ?? 0);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float)($this->attributes['advanced_payment'] ?? 0);
    }

    protected $casts = [
        'charges_payout_at' => 'datetime',
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::saving(function ($sale) {
    //         $sale->due_payment = $sale->payble - $sale->advanced_payment;

    //         // Auto-update payment_status
    //         if ($sale->advanced_payment == 0) {
    //             $sale->payment_status = 'pending';
    //         } elseif ($sale->advanced_payment > 0 && $sale->advanced_payment < $sale->payble) {
    //             $sale->payment_status = 'partial';
    //         } elseif ($sale->advanced_payment >= $sale->payble) {
    //             $sale->payment_status = 'paid';
    //         }
    //     });
    // }
    public function items()
    {
        return $this->hasMany(SalesItem::class, 'order_id');
    }

    public function returns()
    {
        return $this->hasMany(ProductReturn::class, 'sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_by');
    }

    public function payoutUser()
    {
        return $this->belongsTo(User::class, 'charges_payout_by');
    }

    public function bankDetail()
    {
        return $this->belongsTo(BankDetail::class, 'bank_detail_id');
    }

    public function workerPayoutItems()
    {
        return $this->hasMany(WorkerPayoutItem::class, 'sale_id');
    }

    public function workerPayouts()
    {
        return $this->belongsToMany(WorkerPayout::class, 'worker_payout_items', 'sale_id', 'worker_payout_id')
            ->distinct();
    }

    public function getPaidLabourCostAttribute(): float
    {
        return (float) $this->workerPayoutItems()->where('charge_type', 'labour')->sum('amount');
    }

    public function getDueLabourCostAttribute(): float
    {
        return max(0, (float)$this->labour_cost - $this->paid_labour_cost);
    }

    public function getPaidDeliveryChargeAttribute(): float
    {
        return (float) $this->workerPayoutItems()->where('charge_type', 'delivery')->sum('amount');
    }

    public function getDueDeliveryChargeAttribute(): float
    {
        return max(0, (float)$this->delivery_charge - $this->paid_delivery_charge);
    }

    public function getPaidWeightScaleCostAttribute(): float
    {
        return (float) $this->workerPayoutItems()->where('charge_type', 'weight_scale')->sum('amount');
    }

    public function getDueWeightScaleCostAttribute(): float
    {
        return max(0, (float)$this->weight_scale_cost - $this->paid_weight_scale_cost);
    }

    public function getPaidOtherChargesAttribute(): float
    {
        return (float) $this->workerPayoutItems()->where('charge_type', 'other')->sum('amount');
    }

    public function getDueOtherChargesAttribute(): float
    {
        return max(0, (float)$this->other_charges - $this->paid_other_charges);
    }

    public function getTotalChargesAttribute(): float
    {
        return (float)$this->labour_cost + (float)$this->delivery_charge + (float)$this->weight_scale_cost + (float)$this->other_charges;
    }

    public function getTotalChargesPaidAttribute(): float
    {
        return (float) $this->workerPayoutItems()->sum('amount');
    }

    public function getTotalChargesDueAttribute(): float
    {
        return max(0, $this->total_charges - $this->total_charges_paid);
    }

    public function syncChargesPayoutStatus(): string
    {
        $total = $this->total_charges;
        $paid = $this->total_charges_paid;

        if ($total <= 0 || $paid >= $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        $this->update(['charges_payout_status' => $status]);
        return $status;
    }
}