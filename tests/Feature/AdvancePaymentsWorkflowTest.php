<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\BankDetail;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdvancePaymentsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Vendor $vendor;
    private BankDetail $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Advance Admin',
            'email' => 'advance.admin@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->customer = Customer::create([
            'name' => 'Karim Steel Mills',
            'company' => 'Karim Group',
            'phone' => '01711000111',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Bengal Iron & Steels',
            'company' => 'Bengal Steels',
            'phone' => '01811000222',
            'opening_balance' => 0.00,
            'status' => '1',
        ]);

        $this->bank = BankDetail::create([
            'bank_name' => 'Dutch-Bangla Bank',
            'account_name' => 'Steel Treasury Ltd',
            'account_number' => 'DBBL-44556677',
            'account_type' => 'Current',
            'is_active' => true,
        ]);
    }

    public function test_customer_advances_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('customer-advances.index'));
        $response->assertStatus(200);
        $response->assertSee('Customer Advance Payments');
    }

    public function test_can_receive_customer_advance_deposit(): void
    {
        $response = $this->actingAs($this->admin)->post(route('customer-advances.store'), [
            'customer_id'    => $this->customer->id,
            'amount'         => 75000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref'=> 'ADV-CUST-1001',
            'payment_date'   => now()->toDateString(),
            'remarks'        => 'Steel booking advance deposit',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'customer_id'     => $this->customer->id,
            'payment_for'     => 2,
            'sale_id'         => null,
            'amount'          => 75000.00,
            'payment_method'  => 'bank',
            'bank_detail_id'  => $this->bank->id,
            'transaction_ref' => 'ADV-CUST-1001',
        ]);

        $this->assertEquals(75000.00, $this->customer->fresh()->advance_credit);
    }

    public function test_vendor_advances_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('vendor-advances.index'));
        $response->assertStatus(200);
        $response->assertSee('Vendor Advance Payments');
    }

    public function test_can_pay_vendor_advance_disbursement(): void
    {
        $response = $this->actingAs($this->admin)->post(route('vendor-advances.store'), [
            'vendor_id'      => $this->vendor->id,
            'amount'         => 120000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bank->id,
            'transaction_ref'=> 'ADV-VEN-2002',
            'payment_date'   => now()->toDateString(),
            'remarks'        => 'Advance deposit for coil procurement',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'vendor_id'       => $this->vendor->id,
            'payment_for'     => 3,
            'purchase_id'     => null,
            'amount'          => 120000.00,
            'payment_method'  => 'bank',
            'bank_detail_id'  => $this->bank->id,
            'transaction_ref' => 'ADV-VEN-2002',
        ]);
    }

    public function test_customer_advances_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('customer-advances.create', [
            'customer_id' => $this->customer->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Receive Customer Advance');
        $response->assertSee($this->customer->name);
    }

    public function test_vendor_advances_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('vendor-advances.create', [
            'vendor_id' => $this->vendor->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Pay Vendor Advance');
        $response->assertSee($this->vendor->name);
    }

    public function test_customer_advances_statement_pdf_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('customer-advances.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_vendor_advances_statement_pdf_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('vendor-advances.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_customer_advance_receipt_pdf_renders(): void
    {
        $payment = Payment::create([
            'customer_id'     => $this->customer->id,
            'payment_for'     => 2,
            'sale_id'         => null,
            'amount'          => 50000.00,
            'payment_method'  => 'cash',
            'payment_date'    => now()->toDateString(),
            'transaction_ref' => 'RCP-TEST-1',
            'created_by'      => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('customer-advances.receipt-pdf', $payment->id));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_vendor_advance_voucher_pdf_renders(): void
    {
        $payment = Payment::create([
            'vendor_id'       => $this->vendor->id,
            'payment_for'     => 3,
            'purchase_id'     => null,
            'amount'          => 45000.00,
            'payment_method'  => 'bank',
            'bank_detail_id'  => $this->bank->id,
            'payment_date'    => now()->toDateString(),
            'transaction_ref' => 'VOUCH-TEST-1',
            'created_by'      => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('vendor-advances.voucher-pdf', $payment->id));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}

