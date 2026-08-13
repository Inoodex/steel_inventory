<?php

namespace App\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Purchase extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Optional: define table name if not following conventions
    protected $table = 'purchases';

    // Mass assignable fields
    protected $fillable = [
        'product_id',
        'lot_id',
        'vendor_id',
        'warehouse_id',
        'thickness',
        'size',
        'size_type',
        'unit_weight',
        'total_weight',
        'quantity',
        'unit_price',
        'sub_price',
        'total_price',
        'payment',
        'due',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'unit_weight' => 'decimal:3',
        'total_weight' => 'decimal:3',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'sub_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'payment' => 'decimal:2',
        'due' => 'decimal:2',
    ];

    // Relationships
    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function coils()
    {
        return $this->hasMany(Coil::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
