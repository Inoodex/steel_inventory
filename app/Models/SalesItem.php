<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'coil_id',
        'product_id',
        'lot_id',
        'thickness',
        'size',
        'size_type',
        'unit_price',
        'qty',
        'total_price',
        'warranty',
        'returned_qty',
        'purchase_price',
        'profit',
    ];

    public function product()
    {
        return $this->belongsTo(Coil::class, 'coil_id');
    }

    public function coil()
    {
        return $this->belongsTo(Coil::class, 'coil_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'order_id');
    }
}
