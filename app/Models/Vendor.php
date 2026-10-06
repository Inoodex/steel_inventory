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
}
