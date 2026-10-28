<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Lot;
use App\Models\Purchase;
use App\Models\Coil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ShopPurchaseSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $shop;
    protected Warehouse $yard;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->user->assignRole($role);
        
        $this->shop = Warehouse::create([
            'name' => 'Test Retail Shop',
            'type' => 'shop',
            'code' => 'SH-TEST',
            'status' => 'active',
        ]);

        $this->yard = Warehouse::create([
            'name' => 'Main Steel Yard',
            'type' => 'warehouse',
            'code' => 'YD-01',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Steel Supplier Corp',
            'status' => '1',
        ]);
    }

    public function test_shop_purchases_and_lots_do_not_appear_on_normal_purchase_list(): void
    {
        // 1. Create a Yard Lot and Purchase
        $yardLot = Lot::create([
            'lot_number' => 'LOT-YARD-001',
            'vendor_id' => $this->vendor->id,
            'lot_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $yardPurchase = Purchase::create([
            'lot_id' => $yardLot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->yard->id,
            'thickness' => '2.50mm',
            'size' => '4x8',
            'quantity' => 1,
            'total_weight' => 5000,
            'unit_price' => 100,
            'total_price' => 500000,
            'payment' => 500000,
            'due' => 0,
        ]);

        // 2. Create a Shop Lot and Purchase
        $shopLot = Lot::create([
            'lot_number' => 'LOT-SHOP-001',
            'vendor_id' => $this->vendor->id,
            'lot_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ]);

        $shopPurchase = Purchase::create([
            'lot_id' => $shopLot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->shop->id,
            'thickness' => '1.20mm',
            'size' => '3x6',
            'quantity' => 1,
            'total_weight' => 2000,
            'unit_price' => 120,
            'total_price' => 240000,
            'payment' => 100000,
            'due' => 140000,
        ]);

        // 3. Test Normal Purchase Index
        $response = $this->actingAs($this->user)->get(route('purchase.index'));
        $response->assertStatus(200);
        $response->assertSee('LOT-YARD-001');
        $response->assertDontSee('LOT-SHOP-001');

        // 4. Test Normal Lots Index
        $lotsResponse = $this->actingAs($this->user)->get(route('lots.index'));
        $lotsResponse->assertStatus(200);
        $lotsResponse->assertSee('LOT-YARD-001');
        $lotsResponse->assertDontSee('LOT-SHOP-001');

        // 5. Test Normal Purchase Report
        $reportResponse = $this->actingAs($this->user)->get(route('purchase.report'));
        $reportResponse->assertStatus(200);
        $reportResponse->assertSee('LOT-YARD-001');
        $reportResponse->assertDontSee('LOT-SHOP-001');

        // 6. Test Vendor Due Payments includes all supplier liabilities (both Yard & Shop)
        $dueResponse = $this->actingAs($this->user)->get(route('vendor-due-payments.index'));
        $dueResponse->assertStatus(200);
        $dueResponse->assertSee('LOT-SHOP-001');

        // 7. Test Shop Purchases Index shows shop purchase and lot
        $shopResponse = $this->actingAs($this->user)->get(route('shops.purchases.index'));
        $shopResponse->assertStatus(200);
        $shopResponse->assertSee('LOT-SHOP-001');
        $shopResponse->assertDontSee('LOT-YARD-001');
    }

    public function test_shop_purchase_store_redirects_to_shop_purchases_index(): void
    {
        $payload = [
            'lot_type' => 'new',
            'new_lot_number' => 'LOT-SHOP-INTAKE-99',
            'warehouse_id' => $this->shop->id,
            'vendor_id' => $this->vendor->id,
            'purchase_date' => now()->format('Y-m-d'),
            'payment' => 500,
            'payment_method' => 'cash',
            'items' => [
                [
                    'vendor_id' => $this->vendor->id,
                    'thickness' => '2.00mm',
                    'size' => '4x8',
                    'quantity' => 1,
                    'unit_weight' => 10,
                    'total_weight' => 10,
                    'unit_price' => 100,
                    'sub_price' => 1000,
                ]
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('purchase.store'), $payload);
        $response->assertRedirect(route('shops.purchases.index'));
    }

    public function test_shop_purchase_edit_page_renders_successfully(): void
    {
        $lot = Lot::create([
            'lot_number' => 'LOT-SHOP-EDIT-01',
            'vendor_id' => $this->vendor->id,
            'status' => 'active',
            'total_quantity' => 10,
            'total_amount' => 1000,
        ]);

        $purchase = Purchase::create([
            'lot_id' => $lot->id,
            'warehouse_id' => $this->shop->id,
            'vendor_id' => $this->vendor->id,
            'thickness' => '2.50mm',
            'size' => '3x8',
            'size_type' => 'ft',
            'unit_weight' => 10,
            'total_weight' => 10,
            'quantity' => 1,
            'unit_price' => 100,
            'sub_price' => 1000,
            'total_price' => 1000,
            'payment' => 500,
            'due' => 500,
            'status' => 'completed',
        ]);

        Coil::create([
            'lot_id' => $lot->id,
            'purchase_id' => $purchase->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->shop->id,
            'coil_number' => 'COIL-SHOP-001',
            'thickness' => '2.50mm',
            'width' => '3x8',
            'length' => 'ft',
            'piece_count' => 1,
            'gross_weight' => 10,
            'net_weight' => 10,
            'remaining_weight' => 10,
            'rate_per_ton' => 100,
            'total_price' => 1000,
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->user)->get(route('shops.purchases.edit', $purchase->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Shop Purchase Order');
        $response->assertSee('LOT-SHOP-EDIT-01');
        $response->assertSee('COIL-SHOP-001');
        $response->assertSee('2.50mm');
    }

    public function test_shop_purchase_update_updates_record_and_redirects_to_shop_purchases(): void
    {
        $lot = Lot::create([
            'lot_number' => 'LOT-SHOP-EDIT-02',
            'vendor_id' => $this->vendor->id,
            'status' => 'active',
            'total_quantity' => 10,
            'total_amount' => 1000,
        ]);

        $purchase = Purchase::create([
            'lot_id' => $lot->id,
            'warehouse_id' => $this->shop->id,
            'vendor_id' => $this->vendor->id,
            'thickness' => '2.50mm',
            'size' => '3x8',
            'size_type' => 'ft',
            'unit_weight' => 10,
            'total_weight' => 10,
            'quantity' => 1,
            'unit_price' => 100,
            'sub_price' => 1000,
            'total_price' => 1000,
            'payment' => 500,
            'due' => 500,
            'status' => 'completed',
        ]);

        $coil = Coil::create([
            'lot_id' => $lot->id,
            'purchase_id' => $purchase->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->shop->id,
            'coil_number' => 'COIL-SHOP-002',
            'thickness' => '2.50mm',
            'width' => '3x8',
            'length' => 'ft',
            'piece_count' => 1,
            'gross_weight' => 10,
            'net_weight' => 10,
            'remaining_weight' => 10,
            'rate_per_ton' => 100,
            'total_price' => 1000,
            'status' => 'in_stock',
        ]);

        $payload = [
            'lot_id' => $lot->id,
            'vendor_id' => $this->vendor->id,
            'thickness' => '3.00mm',
            'size' => '4x8',
            'size_type' => 'ft',
            'quantity' => 2,
            'unit_weight' => 15,
            'total_weight' => 30,
            'unit_price' => 120,
            'sub_price' => 3600,
            'delivery_charge' => 100,
            'transport_payer' => 'vendor',
            'labour_cost' => 50,
            'weight_scale_cost' => 50,
            'other_charges' => 0,
            'discount' => 0,
            'total_price' => 3800,
            'payment' => 2000,
            'due' => 1800,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($this->user)->put(route('shops.purchases.update', $purchase->id), $payload);
        $response->assertRedirect(route('shops.purchases.index'));
        $response->assertSessionHas('success');

        $purchase->refresh();
        $this->assertEquals('3.00mm', $purchase->thickness);
        $this->assertEquals('4x8', $purchase->size);
        $this->assertEquals(2, $purchase->quantity);
        $this->assertEquals(30, (float)$purchase->total_weight);
        $this->assertEquals(3800, (float)$purchase->total_price);
        $this->assertEquals(2000, (float)$purchase->payment);
        $this->assertEquals(1800, (float)$purchase->due);

        $coil->refresh();
        $this->assertEquals('3.00mm', $coil->thickness);
        $this->assertEquals(30, (float)$coil->net_weight);
        $this->assertEquals(30, (float)$coil->remaining_weight);
    }
}
