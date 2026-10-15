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
                    $q->where('entry_date', '<=', strlen($asOfDate) === 10 ? $asOfDate . ' 23:59:59' : $asOfDate);
                }
                if ($fromDate) {
                    $q->where('entry_date', '>=', strlen($fromDate) === 10 ? $fromDate . ' 00:00:00' : $fromDate);
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

    /**
     * High-performance batch calculation of running balances for all accounts in 1-2 aggregated queries.
     * Returns an array keyed by account_id => float balance.
     */
    public static function batchCalculateBalances(?string $asOfDate = null, ?string $fromDate = null): array
    {
        $query = \Illuminate\Support\Facades\DB::table('journal_entry_items')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->whereIn('journal_entries.status', ['posted', 'approved']);

        if ($asOfDate) {
            $query->where('journal_entries.entry_date', '<=', strlen($asOfDate) === 10 ? $asOfDate . ' 23:59:59' : $asOfDate);
        }
        if ($fromDate) {
            $query->where('journal_entries.entry_date', '>=', strlen($fromDate) === 10 ? $fromDate . ' 00:00:00' : $fromDate);
        }

        $sums = $query->select(
            'journal_entry_items.account_id',
            \Illuminate\Support\Facades\DB::raw('SUM(journal_entry_items.debit) as total_debit'),
            \Illuminate\Support\Facades\DB::raw('SUM(journal_entry_items.credit) as total_credit')
        )->groupBy('journal_entry_items.account_id')->get()->keyBy('account_id');

        $accounts = self::all();
        $balances = [];

        foreach ($accounts as $account) {
            $item = $sums->get($account->id);
            $totalDebit = $item ? (float)$item->total_debit : 0.0;
            $totalCredit = $item ? (float)$item->total_credit : 0.0;

            $opening = 0.0;
            if (!$fromDate && in_array($account->account_type, ['asset', 'liability', 'equity'])) {
                $opening = (float) $account->opening_balance;
            }

            if ($account->isDebitNormal()) {
                $balances[$account->id] = $opening + ($totalDebit - $totalCredit);
            } else {
                $balances[$account->id] = $opening + ($totalCredit - $totalDebit);
            }
        }

        return $balances;
    }

    /**
     * Ensure standard 5-class system chart of accounts and active fiscal year exist.
     * Self-healing, idempotent, and non-destructive to existing balances.
     */
    public static function ensureSystemAccounts(): void
    {
        // 1. Ensure active fiscal year
        if (function_exists('getActiveFiscalYear')) {
            getActiveFiscalYear();
        }

        // 2. Normalize legacy Hand Cash if with code '0'
        $zeroAccount = self::where('account_code', '0')->first();
        if ($zeroAccount && !self::where('account_code', '1110')->exists()) {
            $zeroAccount->update([
                'account_code' => '1110',
                'account_name' => 'Cash in Hand',
                'account_type' => 'asset',
                'level'        => 3,
                'is_system'    => true,
            ]);
        }

        // 3. Define Clean Minimal 2-Tier Tree (5 Root Classes + Direct Operational Accounts)
        $tree = [
            // ASSETS (1000)
            [
                'code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'level' => 1, 'is_system' => true,
                'children' => [
                    ['code' => '1110', 'name' => 'Cash in Hand', 'type' => 'asset', 'level' => 2, 'is_system' => true],
                    ['code' => '1120', 'name' => 'Bank & Mobile Banking Accounts', 'type' => 'asset', 'level' => 2, 'is_system' => true],
                    ['code' => '1130', 'name' => 'Accounts Receivable (Customers)', 'type' => 'asset', 'level' => 2, 'is_system' => true],
                    ['code' => '1140', 'name' => 'Steel Inventory Asset', 'type' => 'asset', 'level' => 2, 'is_system' => true],
                    ['code' => '1150', 'name' => 'Advance to Vendors & Loans', 'type' => 'asset', 'level' => 2, 'is_system' => true],
                ]
            ],
            // LIABILITIES (2000)
            [
                'code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'level' => 1, 'is_system' => true,
                'children' => [
                    ['code' => '2110', 'name' => 'Accounts Payable (Vendors)', 'type' => 'liability', 'level' => 2, 'is_system' => true],
                    ['code' => '2120', 'name' => 'Salary & Wages Payable', 'type' => 'liability', 'level' => 2, 'is_system' => true],
                    ['code' => '2130', 'name' => 'Customer Advances / Deferred Revenue', 'type' => 'liability', 'level' => 2, 'is_system' => true],
                    ['code' => '2140', 'name' => 'Pass-Through Extra Charges Payable', 'type' => 'liability', 'level' => 2, 'is_system' => true],
                ]
            ],
            // EQUITY (3000)
            [
                'code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'level' => 1, 'is_system' => true,
                'children' => [
                    ['code' => '3100', 'name' => 'Owner Capital', 'type' => 'equity', 'level' => 2, 'is_system' => true],
                    ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'level' => 2, 'is_system' => true],
                ]
            ],
            // REVENUE (4000)
            [
                'code' => '4000', 'name' => 'Revenue / Income', 'type' => 'revenue', 'level' => 1, 'is_system' => true,
                'children' => [
                    ['code' => '4110', 'name' => 'Steel Sales Revenue', 'type' => 'revenue', 'level' => 2, 'is_system' => true],
                ]
            ],
            // EXPENSES (5000)
            [
                'code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'level' => 1, 'is_system' => true,
                'children' => [
                    ['code' => '5110', 'name' => 'Purchase Cost / Raw Steel Expense', 'type' => 'expense', 'level' => 2, 'is_system' => true],
                    ['code' => '5120', 'name' => 'Sales Returns & Allowances', 'type' => 'expense', 'level' => 2, 'is_system' => true],
                    ['code' => '5210', 'name' => 'Employee Salary Expense', 'type' => 'expense', 'level' => 2, 'is_system' => true],
                    ['code' => '5220', 'name' => 'Travel & Daily Allowance (TA/DA)', 'type' => 'expense', 'level' => 2, 'is_system' => true],
                    ['code' => '5230', 'name' => 'Office Daily Expenses', 'type' => 'expense', 'level' => 2, 'is_system' => true],
                ]
            ],
        ];

        $insertNode = function ($node, $parentId = null) use (&$insertNode) {
            $account = self::updateOrCreate(
                ['account_code' => $node['code']],
                [
                    'account_name' => $node['name'],
                    'account_type' => $node['type'],
                    'parent_id'    => $parentId,
                    'level'        => $node['level'],
                    'is_active'    => true,
                    'is_system'    => $node['is_system'] ?? false,
                ]
            );

            if (!empty($node['children'])) {
                foreach ($node['children'] as $child) {
                    $insertNode($child, $account->id);
                }
            }

            return $account;
        };

        foreach ($tree as $rootNode) {
            $insertNode($rootNode);
        }

        // 4. Safely prune unused legacy intermediate group accounts if no journal entries exist
        self::whereIn('account_code', ['1100', '2100', '4100', '5100', '5200'])
            ->doesntHave('journalItems')
            ->delete();

        // 5. Connect any existing bank detail accounts under Bank Accounts (1120)
        $bankParent = self::where('account_code', '1120')->first();
        if ($bankParent) {
            self::where('account_code', 'like', '1120-%')
                ->update(['parent_id' => $bankParent->id, 'level' => 3]);

            // Ensure all BankDetail records have a linked ChartOfAccount and synced balance
            $banks = BankDetail::all();
            foreach ($banks as $bank) {
                if (!$bank->chartOfAccount) {
                    $bank->resolveChartOfAccount();
                }
                if (method_exists($bank, 'syncCurrentBalance')) {
                    $bank->syncCurrentBalance();
                }
            }
        }
    }
}
