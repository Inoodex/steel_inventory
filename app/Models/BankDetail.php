<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_name',
        'bank_name',
        'branch',
        'account_number',
        'account_type',
        'routing_number',
        'swift_code',
        'opening_balance',
        'current_balance',
        'currency',
        'is_default',
        'is_active',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    // Scope for active bank details
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for default bank detail
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Associated Chart of Account
     */
    public function chartOfAccount()
    {
        return $this->hasOne(ChartOfAccount::class, 'bank_detail_id');
    }

    /**
     * Get, create, or automatically synchronize the Chart of Account for this bank detail.
     */
    public function resolveChartOfAccount(): ChartOfAccount
    {
        $accountName = "{$this->bank_name} - {$this->account_name} ({$this->account_number})";
        $openingBalance = (float) ($this->opening_balance ?? 0.00);
        $isActive = (bool) ($this->is_active ?? true);

        $parent = ChartOfAccount::where('account_code', '1120')->first();
        $coa = $this->chartOfAccount ?: ChartOfAccount::where('bank_detail_id', $this->id)->first();
        if ($coa) {
            $coa->update([
                'account_name'    => $accountName,
                'opening_balance' => $openingBalance,
                'parent_id'       => $coa->parent_id ?: $parent?->id,
                'is_active'       => $isActive,
            ]);
            return $coa;
        }

        $baseCode = '1120-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
        $code = $baseCode;
        $counter = 1;
        while (ChartOfAccount::where('account_code', $code)->exists()) {
            $code = '1120-' . str_pad((string) ($this->id + $counter), 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return ChartOfAccount::create([
            'bank_detail_id'  => $this->id,
            'account_code'    => $code,
            'account_name'    => $accountName,
            'account_type'    => 'asset',
            'parent_id'       => $parent?->id,
            'level'           => $parent ? ($parent->level + 1) : 3,
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'is_active'       => $isActive,
            'is_system'       => false,
        ]);
    }

    /**
     * Calculate and sync the true running balance from Chart of Accounts or direct Transactions.
     */
    public function syncCurrentBalance(): float
    {
        $coa = $this->chartOfAccount ?: ChartOfAccount::where('bank_detail_id', $this->id)->first();

        // 1. If linked to General Ledger and journal transactions exist, use GAAP double-entry balance
        if ($coa && $coa->journalItems()->exists()) {
            $balance = (float) $coa->calculateBalance();
        } else {
            // 2. Direct transactional fallback: opening balance + received payments - disbursements
            $opening = (float) ($this->opening_balance ?? 0.00);
            $inflows = (float) Payment::where('bank_detail_id', $this->id)
                ->whereIn('payment_for', [1, 2]) // 1: Sale payment, 2: Customer advance
                ->where('amount', '>', 0)
                ->sum('amount');
            $vendorOutflows = (float) Payment::where('bank_detail_id', $this->id)
                ->where('payment_for', 3)
                ->where('amount', '>', 0)
                ->sum('amount');
            $refundOutflows = abs((float) Payment::where('bank_detail_id', $this->id)
                ->where('amount', '<', 0)
                ->sum('amount'));
            $outflows = $vendorOutflows + $refundOutflows;

            // Daily expenses paid via this bank
            $expenses = (float) DailyExpense::where('bank_detail_id', $this->id)->sum('amount');

            // Worker payouts paid via this bank
            $payouts = (float) WorkerPayout::where('bank_detail_id', $this->id)->sum('total_amount');

            $balance = $opening + $inflows - $outflows - $expenses - $payouts;
        }

        if ((float) $this->current_balance !== (float) $balance) {
            $this->forceFill(['current_balance' => $balance])->saveQuietly();
        }

        return $balance;
    }
}