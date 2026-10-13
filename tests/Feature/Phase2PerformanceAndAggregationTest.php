<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase2PerformanceAndAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@perf.com',
            'phone'    => '01711222333',
            'password' => bcrypt('password'),
            'status'   => '1',
        ]);
        $this->admin->assignRole($adminRole);
    }

    public function test_batch_calculate_balances_matches_individual_account_balances(): void
    {
        $assetAccount = ChartOfAccount::create([
            'account_code'    => '1110',
            'account_name'    => 'Cash in Hand',
            'account_type'    => 'asset',
            'normal_balance'  => 'debit',
            'opening_balance' => 5000.00,
            'is_active'       => true,
        ]);

        $revenueAccount = ChartOfAccount::create([
            'account_code'    => '4110',
            'account_name'    => 'Sales Revenue',
            'account_type'    => 'revenue',
            'normal_balance'  => 'credit',
            'opening_balance' => 0.00,
            'is_active'       => true,
        ]);

        $journal = JournalEntry::create([
            'journal_no'     => 'JV-BATCH-001',
            'entry_date'     => date('Y-m-d'),
            'reference_type' => 'manual',
            'description'    => 'Test manual entry for batch verification',
            'total_debit'    => 2500.00,
            'total_credit'   => 2500.00,
            'status'         => 'posted',
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journal->id,
            'account_id'       => $assetAccount->id,
            'debit'            => 2500.00,
            'credit'           => 0.00,
        ]);

        JournalEntryItem::create([
            'journal_entry_id' => $journal->id,
            'account_id'       => $revenueAccount->id,
            'debit'            => 0.00,
            'credit'           => 2500.00,
        ]);

        $batchBalances = ChartOfAccount::batchCalculateBalances(date('Y-m-d'));

        $this->assertEquals(7500.00, $batchBalances[$assetAccount->id]);
        $this->assertEquals(2500.00, $batchBalances[$revenueAccount->id]);

        $this->assertEquals($assetAccount->calculateBalance(date('Y-m-d')), $batchBalances[$assetAccount->id]);
        $this->assertEquals($revenueAccount->calculateBalance(date('Y-m-d')), $batchBalances[$revenueAccount->id]);
    }

    public function test_dashboard_renders_with_grouped_monthly_and_yearly_queries(): void
    {
        $customer = Customer::create([
            'name'   => 'Perf Customer',
            'phone'  => '01811000999',
            'status' => 'active',
        ]);

        Sale::create([
            'customer_id'       => $customer->id,
            'order_no'          => 'SO-PERF-01',
            'total'             => 15000.00,
            'bill'              => 15000.00,
            'payble'            => 15000.00,
            'labour_cost'       => 400.00,
            'delivery_charge'   => 600.00,
            'weight_scale_cost' => 100.00,
            'other_charges'     => 50.00,
            'status'            => 'partial',
            'payment_status'    => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('monthlyRevenue');
        $response->assertViewHas('monthlyPurchases');
        $response->assertViewHas('yearlyRevenue');
        $response->assertViewHas('pendingWorkerCharges');
    }

    public function test_worker_payouts_index_renders_with_sql_aggregated_kpis(): void
    {
        $customer = Customer::create([
            'name'   => 'Worker Payout Customer',
            'phone'  => '01811000888',
            'status' => 'active',
        ]);

        Sale::create([
            'customer_id'      => $customer->id,
            'order_no'         => 'SO-WORKER-01',
            'total'            => 8000.00,
            'bill'             => 8000.00,
            'payble'           => 8000.00,
            'labour_cost'      => 500.00,
            'delivery_charge'  => 700.00,
            'status'           => 'partial',
            'payment_status'   => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('worker-payouts.index'));

        $response->assertStatus(200);
        $response->assertViewHas('totalCollectedLabour', 500.00);
        $response->assertViewHas('totalCollectedDelivery', 700.00);
    }

    public function test_financial_statements_render_successfully(): void
    {
        $responsePL = $this->actingAs($this->admin)->get(route('reports.profit-loss'));
        $responsePL->assertStatus(200);

        $responseBS = $this->actingAs($this->admin)->get(route('reports.balance-sheet'));
        $responseBS->assertStatus(200);
    }
}
