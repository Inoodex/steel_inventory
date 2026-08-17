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
        'customer_id',
        'qty',
        'total',
        'payble',
        'bill',
        'advanced_payment',
        'due_payment',
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
    ];

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
}