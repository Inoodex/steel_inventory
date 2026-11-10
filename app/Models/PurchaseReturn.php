<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'return_no',
        'return_date',
        'vendor_id',
        'purchase_id',
        'warehouse_id',
        'coil_id',
        'lot_id',
        'return_weight',
        'return_qty',
        'unit_price',
        'total_refund_amount',
        'refund_type',
        'payment_method',
        'bank_detail_id',
        'transaction_ref',
        'reason',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'return_weight' => 'decimal:3',
        'return_qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_refund_amount' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function coil()
    {
        return $this->belongsTo(Coil::class, 'coil_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function bankDetail()
    {
        return $this->belongsTo(BankDetail::class, 'bank_detail_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReturnNo(): string
    {
        $today = date('Ymd');
        $prefix = "PR-{$today}-";
        $lastRecord = self::where('return_no', 'like', "{$prefix}%")->latest('id')->first();
        if ($lastRecord && preg_match('/-(\d+)$/', $lastRecord->return_no, $matches)) {
            $seq = intval($matches[1]) + 1;
        } else {
            $seq = 1;
        }
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}

