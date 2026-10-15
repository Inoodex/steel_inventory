<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryItem extends Model
{
    use HasFactory;

    protected $table = 'journal_entry_items';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    // Parent journal entry voucher
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    // Chart of account linked to this debit/credit
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    protected static function booted()
    {
        static::saved(function ($item) {
            if ($item->account && $item->account->bank_detail_id) {
                $item->account->bankDetail?->syncCurrentBalance();
            }
        });

        static::deleted(function ($item) {
            if ($item->account && $item->account->bank_detail_id) {
                $item->account->bankDetail?->syncCurrentBalance();
            }
        });
    }
}
