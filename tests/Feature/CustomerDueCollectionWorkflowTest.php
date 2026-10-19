<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\BankDetail;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerDueCollectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Warehouse $warehouse;
    private BankDetail $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name'     => 'Due Test Admin',
            'email'    => 'admin@duetest.com',
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

        $liabilityRoot = ChartOfAccount::create([
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
            'parent_id'    => $liabilityRoot->id,
            'is_active'    => true,
            'level'        => 2,
        ]);

        $this->bank = BankDetail::create([
            'bank_name'       => 'Eastern Bank PLC',
            'account_name'    => 'Steel Inventory Operations',
            'account_number'  => 'EBL-1029384756',
            'account_type'    => 'Current',
            'opening_balance' => 100000.00,
            'current_balance' => 100000.00,
            'is_active'       => true,
            'is_default'      => true,
        ]);

        $this->warehouse = Warehouse::create([
            'name'     => 'Central Warehouse',
            'location' => 'Dhaka',
            'status'   => 'active',
        ]);

        $this->customer = Customer::create([
            'name'            => 'Haji Steel Traders',
            'phone'           => '01700998877',
            'opening_balance' => 5000.00,
            'status'          => 'active',
        ]);
    }

    public function test_customer_with_dues_appears_on_due_list(): void
    {
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'SO-DUE-001',
            'total'            => 45000.00,
            'bill'             => 45000.00,
            'payble'           => 45000.00,
            'advanced_payment' => 15000.00,
            'due_payment'      => 30000.00,
            'status'           => 'partial',
        ]);

        $response = $this->actingAs($this->admin)->get(route('due-payments.index'));
        $response->assertStatus(200);
        $response->assertSee('Haji Steel Traders');
        $response->assertSee('SO-DUE-001');
        // Opening due = 5000, Sales due = 30000, Total = 35000
        $response->assertSee('35,000.00');
    }

    public function test_collecting_due_clears_customer_and_sales_from_due_list(): void
    {
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'SO-DUE-002',
            'total'            => 45000.00,
            'bill'             => 45000.00,
            'payble'           => 45000.00,
            'advanced_payment' => 15000.00,
            'due_payment'      => 30000.00,
            'status'           => 'partial',
        ]);

        // Total due is 5,000 opening + 30,000 sales = 35,000
        // Collect full due 35,000
        $response = $this->actingAs($this->admin)->post(route('customer.due.pay'), [
            'customer_id'    => $this->customer->id,
            'amount'         => 35000.00,
            'payment_type'   => 'auto_due_settlement',
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'payment_date'   => now()->toDateString(),
            'remarks'        => 'Full due settlement',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Check customer opening balance settled to 0
        $this->customer->refresh();
        $this->assertEquals(0.00, (float)$this->customer->opening_balance);

        // 2. Check sale is fully paid
        $sale->refresh();
        $this->assertEquals(45000.00, (float)$sale->advanced_payment);
        $this->assertEquals(0.00, (float)$sale->due_payment);
        $this->assertEquals('paid', $sale->status);

        // 3. Check customer total due is now 0
        $this->assertEquals(0.00, $this->customer->total_due);

        // 4. Verify due-payments page shows no dues outstanding
        $listResponse = $this->actingAs($this->admin)->get(route('due-payments.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertDontSee('SO-DUE-002');
        $listResponse->assertSee('No Customer Dues Outstanding');
        $listResponse->assertSee('No Due Orders Found');

        // 5. Verify double entry journal entry was created
        $journal = JournalEntry::where('reference_type', 'customer_payment')->latest()->first();
        $this->assertNotNull($journal);
        $this->assertEquals(35000.00, (float)$journal->total_debit);
        $this->assertEquals(35000.00, (float)$journal->total_credit);

        // 6. Verify bank balance incremented by 35,000
        $this->bank->refresh();
        $this->assertEquals(135000.00, (float)$this->bank->current_balance);
    }

    public function test_partial_due_collection_reduces_sale_and_customer_due_proportionally(): void
    {
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'SO-DUE-003',
            'total'            => 20000.00,
            'bill'             => 20000.00,
            'payble'           => 20000.00,
            'advanced_payment' => 0.00,
            'due_payment'      => 20000.00,
            'status'           => 'partial',
        ]);

        // Customer has 5,000 opening + 20,000 sale = 25,000 total.
        // Pay 15,000: settles 5,000 opening, and 10,000 of the sale.
        $this->actingAs($this->admin)->post(route('customer.due.pay'), [
            'customer_id'    => $this->customer->id,
            'amount'         => 15000.00,
            'payment_type'   => 'auto_due_settlement',
            'payment_method' => 'cash',
        ]);

        $this->customer->refresh();
        $sale->refresh();

        $this->assertEquals(0.00, (float)$this->customer->opening_balance);
        $this->assertEquals(10000.00, (float)$sale->due_payment);
        $this->assertEquals(10000.00, (float)$sale->advanced_payment);
        $this->assertEquals(10000.00, $this->customer->total_due);
    }

    public function test_auto_reconciliation_clears_open_sales_when_unallocated_payment_exists(): void
    {
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'SO-DUE-004',
            'total'            => 18000.00,
            'bill'             => 18000.00,
            'payble'           => 18000.00,
            'advanced_payment' => 0.00,
            'due_payment'      => 18000.00,
            'status'           => 'partial',
        ]);

        // Simulate an unallocated payment already in database
        Payment::create([
            'payment_for'    => 2,
            'customer_id'    => $this->customer->id,
            'sale_id'        => null,
            'amount'         => 18000.00,
            'payment_method' => 'cash',
            'payment_date'   => now()->toDateString(),
            'remarks'        => 'Unallocated collection prior to fix',
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        // Trigger duePayments page which automatically runs reconcileUnallocatedPayments
        $response = $this->actingAs($this->admin)->get(route('due-payments.index'));
        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(0.00, (float)$sale->due_payment);
        $this->assertEquals(18000.00, (float)$sale->advanced_payment);
        $this->assertEquals('paid', $sale->status);
    }

    public function test_editing_customer_payment_from_1600_to_1500_restores_100_due_and_syncs_bank_and_ledger(): void
    {
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'SO-EDIT-001',
            'total'            => 2000.00,
            'bill'             => 2000.00,
            'payble'           => 2000.00,
            'advanced_payment' => 0.00,
            'due_payment'      => 2000.00,
            'status'           => 'partial',
        ]);

        // Mistakenly input 1600 instead of 1500
        $addResponse = $this->actingAs($this->admin)->post(route('add.payment'), [
            'id'             => $sale->id,
            'amount'         => 1600.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref'=> 'TX-MISTAKE-1600',
            'remarks'        => 'Collected due mistakenly 1600',
        ]);

        $addResponse->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertEquals(1600.00, (float)$sale->advanced_payment);
        $this->assertEquals(400.00, (float)$sale->due_payment);

        $payment = Payment::where('sale_id', $sale->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1600.00, (float)$payment->amount);

        // Bank balance should be 100,000 + 1,600 = 101,600
        $this->assertEquals(101600.00, (float)$this->bank->syncCurrentBalance());

        // Now edit payment: correct to 1500
        $editResponse = $this->actingAs($this->admin)->put(route('payments.update', $payment->id), [
            'amount'         => 1500.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref'=> 'TX-CORRECT-1500',
            'payment_date'   => now()->toDateString(),
            'remarks'        => 'Corrected due collection from 1600 to 1500',
        ]);

        $editResponse->assertSessionHasNoErrors();
        $editResponse->assertRedirect();

        // 1. Verify Payment updated
        $payment->refresh();
        $this->assertEquals(1500.00, (float)$payment->amount);
        $this->assertEquals('TX-CORRECT-1500', $payment->transaction_ref);

        // 2. Verify Sale due restored by 100
        $sale->refresh();
        $this->assertEquals(1500.00, (float)$sale->advanced_payment);
        $this->assertEquals(500.00, (float)$sale->due_payment);
        $this->assertEquals('partial', $sale->status);

        // 3. Verify Bank balance restored by 100 (100,000 + 1,500 = 101,500)
        $this->bank->refresh();
        $this->assertEquals(101500.00, (float)$this->bank->current_balance);

        // 4. Verify Journal Entry updated to 1500
        $journal = JournalEntry::where('reference_type', 'sale')->where('reference_id', $sale->id)->first();
        $this->assertNotNull($journal);
        $this->assertEquals(1500.00, (float)$journal->total_debit);
        $this->assertEquals(1500.00, (float)$journal->total_credit);
    }

    public function test_editing_vendor_payment_from_1600_to_1500_restores_100_due_and_syncs_bank_and_ledger(): void
    {
        $vendor = Vendor::create([
            'name'            => 'Abul Steel Mills Ltd',
            'phone'           => '01899112233',
            'opening_balance' => 0.00,
            'status'          => '1',
        ]);

        $purchase = Purchase::create([
            'vendor_id'    => $vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness'    => '2.5mm',
            'size'         => '4x8',
            'unit_weight'  => 50.0,
            'total_weight' => 500.0,
            'quantity'     => 10,
            'unit_price'   => 200.00,
            'sub_price'    => 2000.00,
            'total_price'  => 2000.00,
            'payment'      => 0.00,
            'due'          => 2000.00,
            'status'       => 'pending',
        ]);

        // Mistakenly input vendor disbursement 1600 instead of 1500
        $addResponse = $this->actingAs($this->admin)->post(route('vendor-payments.store'), [
            'vendor_id'      => $vendor->id,
            'purchase_id'    => $purchase->id,
            'amount'         => 1600.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref'=> 'TX-VENDOR-1600',
            'remarks'        => 'Paid vendor due mistakenly 1600',
        ]);

        $addResponse->assertSessionHasNoErrors();
        $purchase->refresh();
        $this->assertEquals(1600.00, (float)$purchase->payment);
        $this->assertEquals(400.00, (float)$purchase->due);

        $payment = Payment::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1600.00, (float)$payment->amount);

        // Bank balance after disbursement = 100,000 - 1,600 = 98,400
        $this->assertEquals(98400.00, (float)$this->bank->syncCurrentBalance());

        // Now edit vendor payment: correct to 1500
        $editResponse = $this->actingAs($this->admin)->put(route('vendor-payments.update', $payment->id), [
            'amount'         => 1500.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref'=> 'TX-VENDOR-CORRECT-1500',
            'payment_date'   => now()->toDateString(),
            'remarks'        => 'Corrected vendor disbursement to 1500',
        ]);

        $editResponse->assertSessionHasNoErrors();
        $editResponse->assertRedirect();

        // 1. Verify Payment updated
        $payment->refresh();
        $this->assertEquals(1500.00, (float)$payment->amount);
        $this->assertEquals('TX-VENDOR-CORRECT-1500', $payment->transaction_ref);

        // 2. Verify Purchase due restored by 100
        $purchase->refresh();
        $this->assertEquals(1500.00, (float)$purchase->payment);
        $this->assertEquals(500.00, (float)$purchase->due);

        // 3. Verify Bank balance restored by 100 (100,000 - 1,500 = 98,500)
        $this->bank->refresh();
        $this->assertEquals(98500.00, (float)$this->bank->current_balance);

        // 4. Verify Journal Entry updated to 1500
        $journal = JournalEntry::where('reference_type', 'purchase_payment')->where('reference_id', $payment->id)->first();
        $this->assertNotNull($journal);
        $this->assertEquals(1500.00, (float)$journal->total_debit);
        $this->assertEquals(1500.00, (float)$journal->total_credit);
    }

    public function test_deleting_customer_payment_reverts_sale_due_and_syncs_bank(): void
    {
        $sale = Sale::create([
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'order_no'         => 'SO-DEL-001',
            'total'            => 5000.00,
            'bill'             => 5000.00,
            'payble'           => 5000.00,
            'advanced_payment' => 3000.00,
            'due_payment'      => 2000.00,
            'status'           => 'partial',
        ]);

        $payment = Payment::create([
            'payment_for'    => 2,
            'customer_id'    => $this->customer->id,
            'sale_id'        => $sale->id,
            'amount'         => 3000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        $this->bank->syncCurrentBalance();
        $this->assertEquals(103000.00, (float)$this->bank->current_balance);

        $response = $this->actingAs($this->admin)->delete(route('delete.payment', $payment->id));
        $response->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertEquals(0.00, (float)$sale->advanced_payment);
        $this->assertEquals(5000.00, (float)$sale->due_payment);

        $this->bank->refresh();
        $this->assertEquals(100000.00, (float)$this->bank->current_balance);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_deleting_vendor_payment_reverts_purchase_due_and_syncs_bank(): void
    {
        $vendor = Vendor::create([
            'name'            => 'BSRM Steels',
            'phone'           => '01777889900',
            'opening_balance' => 0.00,
            'status'          => '1',
        ]);

        $purchase = Purchase::create([
            'vendor_id'    => $vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness'    => '3.0mm',
            'size'         => '4x8',
            'quantity'     => 5,
            'unit_price'   => 1000.00,
            'total_price'  => 5000.00,
            'payment'      => 3000.00,
            'due'          => 2000.00,
            'status'       => 'pending',
        ]);

        $payment = Payment::create([
            'payment_for'    => 3,
            'vendor_id'      => $vendor->id,
            'purchase_id'    => $purchase->id,
            'amount'         => 3000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'status'         => 1,
            'created_by'     => $this->admin->id,
        ]);

        $this->bank->syncCurrentBalance();
        $this->assertEquals(97000.00, (float)$this->bank->current_balance);

        $response = $this->actingAs($this->admin)->delete(route('vendor-payments.destroy', $payment->id));
        $response->assertSessionHasNoErrors();

        $purchase->refresh();
        $this->assertEquals(0.00, (float)$purchase->payment);
        $this->assertEquals(5000.00, (float)$purchase->due);

        $this->bank->refresh();
        $this->assertEquals(100000.00, (float)$this->bank->current_balance);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_customer_with_multiple_dues_shown_in_one_consolidated_row(): void
    {
        // Create 2 open sales with dues for the same customer
        $sale1 = Sale::create([
            'order_no'         => 'ORD-MULTI-001',
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'total'            => 4000.00,
            'bill'             => 4000.00,
            'payble'           => 4000.00,
            'advanced_payment' => 1000.00,
            'due_payment'      => 3000.00,
            'status'           => 'partial',
        ]);

        $sale2 = Sale::create([
            'order_no'         => 'ORD-MULTI-002',
            'customer_id'      => $this->customer->id,
            'warehouse_id'     => $this->warehouse->id,
            'total'            => 5000.00,
            'bill'             => 5000.00,
            'payble'           => 5000.00,
            'advanced_payment' => 2000.00,
            'due_payment'      => 3000.00,
            'status'           => 'partial',
        ]);

        $response = $this->actingAs($this->admin)->get(route('due-payments.index'));
        $response->assertStatus(200);

        // Assert customer appears once in the consolidated summary
        $response->assertSee($this->customer->name);
        // Assert 2 Orders badge is shown
        $response->assertSee('2 Orders');
        // Assert both orders appear in the customer breakdown
        $response->assertSee('ORD-MULTI-001');
        $response->assertSee('ORD-MULTI-002');
    }
}
