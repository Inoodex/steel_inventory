<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Lot;
use App\Models\Purchase;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class VendorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Vendor Manager',
            'email' => 'vendor_mgr@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);
    }

    public function test_can_create_vendor_with_opening_due(): void
    {
        $response = $this->actingAs($this->admin)->post(route('vendors.store'), [
            'name' => 'National Steel Mills',
            'phone' => '01700112233',
            'email' => 'national@mills.test',
            'address' => 'Chittagong Yard',
            'opening_due' => '35000.50',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', [
            'name' => 'National Steel Mills',
            'phone' => '01700112233',
            'opening_balance' => 35000.50,
        ]);
    }

    public function test_can_update_vendor_opening_due(): void
    {
        $vendor = Vendor::create([
            'name' => 'Dhaka Re-Rolling',
            'phone' => '01800112233',
            'address' => 'Fatullah',
            'opening_balance' => 10000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('vendors.update', $vendor->id), [
            'name' => 'Dhaka Re-Rolling Ltd',
            'phone' => '01800112233',
            'address' => 'Fatullah Industrial Area',
            'opening_due' => '45000.00',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('vendors.index'));

        $vendor->refresh();
        $this->assertEquals('Dhaka Re-Rolling Ltd', $vendor->name);
        $this->assertEquals(45000.00, (float)$vendor->opening_balance);
    }

    public function test_vendor_opening_due_rejects_negative_value(): void
    {
        $response = $this->actingAs($this->admin)->post(route('vendors.store'), [
            'name' => 'Negative Supplier',
            'phone' => '01900112233',
            'address' => 'Dhaka',
            'opening_due' => '-500',
        ]);

        $response->assertSessionHasErrors('opening_due');
    }

    public function test_vendor_create_and_edit_pages_render_opening_due_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('vendors.create'));
        $response->assertStatus(200);
        $response->assertSee('name="opening_due"', false);

        $vendor = Vendor::create([
            'name' => 'View Test Vendor',
            'phone' => '01500112233',
            'address' => 'Dhaka',
            'opening_balance' => 12500.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('vendors.edit', $vendor->id));
        $response->assertStatus(200);
        $response->assertSee('name="opening_due"', false);
        $response->assertSee('12500.00');
    }

    public function test_vendor_index_renders_identical_columns_and_opening_due(): void
    {
        $warehouse = Warehouse::create(['name' => 'Depot 1', 'type' => 'warehouse']);
        $vendor = Vendor::create([
            'name' => 'Standard Steel Corp',
            'phone' => '01600112233',
            'email' => 'standard@steel.test',
            'address' => 'Sitakunda, Chittagong',
            'opening_balance' => 40000.00,
            'status' => 'active',
        ]);

        $lot = Lot::create([
            'vendor_id' => $vendor->id,
            'lot_number' => 'LOT-STD-01',
            'date' => now()->toDateString(),
            'status' => 'received',
        ]);

        $purchase = Purchase::create([
            'vendor_id' => $vendor->id,
            'lot_id' => $lot->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 60000.00,
            'total_price' => 60000.00,
            'payment' => 20000.00,
            'due' => 40000.00,
            'status' => 'partial',
        ]);

        Payment::create([
            'payment_for' => 3,
            'vendor_id' => $vendor->id,
            'purchase_id' => $purchase->id,
            'amount' => 20000.00,
            'payment_method' => 'cash',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('vendors.index'));
        $response->assertStatus(200);

        // Assert headers
        $response->assertSee('Opening Due');
        $response->assertSee('Purchases Due');
        $response->assertSee('Account Balance');

        // Assert figures
        $response->assertSee('40,000.00'); // opening due and purchase due
        $response->assertSee('80,000.00'); // total payable account balance
    }

    public function test_vendor_show_displays_opening_due_metric_card(): void
    {
        $vendor = Vendor::create([
            'name' => 'Profile Test Vendor',
            'phone' => '01400112233',
            'address' => 'Bhatiary, Chittagong',
            'opening_balance' => 50000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('vendors.show', $vendor->id));
        $response->assertStatus(200);
        $response->assertSee('Opening Due Balance');
        $response->assertSee('50,000.00');
    }

    public function test_vendor_creation_and_balance(): void
    {
        $vendor = Vendor::create([
            'name' => 'Eastern Steel Suppliers',
            'company' => 'Eastern Group',
            'phone' => '01311112222',
            'opening_balance' => 100000.00,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Eastern Steel Suppliers',
            'opening_balance' => 100000.00,
        ]);
    }

    public function test_vendor_soft_delete(): void
    {
        $vendor = Vendor::create([
            'name' => 'Deletable Supplier',
            'phone' => '01399998888',
            'status' => 'active',
        ]);

        $vendor->delete();

        $this->assertSoftDeleted('vendors', [
            'id' => $vendor->id,
        ]);
    }

    public function test_vendor_due_payments_page_consolidated_per_vendor(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Main Steel Depot',
            'type' => 'warehouse',
        ]);

        $vendor1 = Vendor::create([
            'name' => 'Apex Iron Mill',
            'company_name' => 'Apex Group',
            'phone' => '01711223344',
            'opening_balance' => 50000.00,
            'status' => 'active',
        ]);

        $vendor2 = Vendor::create([
            'name' => 'Premier Steel Ltd',
            'company_name' => 'Premier Corp',
            'phone' => '01811223344',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        // Create purchases for vendor1
        $lot1 = Lot::create([
            'vendor_id' => $vendor1->id,
            'lot_number' => 'LOT-V1-01',
            'date' => now()->toDateString(),
            'status' => 'received',
        ]);

        Purchase::create([
            'vendor_id' => $vendor1->id,
            'lot_id' => $lot1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 120000.00,
            'total_price' => 120000.00,
            'payment' => 40000.00,
            'due' => 80000.00,
            'status' => 'partial',
        ]);

        // Create purchases for vendor2
        $lot2 = Lot::create([
            'vendor_id' => $vendor2->id,
            'lot_number' => 'LOT-V2-01',
            'date' => now()->toDateString(),
            'status' => 'received',
        ]);

        Purchase::create([
            'vendor_id' => $vendor2->id,
            'lot_id' => $lot2->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 50000.00,
            'total_price' => 50000.00,
            'payment' => 20000.00,
            'due' => 30000.00,
            'status' => 'partial',
        ]);

        // Request vendor due payments page
        $response = $this->actingAs($this->admin)->get(route('vendor-due-payments.index'));

        $response->assertStatus(200);
        $response->assertSee('Apex Iron Mill');
        $response->assertSee('Premier Steel Ltd');
        $response->assertSee('By Vendor Summary (Consolidated)');
        $response->assertSee('By Purchase Orders (PO / Lots)');
        
        // Check consolidated dues calculation
        // Vendor 1: opening 50,000 + purchase 80,000 = 130,000 total due
        // Vendor 2: opening 0 + purchase 30,000 = 30,000 total due
        $response->assertSee('130,000.00');
        $response->assertSee('30,000.00');
    }

    public function test_vendor_due_payments_pdf(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
        ]);

        $vendor = Vendor::create([
            'name' => 'Metro Steel Co',
            'phone' => '01911223344',
            'opening_balance' => 25000.00,
            'status' => 'active',
        ]);

        $lot = Lot::create([
            'vendor_id' => $vendor->id,
            'lot_number' => 'LOT-METRO-01',
            'date' => now()->toDateString(),
            'status' => 'received',
        ]);

        Purchase::create([
            'vendor_id' => $vendor->id,
            'lot_id' => $lot->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 75000.00,
            'total_price' => 75000.00,
            'payment' => 25000.00,
            'due' => 50000.00,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($this->admin)->get(route('vendor-due-payments.pdf'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_consolidated_vendor_due_payment_settlement(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
        ]);

        $vendor = Vendor::create([
            'name' => 'Summit Steel Corp',
            'phone' => '01799887766',
            'opening_balance' => 20000.00,
            'status' => 'active',
        ]);

        $lot = Lot::create([
            'vendor_id' => $vendor->id,
            'lot_number' => 'LOT-SUMMIT-01',
            'date' => now()->toDateString(),
            'status' => 'received',
        ]);

        $purchase1 = Purchase::create([
            'vendor_id' => $vendor->id,
            'lot_id' => $lot->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 50000.00,
            'total_price' => 50000.00,
            'payment' => 20000.00,
            'due' => 30000.00,
            'status' => 'partial',
            'created_at' => now()->subDay(),
        ]);

        $purchase2 = Purchase::create([
            'vendor_id' => $vendor->id,
            'lot_id' => $lot->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 60000.00,
            'total_price' => 60000.00,
            'payment' => 20000.00,
            'due' => 40000.00,
            'status' => 'partial',
            'created_at' => now(),
        ]);

        // Submit consolidated vendor due payment of 45,000 (no purchase_id)
        $response = $this->actingAs($this->admin)->post(route('vendor-payments.store'), [
            'vendor_id' => $vendor->id,
            'purchase_id' => '',
            'amount' => 45000.00,
            'payment_method' => 'cash',
            'remarks' => 'Settling consolidated dues',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Opening balance settled: 20000 - 20000 = 0
        $vendor->refresh();
        $this->assertEquals(0.00, (float)$vendor->opening_balance);

        // 2. Oldest purchase1 settled with remaining 25000: 30000 - 25000 = 5000 due
        $purchase1->refresh();
        $this->assertEquals(5000.00, (float)$purchase1->due);
        $this->assertEquals(45000.00, (float)$purchase1->payment);

        // 3. Purchase2 untouched: 40000 due
        $purchase2->refresh();
        $this->assertEquals(40000.00, (float)$purchase2->due);

        // 4. Verify Payment records created for opening balance and purchase
        $this->assertDatabaseHas('payments', [
            'vendor_id' => $vendor->id,
            'amount' => 20000.00,
            'payment_for' => 3,
            'payment_method' => 'cash',
        ]);
        $this->assertDatabaseHas('payments', [
            'vendor_id' => $vendor->id,
            'amount' => 25000.00,
            'payment_for' => 3,
            'payment_method' => 'cash',
        ]);
    }

    public function test_vendor_due_payment_with_discount_reduces_purchase_due_and_balance(): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Discount Test Yard',
            'status' => 'active',
        ]);

        $vendor = Vendor::create([
            'name' => 'Discount Test Vendor',
            'phone' => '01555444333',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $lot = Lot::create([
            'lot_number' => 'LOT-DISC-001',
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'received_date' => now(),
            'status' => 'active',
            'total_amount' => 10000.00,
        ]);

        $purchase = Purchase::create([
            'purchase_date' => now(),
            'vendor_id' => $vendor->id,
            'lot_id' => $lot->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'unit_weight' => 1.0,
            'total_weight' => 1.0,
            'unit_price' => 10000.00,
            'total_price' => 10000.00,
            'payment' => 0.00,
            'due' => 10000.00,
            'status' => 'unpaid',
            'created_at' => now(),
        ]);

        // Pay specific PO with 9,000 cash and 1,000 discount
        $response = $this->actingAs($this->admin)->post(route('vendor-payments.store'), [
            'vendor_id' => $vendor->id,
            'purchase_id' => $purchase->id,
            'amount' => 9000.00,
            'discount' => 1000.00,
            'payment_method' => 'cash',
            'remarks' => 'Settled with discount rebate',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $purchase->refresh();
        $this->assertEquals(1000.00, (float)$purchase->discount);
        $this->assertEquals(9000.00, (float)$purchase->total_price);
        $this->assertEquals(9000.00, (float)$purchase->payment);
        $this->assertEquals(0.00, (float)$purchase->due);

        // Verify payment record is for 9,000 cash
        $this->assertDatabaseHas('payments', [
            'vendor_id' => $vendor->id,
            'purchase_id' => $purchase->id,
            'amount' => 9000.00,
            'payment_for' => 3,
        ]);

        // Vendor net balance is 0
        $vendor->refresh();
        $this->assertEquals(0.00, (float)$vendor->net_balance);
    }
}

