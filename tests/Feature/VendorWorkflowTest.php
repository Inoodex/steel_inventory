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
}

