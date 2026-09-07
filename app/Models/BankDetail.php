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

        $coa = $this->chartOfAccount ?: ChartOfAccount::where('bank_detail_id', $this->id)->first();

        if ($coa) {
            $coa->update([
                'account_name'    => $accountName,
                'opening_balance' => $openingBalance,
                'is_active'       => $isActive,
            ]);
            return $coa;
        }

        $parent = ChartOfAccount::where('account_code', '1120')->first();
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
            'level'           => 3,
            'opening_balance' => $openingBalance,
            'current_balance' => 0.00,
            'is_active'       => $isActive,
            'is_system'       => false,
        ]);
    }
}