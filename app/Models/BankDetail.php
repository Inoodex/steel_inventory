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
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean'
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
     * Get or automatically initialize Chart of Account for this bank detail.
     */
    public function resolveChartOfAccount(): ChartOfAccount
    {
        if ($this->chartOfAccount) {
            return $this->chartOfAccount;
        }

        $parent = ChartOfAccount::where('account_code', '1120')->first();
        $code = '1120-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);

        return ChartOfAccount::firstOrCreate(
            ['bank_detail_id' => $this->id],
            [
                'account_code' => $code,
                'account_name' => "{$this->bank_name} - {$this->account_name} ({$this->account_number})",
                'account_type' => 'asset',
                'parent_id' => $parent?->id,
                'level' => 3,
                'is_active' => true,
                'is_system' => false,
            ]
        );
    }
}