<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vendor_id',
        'sale_id',
        'purchase_id',
        'payment_for',
        'payment_method',
        'bank_detail_id',
        'transaction_ref',
        'amount',
        'remarks',
        'notes',
        'note',
        'status',
        'created_by',
        'updated_by'
    ];

    public function getNoteAttribute()
    {
        return $this->attributes['notes'] ?? $this->attributes['remarks'] ?? null;
    }

    public function setNoteAttribute($value)
    {
        $this->attributes['notes'] = $value;
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function bankDetail()
    {
        return $this->belongsTo(BankDetail::class, 'bank_detail_id');
    }
}
