<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'type',
        'code',
        'location',
        'contact_person',
        'contact_phone',
        'capacity_ton',
        'status',
        'notes',
    ];

    public function scopeShops($query)
    {
        return $query->where('type', 'shop');
    }

    public function scopeWarehouses($query)
    {
        return $query->where('type', '!=', 'shop')->orWhereNull('type');
    }

    public function isShop(): bool
    {
        return $this->type === 'shop';
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function coils()
    {
        return $this->hasMany(Coil::class);
    }
}
