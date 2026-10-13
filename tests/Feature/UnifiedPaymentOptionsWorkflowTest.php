<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\BankDetail;
use App\Models\DailyExpense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UnifiedPaymentOptionsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Vendor $vendor;
    private BankDetail $bank;
    private BankDetail $mfs;
    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Payment Admin',
            'email' => 'payment.admin@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->customer = Customer::create([
            'name' => 'Unified Customer',
            'phone' => '01700000001',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Unified Vendor',
            'phone' => '01800000002',
            'opening_balance' => 0.00,
            'status' => '1',
        ]);

        $this->bank = BankDetail::create([
            'bank_name' => 'City Bank PLC',
            'account_name' => 'Steel Treasury Ltd',
            'account_number' => 'CBL-998877',
            'account_type' => 'Corporate Current',
            'is_active' => true,
        ]);

        $this->mfs = BankDetail::create([
            'bank_name' => 'bKash Merchant',
            'account_name' => 'Steel Inoodex MFS',
            'account_number' => '01700000000',
            'account_type' => 'MFS Merchant',
            'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'name' => 'Office Utility',
            'status' => 'active',
        ]);
    }

    public function test_expense_create_page_renders_unified_selector(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dailyExpenses.create'));
        $response->assertStatus(200);
        $response->assertSee('expenseAccountSelector');
        $response->assertSee('City Bank PLC');
        $response->assertDontSee('Cash in Hand');
    }

    public function test_can_create_expense_with_unified_cash_and_bank(): void
    {
        // 1. Cash expense
        $cashResponse = $this->actingAs($this->admin)->post(route('dailyExpenses.store'), [
            'date' => date('Y-m-d'),
            'expense_category_id' => $this->category->id,
            'amount' => 1500.00,
            'spend_method' => 'cash',
            'bank_detail_id' => null,
            'remarks' => 'Cash expense utility bill',
        ]);
        $cashResponse->assertRedirect(route('dailyExpenses.index'));

        $this->assertDatabaseHas('daily_expenses', [
            'amount' => 1500.00,
            'spend_method' => 'cash',
            'bank_detail_id' => null,
        ]);

        // 2. Bank expense
        $bankResponse = $this->actingAs($this->admin)->post(route('dailyExpenses.store'), [
            'date' => date('Y-m-d'),
            'expense_category_id' => $this->category->id,
            'amount' => 4500.00,
            'spend_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'remarks' => 'Bank transfer for equipment maintenance',
        ]);
        $bankResponse->assertRedirect(route('dailyExpenses.index'));

        $this->assertDatabaseHas('daily_expenses', [
            'amount' => 4500.00,
            'spend_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
        ]);
    }

    public function test_bill_payment_page_renders_with_unified_selector(): void
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'order_no' => 'SO-TEST-001',
            'total' => 25000.00,
            'bill' => 25000.00,
            'payble' => 25000.00,
            'advanced_payment' => 5000.00,
            'due_payment' => 20000.00,
            'status' => 'partial',
            'payment_status' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales.payments', ['saleId' => $sale->id]));
        $response->assertStatus(200);
        $response->assertSee('salesPaymentAccountSelector');
        $response->assertSee('City Bank PLC');
        $response->assertSee('bKash Merchant');
    }

    public function test_can_collect_due_payment_with_bank_via_unified_selection(): void
    {
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'order_no' => 'SO-TEST-002',
            'total' => 50000.00,
            'bill' => 50000.00,
            'payble' => 50000.00,
            'advanced_payment' => 10000.00,
            'due_payment' => 40000.00,
            'status' => 'partial',
            'payment_status' => 0,
        ]);

        $response = $this->actingAs($this->admin)->post(route('add.payment'), [
            'id' => $sale->id,
            'payment_for' => 2,
            'amount' => 15000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref' => 'TRX-BANK-9988',
            'remarks' => 'Direct bank due collection',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'customer_id' => $this->customer->id,
            'amount' => 15000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref' => 'TRX-BANK-9988',
        ]);

        $sale->refresh();
        $this->assertEquals(25000.00, (float)$sale->advanced_payment);
        $this->assertEquals(25000.00, (float)$sale->due_payment);
    }
}
