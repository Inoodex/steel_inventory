<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AccountingDoubleEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Chief Accountant',
            'email' => 'accounts@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);
    }

    public function test_double_entry_balanced_journal(): void
    {
        $cashCoa = ChartOfAccount::create([
            'account_code' => '1110',
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'is_active' => true,
        ]);

        $salesCoa = ChartOfAccount::create([
            'account_code' => '4110',
            'account_name' => 'Steel Sales Revenue',
            'account_type' => 'revenue',
            'is_active' => true,
        ]);

        $journal = JournalEntry::create([
            'journal_no' => 'JV-202610-001',
            'entry_date' => now()->toDateString(),
            'description' => 'Daily Cash Sales Entry',
            'total_debit' => 50000.00,
            'total_credit' => 50000.00,
            'status' => 'posted',
            'created_by' => $this->admin->id,
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $cashCoa->id,
            'debit' => 50000.00,
            'credit' => 0.00,
            'description' => 'Cash collected',
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $salesCoa->id,
            'debit' => 0.00,
            'credit' => 50000.00,
            'description' => 'Sales revenue recognition',
        ]);

        $this->assertEquals($journal->total_debit, $journal->total_credit);
        $this->assertEquals(50000.00, $journal->items()->sum('debit'));
        $this->assertEquals(50000.00, $journal->items()->sum('credit'));
    }
}
