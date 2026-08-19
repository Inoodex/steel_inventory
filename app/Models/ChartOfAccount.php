<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_id',
        'level',
        'bank_detail_id',
        'is_active',
        'is_system',
        'opening_balance',
        'current_balance',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'level' => 'integer',
    ];

    // Accessors for short aliases
    public function getCodeAttribute()
    {
        return $this->attributes['account_code'] ?? null;
    }

    public function getNameAttribute()
    {
        return $this->attributes['account_name'] ?? null;
    }

    public function getTypeAttribute()
    {
        return $this->attributes['account_type'] ?? null;
    }

    // Parent account
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    // Child accounts (sub-accounts)
    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('account_code');
    }

    // All recursive descendants
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // Linked bank detail profile
    public function bankDetail()
    {
        return $this->belongsTo(BankDetail::class, 'bank_detail_id');
    }

    // Linked journal entry items
    public function journalItems()
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Determines whether this account type normally increases with Debit.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->account_type, ['asset', 'expense']);
    }

    /**
     * Calculate running balance up to an optional date, with optional from-date for period movements.
     */
    public function calculateBalance(?string $asOfDate = null, ?string $fromDate = null): float
    {
        $query = $this->journalItems()
            ->whereHas('journalEntry', function ($q) use ($asOfDate, $fromDate) {
                $q->whereIn('status', ['posted', 'approved']);
                if ($asOfDate) {
                    $q->where('entry_date', '<=', $asOfDate);
                }
                if ($fromDate) {
                    $q->where('entry_date', '>=', $fromDate);
                }
            });

        $totalDebit = (float) (clone $query)->sum('debit');
        $totalCredit = (float) (clone $query)->sum('credit');

        // Opening balance applies only to Balance Sheet accounts (Asset, Liability, Equity)
        // when computing cumulative balance (i.e. when $fromDate is null).
        // For period-based statements (like P&L or period movements), opening balance is not included.
        $opening = 0.0;
        if (!$fromDate && in_array($this->account_type, ['asset', 'liability', 'equity'])) {
            $opening = (float) $this->opening_balance;
        }

        if ($this->isDebitNormal()) {
            return $opening + ($totalDebit - $totalCredit);
        } else {
            return $opening + ($totalCredit - $totalDebit);
        }
    }
}
