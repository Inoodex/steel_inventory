<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "customers";

    protected $guarded = [];

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

    public function getOutstandingBalanceAttribute(): float
    {
        return $this->total_due;
    }
}
