<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Lot;
use App\Models\Purchase;
use App\Models\Coil;
use App\Models\PurchaseReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PurchaseReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vendor $vendor;
    private Warehouse $warehouse;
    private Lot $lot;
    private Purchase $purchase;
    private Coil $coil;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Steel Admin',
            'email' => 'admin@steel.test',
            'password' => Hash::make('password123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->vendor = Vendor::create([
            'name' => 'Tata Steel Bangladesh',
            'phone' => '01811223344',
            'email' => 'tata@steel.test',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Central Steel Yard',
            'type' => 'stockyard',
            'status' => 'active',
        ]);

        $this->lot = Lot::create([
            'lot_number' => 'LOT-2026-001',
            'status' => 'active',
        ]);

        // Purchase: 100 kg at ৳120 = ৳12,000, Due = ৳12,000
        $this->purchase = Purchase::create([
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'lot_id' => $this->lot->id,
            'product_name' => 'Prime Hot Rolled Steel',
            'thickness' => '2.5mm',
            'size' => '1220mm',
            'unit_price' => 120.00,
            'unit_weight' => 100.00,
            'total_weight' => 100.00,
            'quantity' => 1,
            'sub_price' => 12000.00,
            'total_price' => 12000.00,
            'paid' => 0.00,
            'due' => 12000.00,
        ]);

        // Coil: initial 100 kg, remaining 100 kg
        $this->coil = Coil::create([
            'coil_number' => 'COIL-TEST-100',
            'purchase_id' => $this->purchase->id,
            'lot_id' => $this->lot->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness' => '2.5mm',
            'size' => '1220mm',
            'net_weight' => 100.00,
            'remaining_weight' => 100.00,
            'cost_per_kg' => 120.00,
            'status' => 'in_stock',
        ]);
    }

    public function test_can_render_purchase_returns_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('purchase-returns.index'));
        $response->assertStatus(200);
        $response->assertSee('Purchase Returns &amp; Debit Notes', false);
    }

    public function test_can_render_purchase_returns_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('purchase-returns.create'));
        $response->assertStatus(200);
        $response->assertSee('Create Purchase Return / Debit Note');
    }

    public function test_can_fetch_vendor_purchases_via_ajax(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('purchase-returns.vendor-purchases', $this->vendor->id));
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals($this->purchase->id, $data[0]['id']);
        $this->assertEquals(100.00, $data[0]['remaining_weight']);
        $this->assertEquals('COIL-TEST-100', $data[0]['coil_number']);
    }

    public function test_can_create_purchase_return_and_deduct_coil_stock(): void
    {
        // Return 50 kg of the 100 kg coil
        $response = $this->actingAs($this->admin)->post(route('purchase-returns.store'), [
            'return_date' => date('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'purchase_id' => $this->purchase->id,
            'return_weight' => 50.00,
            'unit_price' => 120.00,
            'refund_type' => 'reduce_due',
            'reason' => 'defective',
            'notes' => 'Returned 50 kg to vendor due to edge crack defect. Gate Pass #GP-001',
        ]);

        $response->assertSessionHasNoErrors();

        // Coil should now have 50 kg remaining
        $this->coil->refresh();
        $this->assertEquals(50.00, (float)$this->coil->remaining_weight);
        $this->assertEquals('in_stock', $this->coil->status);

        // Purchase due should be reduced by 50 * 120 = 6,000
        $this->purchase->refresh();
        $this->assertEquals(6000.00, (float)$this->purchase->due);

        // Verify purchase_returns record created
        $this->assertDatabaseHas('purchase_returns', [
            'vendor_id' => $this->vendor->id,
            'purchase_id' => $this->purchase->id,
            'return_weight' => 50.00,
            'unit_price' => 120.00,
            'total_refund_amount' => 6000.00,
            'refund_type' => 'reduce_due',
        ]);
    }

    public function test_cannot_return_weight_exceeding_available_stock(): void
    {
        // Reduce coil remaining weight to 30 kg (as if 70 kg was already sold)
        $this->coil->update(['remaining_weight' => 30.00]);

        // Attempting to return 50 kg must fail
        $response = $this->actingAs($this->admin)->post(route('purchase-returns.store'), [
            'return_date' => date('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'purchase_id' => $this->purchase->id,
            'return_weight' => 50.00,
            'unit_price' => 120.00,
            'refund_type' => 'reduce_due',
            'reason' => 'excess',
        ]);

        $response->assertSessionHas('error');

        // Coil stock remains unchanged at 30 kg
        $this->coil->refresh();
        $this->assertEquals(30.00, (float)$this->coil->remaining_weight);
        $this->assertDatabaseCount('purchase_returns', 0);
    }

    public function test_exhausts_coil_when_returning_entire_stock(): void
    {
        // Return all 100 kg
        $response = $this->actingAs($this->admin)->post(route('purchase-returns.store'), [
            'return_date' => date('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'purchase_id' => $this->purchase->id,
            'return_weight' => 100.00,
            'unit_price' => 120.00,
            'refund_type' => 'reduce_due',
        ]);

        $response->assertSessionHasNoErrors();

        // Coil stock is 0 and exhausted
        $this->coil->refresh();
        $this->assertEquals(0.00, (float)$this->coil->remaining_weight);
        $this->assertEquals('exhausted', $this->coil->status);
    }

    public function test_can_view_purchase_return_voucher_and_stream_pdf(): void
    {
        $pReturn = PurchaseReturn::create([
            'return_no' => 'PR-20261109-001',
            'return_date' => date('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'coil_id' => $this->coil->id,
            'lot_id' => $this->lot->id,
            'return_weight' => 50.00,
            'return_qty' => 1,
            'unit_price' => 120.00,
            'total_refund_amount' => 6000.00,
            'refund_type' => 'reduce_due',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        // Voucher show page
        $showResponse = $this->actingAs($this->admin)->get(route('purchase-returns.show', $pReturn->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('PR-20261109-001');
        $showResponse->assertSee('Tata Steel Bangladesh');

        // PDF stream response
        $pdfResponse = $this->actingAs($this->admin)->get(route('purchase-returns.pdf', $pReturn->id));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_purchase_return_is_reflected_in_vendor_ledger(): void
    {
        $pReturn = PurchaseReturn::create([
            'return_no' => 'PR-20261109-002',
            'return_date' => date('Y-m-d'),
            'vendor_id' => $this->vendor->id,
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'coil_id' => $this->coil->id,
            'lot_id' => $this->lot->id,
            'return_weight' => 50.00,
            'return_qty' => 1,
            'unit_price' => 120.00,
            'total_refund_amount' => 6000.00,
            'refund_type' => 'reduce_due',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        // Check vendor ledger response
        $response = $this->actingAs($this->admin)->get(route('vendors.ledger', $this->vendor->id));
        $response->assertStatus(200);
        $response->assertSee('PR-20261109-002');
        $response->assertSee('Purchase Return');
    }
}
