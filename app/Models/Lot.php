<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Lot extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'lot_number',
        'vendor_id',
        'lot_date',
        'total_quantity',
        'total_amount',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lot_date'       => 'date',
        'total_quantity' => 'decimal:2',
        'total_amount'   => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
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

    /**
     * Generate unique default Lot Number if custom number is not provided
     */
    public static function generateLotNumber(): string
    {
        $prefix = 'LOT-' . date('Ymd') . '-';
        $count = self::whereDate('created_at', date('Y-m-d'))->withTrashed()->count() + 1;
        
        do {
            $code = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (self::where('lot_number', $code)->exists());

        return $code;
    }
}
