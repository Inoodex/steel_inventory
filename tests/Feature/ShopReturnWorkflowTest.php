<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Lot;
use App\Models\Coil;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\ProductReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ShopReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Warehouse $shop;
    private Vendor $vendor;
    private Lot $lot;
    private Coil $coil;
    private Sale $shopSale;
    private SalesItem $salesItem;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Shop Admin',
            'email' => 'shopadmin@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->customer = Customer::create([
            'name' => 'Apex Retail Customer',
            'company' => 'Apex Build',
            'phone' => '01711112222',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->shop = Warehouse::create([
            'name' => 'Central Retail Outlet',
            'type' => 'shop',
            'code' => 'SH-01',
            'location' => 'Main Commercial Road',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Prime Steel Mill',
            'phone' => '01900000000',
            'status' => 'active',
        ]);

        $this->lot = Lot::create([
            'lot_number' => 'LOT-SHOP-01',
            'vendor_id' => $this->vendor->id,
            'status' => 'active',
        ]);

        $this->coil = Coil::create([
            'coil_number' => 'COIL-SHOP-01',
            'lot_id' => $this->lot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->shop->id,
            'thickness' => 0.40,
            'gross_weight' => 500.00,
            'tare_weight' => 0.00,
            'net_weight' => 500.00,
            'remaining_weight' => 500.00,
            'rate_per_ton' => 90000.00,
            'status' => 'in_stock',
            'created_by' => $this->admin->id,
        ]);

        $this->shopSale = Sale::create([
            'order_no' => 'SH-INV-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->shop->id,
            'order_date' => now()->toDateString(),
            'qty' => 100.00,
            'subtotal' => 10000.00,
            'total' => 10000.00,
            'payble' => 10000.00,
            'bill' => 10000.00,
            'due_payment' => 10000.00,
            'advanced_payment' => 0.00,
            'status' => 'credit',
            'sales_by' => $this->admin->id,
        ]);

        $this->salesItem = SalesItem::create([
            'order_id' => $this->shopSale->id,
            'coil_id' => $this->coil->id,
            'lot_id' => $this->lot->id,
            'qty' => 100.00,
            'unit_price' => 100.00,
            'total_price' => 10000.00,
            'returned_qty' => 0.00,
        ]);
    }

    public function test_shop_return_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('shops.returns.create'));
        $response->assertStatus(200);
        $response->assertSee('Create Shop Return');
        $response->assertSee('SH-INV-001');
    }

    public function test_shop_sale_items_ajax_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('shops.returns.sale.items', $this->shopSale->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'sale',
            'items' => [
                '*' => [
                    'id',
                    'sales_item_id',
                    'product_id',
                    'product_name',
                    'quantity',
                    'available_qty',
                    'unit_price',
                    'total_price',
                ]
            ]
        ]);
        $this->assertCount(1, $response->json('items'));
    }

    public function test_can_create_shop_return_and_view_details(): void
    {
        $payload = [
            'sale_id' => $this->shopSale->id,
            'return_date' => now()->toDateString(),
            'reason' => 'Customer over-ordered for project',
            'items' => [
                [
                    'sales_item_id' => $this->salesItem->id,
                    'product_id' => $this->coil->id,
                    'quantity' => 20.00,
                    'unit_price' => 100.00,
                    'return_reason' => 'customer_changed_mind',
                    'condition' => 'good',
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('shops.returns.store'), $payload);

        $return = ProductReturn::where('sale_id', $this->shopSale->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals(2000.00, (float)$return->total_refund_amount);
        $this->assertEquals('pending', $return->status);

        $response->assertRedirect(route('shops.returns.show', $return->id));

        // Test show details page
        $showResponse = $this->actingAs($this->admin)->get(route('shops.returns.show', $return->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Shop Return Details #' . $return->id);
        $showResponse->assertSee('2,000.00');

        // Test approve returns
        $approveResponse = $this->actingAs($this->admin)->patch(route('returns.approve', $return->id));
        $return->refresh();
        $this->assertEquals('approved', $return->status);
        $this->coil->refresh();
        $this->assertEquals(520.00, (float)$this->coil->remaining_weight); // 500 + 20 restocked
    }
}
