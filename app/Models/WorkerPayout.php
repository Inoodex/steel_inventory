<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_no',
        'payout_date',
        'charge_type',
        'recipient_name',
        'recipient_phone',
        'total_amount',
        'payment_method',
        'bank_detail_id',
        'payment_account_id',
        'journal_entry_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public static function generatePayoutNo($date = null): string
    {
        $dateStr = $date ? \Carbon\Carbon::parse($date)->format('Ymd') : date('Ymd');
        $prefix = "WPO-{$dateStr}-";
        $latest = self::where('payout_no', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $lastNum = (int) substr($latest->payout_no, strlen($prefix));
            $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '0001';
        }

        return "{$prefix}{$nextNum}";
    }

    public function items()
    {
        return $this->hasMany(WorkerPayoutItem::class, 'worker_payout_id');
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'worker_payout_items', 'worker_payout_id', 'sale_id')
            ->distinct();
    }

    public function paymentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }

    public function bankDetail()
    {
        return $this->belongsTo(BankDetail::class, 'bank_detail_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
