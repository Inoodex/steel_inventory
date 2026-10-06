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

    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'lot_id');
    }

    /**
     * Get unique collection of all vendors involved in this lot's purchases
     */
    public function getVendorsListAttribute()
    {
        $vendors = $this->purchases->map(fn($p) => $p->vendor)->filter()->unique('id');
        if ($vendors->isNotEmpty()) {
            return $vendors;
        }
        return $this->vendor ? collect([$this->vendor]) : collect();
    }

    /**
     * Get comma-separated list of vendor names for this lot
     */
    public function getVendorNamesAttribute(): string
    {
        $names = $this->vendors_list->pluck('name')->toArray();
        if (empty($names)) {
            return $this->vendor?->name ?? 'N/A';
        }
        return implode(', ', $names);
    }

    /**
     * Get the primary warehouse for this lot from its purchases
     */
    public function getWarehouseAttribute()
    {
        return $this->purchases->first()?->warehouse;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTotalPurchasesCountAttribute(): int
    {
        return $this->purchases()->count();
    }

    public function getTotalCoilsCountAttribute(): int
    {
        return (int) $this->purchases()->sum('quantity');
    }

    public function getTotalWeightAttribute(): float
    {
        return (float) $this->purchases->sum(fn($p) => (float)($p->total_weight ?: $p->quantity));
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->purchases->sum('total_price');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->purchases->sum('payment');
    }

    public function getTotalDueAttribute(): float
    {
        return max(0, round($this->total_amount - $this->total_paid, 2));
    }

    public function getFinancialSummaryAttribute(): array
    {
        return [
            'total_purchases' => $this->total_purchases_count,
            'total_coils'     => $this->total_coils_count,
            'total_weight'    => $this->total_weight,
            'total_amount'    => $this->total_amount,
            'total_paid'      => $this->total_paid,
            'total_due'       => $this->total_due,
        ];
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
