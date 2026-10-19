<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\BankDetail;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdvanceRefundWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Vendor $vendor;
    private Warehouse $warehouse;
    private BankDetail $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name'     => 'Refund Test Admin',
            'email'    => 'admin@refundtest.com',
            'password' => Hash::make('secret123'),
            'status'   => '1',
        ]);
        $this->admin->assignRole($role);

        // Chart of accounts setup
        $assetRoot = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Assets',
            'account_type' => 'asset',
            'is_active'    => true,
            'level'        => 1,
        ]);

        ChartOfAccount::create([
            'account_code' => '1110',
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'parent_id'    => $assetRoot->id,
            'is_active'    => true,
            'level'        => 2,
        ]);

        ChartOfAccount::create([
            'account_code' => '1120',
            'account_name' => 'Bank Accounts',
            'account_type' => 'asset',
            'parent_id'    => $assetRoot->id,
            'is_active'    => true,
            'level'        => 2,
        ]);

        ChartOfAccount::create([
            'account_code' => '1130',
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'parent_id'    => $assetRoot->id,
            'is_active'    => true,
            'level'        => 2,
        ]);

        $liabRoot = ChartOfAccount::create([
            'account_code' => '2000',
            'account_name' => 'Liabilities',
            'account_type' => 'liability',
            'is_active'    => true,
            'level'        => 1,
        ]);

        ChartOfAccount::create([
            'account_code' => '2110',
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'parent_id'    => $liabRoot->id,
            'is_active'    => true,
            'level'        => 2,
        ]);

        $this->bank = BankDetail::create([
            'account_name'    => 'Main Operations',
            'bank_name'       => 'City Bank',
            'branch'          => 'Gulshan',
            'account_number'  => 'CB-998877',
            'account_type'    => 'current',
            'opening_balance' => 20000.00,
            'current_balance' => 20000.00,
            'is_active'       => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name'     => 'Dhaka Central Yard',
            'location' => 'Tejgaon',
            'capacity' => 50000.00,
            'status'   => 'active',
        ]);

        $this->customer = Customer::create([
            'name'            => 'Advance Client Ltd',
            'company'         => 'Advance Corp',
            'phone'           => '01799887766',
            'email'           => 'client@advance.com',
            'address'         => 'Gulshan, Dhaka',
            'opening_balance' => 0.00,
            'status'          => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name'            => 'Apex Steel Mills',
            'company'         => 'Apex Group',
            'phone'           => '01899887766',
            'email'           => 'supply@apex.com',
            'address'         => 'Chittagong',
            'opening_balance' => 0.00,
            'status'          => '1',
        ]);
    }

    public function test_customer_advance_refund_decreases_advance_credit_and_updates_bank_balance(): void
    {
        // 1. Customer deposits 10,000 advance into Bank
        $response = $this->actingAs($this->admin)->post(route('customer-advances.store'), [
            'customer_id'    => $this->customer->id,
            'amount'         => 10000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'payment_date'   => '2026-10-19',
            'remarks'        => 'Advance deposit for steel procurement',
        ]);
        $response->assertSessionHasNoErrors();

        $this->bank->refresh();
        $this->customer->refresh();
        $this->assertEquals(10000.00, $this->customer->calculateFinancials()['advance_credit']);
        $this->assertEquals(30000.00, (float) $this->bank->current_balance);

        // 2. Customer requests refund of 4,000 back to their bank
        $refundResponse = $this->actingAs($this->admin)->post(route('customer-advances.refund'), [
            'customer_id'    => $this->customer->id,
            'amount'         => 4000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'refund_date'    => '2026-10-19',
            'transaction_ref'=> 'REF-TRX-001',
            'remarks'        => 'Refunding 4000 unallocated advance credit',
        ]);

        $refundResponse->assertSessionHasNoErrors();
        $refundResponse->assertRedirect();

        // 3. Verify Customer Financials
        $this->customer->refresh();
        $financials = $this->customer->calculateFinancials();
        $this->assertEquals(6000.00, $financials['advance_credit']);
        $this->assertEquals(0.00, $financials['effective_due']);

        // 4. Verify Bank Balance dropped by 4,000 (from 30,000 to 26,000)
        $this->bank->refresh();
        $this->assertEquals(26000.00, (float) $this->bank->current_balance);

        // 5. Verify Payment record created with negative amount
        $refundPayment = Payment::where('customer_id', $this->customer->id)
            ->where('amount', -4000.00)
            ->where('payment_for', 2)
            ->whereNull('sale_id')
            ->first();
        $this->assertNotNull($refundPayment);

        // 6. Verify Journal Entry
        $je = JournalEntry::where('reference_type', 'customer_advance_refund')
            ->where('reference_id', $refundPayment->id)
            ->first();
        $this->assertNotNull($je);
        $arItem = $je->items->firstWhere('account_id', ChartOfAccount::where('account_code', '1130')->first()->id);
        $this->assertNotNull($arItem);
        $this->assertEquals(4000.00, (float) $arItem->debit);
    }

    public function test_customer_advance_refund_fails_if_exceeds_active_advance_credit(): void
    {
        // Customer has advance credit 3,000
        Payment::create([
            'customer_id'    => $this->customer->id,
            'sale_id'        => null,
            'payment_for'    => 2,
            'amount'         => 3000.00,
            'payment_method' => 'cash',
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        $this->assertEquals(3000.00, $this->customer->calculateFinancials()['advance_credit']);

        // Attempt to refund 5,000
        $response = $this->actingAs($this->admin)->post(route('customer-advances.refund'), [
            'customer_id'    => $this->customer->id,
            'amount'         => 5000.00,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->customer->refresh();
        $this->assertEquals(3000.00, $this->customer->calculateFinancials()['advance_credit']);
    }

    public function test_vendor_advance_return_decreases_advance_balance_and_increases_bank_balance(): void
    {
        // 1. Pay advance of 12,000 to Vendor from Bank
        $response = $this->actingAs($this->admin)->post(route('vendor-advances.store'), [
            'vendor_id'      => $this->vendor->id,
            'amount'         => 12000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'payment_date'   => '2026-10-19',
            'remarks'        => 'Advance prepayment for raw billet order',
        ]);
        $response->assertSessionHasNoErrors();

        $this->bank->refresh();
        $this->vendor->refresh();
        $this->assertEquals(12000.00, $this->vendor->calculateFinancials()['advance_balance']);
        $this->assertEquals(8000.00, (float) $this->bank->current_balance); // 20,000 - 12,000

        // 2. Vendor returns 5,000 back into Bank
        $returnResponse = $this->actingAs($this->admin)->post(route('vendor-advances.refund'), [
            'vendor_id'      => $this->vendor->id,
            'amount'         => 5000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'refund_date'    => '2026-10-19',
            'transaction_ref'=> 'VEN-RET-999',
            'remarks'        => 'Supplier returned unused advance prepayment',
        ]);

        $returnResponse->assertSessionHasNoErrors();
        $returnResponse->assertRedirect();

        // 3. Verify Vendor Financials
        $this->vendor->refresh();
        $financials = $this->vendor->calculateFinancials();
        $this->assertEquals(7000.00, $financials['advance_balance']); // 12,000 - 5,000 = 7,000
        $this->assertEquals(0.00, $financials['payable_due']);

        // 4. Verify Bank Balance increased by 5,000 (from 8,000 to 13,000)
        $this->bank->refresh();
        $this->assertEquals(13000.00, (float) $this->bank->current_balance);

        // 5. Verify Payment record created with negative amount
        $returnPayment = Payment::where('vendor_id', $this->vendor->id)
            ->where('amount', -5000.00)
            ->where('payment_for', 3)
            ->whereNull('purchase_id')
            ->first();
        $this->assertNotNull($returnPayment);

        // 6. Verify Journal Entry
        $je = JournalEntry::where('reference_type', 'vendor_advance_refund')
            ->where('reference_id', $returnPayment->id)
            ->first();
        $this->assertNotNull($je);
        $apItem = $je->items->firstWhere('account_id', ChartOfAccount::where('account_code', '2110')->first()->id);
        $this->assertNotNull($apItem);
        $this->assertEquals(5000.00, (float) $apItem->credit);
    }

    public function test_vendor_advance_return_fails_if_exceeds_active_advance_balance(): void
    {
        // Vendor has advance balance 4,000
        Payment::create([
            'vendor_id'      => $this->vendor->id,
            'purchase_id'    => null,
            'payment_for'    => 3,
            'amount'         => 4000.00,
            'payment_method' => 'cash',
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        $this->assertEquals(4000.00, $this->vendor->calculateFinancials()['advance_balance']);

        // Attempt return of 6,000
        $response = $this->actingAs($this->admin)->post(route('vendor-advances.refund'), [
            'vendor_id'      => $this->vendor->id,
            'amount'         => 6000.00,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->vendor->refresh();
        $this->assertEquals(4000.00, $this->vendor->calculateFinancials()['advance_balance']);
    }

    public function test_refund_payments_are_not_consumed_by_reconcile_unallocated_payments(): void
    {
        // Customer deposits 10,000 advance
        $deposit = Payment::create([
            'customer_id'    => $this->customer->id,
            'sale_id'        => null,
            'payment_for'    => 2,
            'amount'         => 10000.00,
            'payment_method' => 'cash',
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        // Customer receives 3,000 advance refund
        $refund = Payment::create([
            'customer_id'    => $this->customer->id,
            'sale_id'        => null,
            'payment_for'    => 2,
            'amount'         => -3000.00,
            'payment_method' => 'cash',
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        $this->assertEquals(7000.00, $this->customer->calculateFinancials()['advance_credit']);

        // Create a new sale with 4,000 due
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'ORD-REFUND-001',
            'date'             => '2026-10-19',
            'total'            => 4000.00,
            'payble'           => 4000.00,
            'advanced_payment' => 0.00,
            'due_payment'      => 4000.00,
            'status'           => 'credit',
            'created_by'       => $this->admin->id,
        ]);

        // Run reconciliation
        Customer::reconcileUnallocatedPayments($this->customer->id);

        $sale->refresh();
        $refund->refresh();

        // The sale should be paid off using 4,000 from the 10,000 deposit
        $this->assertEquals('paid', $sale->status);
        $this->assertEquals(0.00, (float) $sale->due_payment);
        $this->assertEquals(4000.00, (float) $sale->advanced_payment);

        // The refund payment record MUST remain untouched
        $this->assertEquals(-3000.00, (float) $refund->amount);
        $this->assertNull($refund->sale_id);

        // Net advance credit should now be 7,000 - 4,000 = 3,000
        $this->assertEquals(3000.00, $this->customer->calculateFinancials()['advance_credit']);
    }
}
