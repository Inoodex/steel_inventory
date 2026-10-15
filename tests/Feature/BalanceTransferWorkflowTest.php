<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChartOfAccount;
use App\Models\BankDetail;
use App\Models\JournalEntry;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BalanceTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ChartOfAccount $cashAccount;
    protected ChartOfAccount $bankAccount;
    protected BankDetail $bankDetail;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Finance Director',
            'email' => 'finance@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        // Active fiscal year
        FiscalYear::create([
            'year_name' => 'FY-2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'is_closed' => false,
        ]);

        // Chart of Accounts setup
        $assetRoot = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Assets',
            'account_type' => 'asset',
            'is_active' => true,
            'level' => 1,
        ]);

        $this->cashAccount = ChartOfAccount::create([
            'account_code' => '1110',
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'parent_id' => $assetRoot->id,
            'is_active' => true,
            'level' => 2,
            'opening_balance' => 50000,
            'current_balance' => 50000,
        ]);

        $bankParent = ChartOfAccount::create([
            'account_code' => '1120',
            'account_name' => 'Bank & Mobile Banking Accounts',
            'account_type' => 'asset',
            'parent_id' => $assetRoot->id,
            'is_active' => true,
            'level' => 2,
        ]);

        $this->bankDetail = BankDetail::create([
            'account_name' => 'Steel Corp Prime Current',
            'bank_name' => 'City Bank Ltd',
            'branch' => 'Principal Branch',
            'account_number' => '1102938475',
            'opening_balance' => 200000,
            'current_balance' => 200000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->bankAccount = ChartOfAccount::create([
            'account_code' => '1120-001',
            'account_name' => 'City Bank - Steel Corp Prime',
            'account_type' => 'asset',
            'parent_id' => $bankParent->id,
            'bank_detail_id' => $this->bankDetail->id,
            'is_active' => true,
            'level' => 3,
            'opening_balance' => 200000,
            'current_balance' => 200000,
        ]);
    }

    public function test_balance_transfer_index_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('balance-transfers.index'));
        $response->assertStatus(200);
        $response->assertSee('Balance & Fund Transfers');
        $response->assertSee('Cash in Hand');
        $response->assertSee('City Bank - Steel Corp Prime');
    }

    public function test_balance_transfer_create_page_renders_with_accounts(): void
    {
        $response = $this->actingAs($this->admin)->get(route('balance-transfers.create'));
        $response->assertStatus(200);
        $response->assertSee('New Balance / Fund Transfer');
        $response->assertSee('Cash in Hand');
        $response->assertSee('City Bank - Steel Corp Prime');
    }

    public function test_can_execute_balance_transfer_between_cash_and_bank(): void
    {
        $payload = [
            'transfer_date'   => '2026-10-15',
            'from_account_id' => $this->bankAccount->id,
            'to_account_id'   => $this->cashAccount->id,
            'amount'          => 25000,
            'reference_no'    => 'CHQ-99012',
            'notes'           => 'Cash withdrawal for petty office expenses',
        ];

        $response = $this->actingAs($this->admin)->post(route('balance-transfers.store'), $payload);
        
        $entry = JournalEntry::where('reference_type', 'contra')->latest()->first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('balance-transfers.show', $entry->id));

        $this->assertEquals(25000, (float) $entry->total_debit);
        $this->assertEquals(25000, (float) $entry->total_credit);
        $this->assertEquals('approved', $entry->status);

        // Verify Journal Items
        $this->assertCount(2, $entry->items);
        $debitItem = $entry->items->firstWhere('account_id', $this->cashAccount->id);
        $creditItem = $entry->items->firstWhere('account_id', $this->bankAccount->id);

        $this->assertEquals(25000, (float) $debitItem->debit);
        $this->assertEquals(0, (float) $debitItem->credit);

        $this->assertEquals(0, (float) $creditItem->debit);
        $this->assertEquals(25000, (float) $creditItem->credit);

        // Verify Balances:
        // Cash should have increased: 50,000 + 25,000 = 75,000
        $this->assertEquals(75000, (float) $this->cashAccount->calculateBalance());

        // Bank should have decreased: 200,000 - 25,000 = 175,000
        $this->assertEquals(175000, (float) $this->bankAccount->calculateBalance());

        // Bank detail current_balance synchronized
        $this->bankDetail->refresh();
        $this->assertEquals(175000, (float) $this->bankDetail->current_balance);
    }

    public function test_balance_transfer_validation_prevents_same_account_transfer(): void
    {
        $payload = [
            'transfer_date'   => '2026-10-15',
            'from_account_id' => $this->cashAccount->id,
            'to_account_id'   => $this->cashAccount->id, // Same account!
            'amount'          => 5000,
        ];

        $response = $this->actingAs($this->admin)->post(route('balance-transfers.store'), $payload);
        $response->assertSessionHasErrors(['to_account_id']);
    }

    public function test_can_view_and_reverse_balance_transfer(): void
    {
        // Execute a transfer first
        $res = $this->actingAs($this->admin)->post(route('balance-transfers.store'), [
            'transfer_date'   => '2026-10-15',
            'from_account_id' => $this->cashAccount->id,
            'to_account_id'   => $this->bankAccount->id,
            'amount'          => 10000,
            'notes'           => 'Cash deposit into bank',
        ]);
        $res->assertSessionHasNoErrors();

        $entry = JournalEntry::where('reference_type', 'contra')->latest()->first();

        // 1. Show page
        $showResponse = $this->actingAs($this->admin)->get(route('balance-transfers.show', $entry->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($entry->journal_no);
        $showResponse->assertSee('10,000.00');

        // 2. PDF voucher download
        $pdfResponse = $this->actingAs($this->admin)->get(route('balance-transfers.pdf', $entry->id));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));

        // 3. Reverse the transfer
        $revResponse = $this->actingAs($this->admin)->post(route('balance-transfers.reverse', $entry->id), [
            'reason' => 'Transfer cancelled by mistake',
        ]);

        $revResponse->assertRedirect(route('balance-transfers.show', $entry->id));

        $entry->refresh();
        $this->assertEquals('reversed', $entry->status);

        // Balances should be back to original
        $this->assertEquals(50000, (float) $this->cashAccount->calculateBalance());
        $this->assertEquals(200000, (float) $this->bankAccount->calculateBalance());
    }

    public function test_balance_transfer_fails_when_source_balance_is_insufficient(): void
    {
        // cashAccount balance is 50,000. Attempt to transfer 75,000
        $payload = [
            'transfer_date'   => '2026-10-15',
            'from_account_id' => $this->cashAccount->id,
            'to_account_id'   => $this->bankAccount->id,
            'amount'          => 75000,
        ];

        $response = $this->actingAs($this->admin)->from(route('balance-transfers.create'))->post(route('balance-transfers.store'), $payload);
        $response->assertRedirect(route('balance-transfers.create'));
        $response->assertSessionHas('error');

        // Verify no contra entry was created
        $this->assertEquals(0, JournalEntry::where('reference_type', 'contra')->count());

        // Verify balances remained unchanged
        $this->assertEquals(50000, (float) $this->cashAccount->calculateBalance());
        $this->assertEquals(200000, (float) $this->bankAccount->calculateBalance());
    }
}
