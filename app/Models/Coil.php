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
        'thickness',
        'width',
        'length',
        'piece_count',
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
        'piece_count'      => 'decimal:2',
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
     * Total initial intake weight (net weight preferred, gross weight fallback).
     */
    public function getInitialWeightAttribute(): float
    {
        $net = (float) $this->net_weight;
        if ($net > 0) return $net;
        $gross = (float) $this->gross_weight;
        return $gross > 0 ? $gross : (float) $this->remaining_weight;
    }

    /**
     * Initial weight per individual coil / piece in kg.
     */
    public function getUnitWeightAttribute(): float
    {
        $pieces = (float) ($this->piece_count > 0 ? $this->piece_count : 1);
        $initial = $this->initial_weight;
        return $pieces > 0 && $initial > 0 ? round($initial / $pieces, 2) : $initial;
    }

    /**
     * Remaining count of physical coils based on remaining weight.
     * E.g. 5 coils = 500kg, sold 150kg -> remaining 350kg = 3.5 coils.
     */
    public function getRemainingCoilsAttribute(): float
    {
        $initial = $this->initial_weight;
        $pieces = (float) ($this->piece_count > 0 ? $this->piece_count : 1);
        if ($initial <= 0) return 0;
        
        $remWeight = (float) $this->remaining_weight;
        $remCoils = ($remWeight / $initial) * $pieces;
        return round($remCoils, 2);
    }

    /**
     * Percentage of remaining weight relative to initial intake weight (0 - 100%).
     */
    public function getRemainingPercentageAttribute(): float
    {
        $initial = $this->initial_weight;
        if ($initial <= 0) return 0;
        
        $remWeight = (float) $this->remaining_weight;
        $pct = ($remWeight / $initial) * 100;
        return round(min(100, max(0, $pct)), 1);
    }

    /**
     * Clean string format for remaining coils (e.g. "3.5" or "5" instead of "5.00").
     */
    public function getFormattedRemainingCoilsAttribute(): string
    {
        $val = $this->remaining_coils;
        return (string) (floor($val) == $val ? (int)$val : (float)$val);
    }

    /**
     * Clean string format for initial piece count (e.g. "5" instead of "5.00").
     */
    public function getFormattedPieceCountAttribute(): string
    {
        $val = (float) ($this->piece_count > 0 ? $this->piece_count : 1);
        return (string) (floor($val) == $val ? (int)$val : (float)$val);
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
