<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Coil extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'coils';

    protected $fillable = [
        'coil_number',
        'purchase_id',
        'lot_id',
        'vendor_id',
        'warehouse_id',
        'product_id',
        'steel_type',
        'thickness',
        'width',
        'length',
        'gross_weight',
        'tare_weight',
        'net_weight',
        'remaining_weight',
        'rate_per_ton',
        'total_price',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gross_weight'     => 'decimal:3',
        'tare_weight'      => 'decimal:3',
        'net_weight'       => 'decimal:3',
        'remaining_weight' => 'decimal:3',
        'rate_per_ton'     => 'decimal:2',
        'total_price'      => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNameAttribute(): string
    {
        return 'Coil #' . ($this->coil_number ?? '');
    }

    /**
     * Generate unique sequential Coil tag
     */
    public static function generateCoilNumber(): string
    {
        $prefix = 'COIL-' . date('Ymd') . '-';
        $count = self::whereDate('created_at', date('Y-m-d'))->withTrashed()->count() + 1;
        
        do {
            $code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (self::where('coil_number', $code)->exists());

        return $code;
    }
}
