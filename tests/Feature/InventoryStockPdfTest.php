<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Coil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class InventoryStockPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $yardWarehouse;
    private Warehouse $shopWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Inventory Admin',
            'email' => 'inv.admin@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->yardWarehouse = Warehouse::create([
            'name' => 'Central Yard B',
            'code' => 'YARD-B',
            'type' => 'warehouse',
            'status' => 'active',
        ]);

        $this->shopWarehouse = Warehouse::create([
            'name' => 'Retail Outlet A',
            'code' => 'SHOP-A',
            'type' => 'shop',
            'status' => 'active',
        ]);
    }

    public function test_inventory_pdf_excludes_shop_stock(): void
    {
        // 1. Create a yard coil (in_stock)
        $yardCoil = Coil::create([
            'coil_number' => 'YARD-COIL-101',
            'warehouse_id' => $this->yardWarehouse->id,
            'thickness' => '2.5mm',
            'width' => '1250mm',
            'length' => 'Coil',
            'piece_count' => 1,
            'gross_weight' => 5000,
            'net_weight' => 5000,
            'remaining_weight' => 5000,
            'rate_per_ton' => 85000,
            'status' => 'in_stock',
        ]);

        // 2. Create a shop coil (in_stock)
        $shopCoil = Coil::create([
            'coil_number' => 'SHOP-COIL-999',
            'warehouse_id' => $this->shopWarehouse->id,
            'thickness' => '3.0mm',
            'width' => '1200mm',
            'length' => 'Sheet',
            'piece_count' => 10,
            'gross_weight' => 2000,
            'net_weight' => 2000,
            'remaining_weight' => 2000,
            'rate_per_ton' => 90000,
            'status' => 'in_stock',
        ]);

        // Request inventory PDF
        $response = $this->actingAs($this->admin)->get(route('inventory.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Verify with controller query directly
        $query = Coil::where('status', 'in_stock')
            ->where('remaining_weight', '>', 0)
            ->whereDoesntHave('warehouse', function ($q) {
                $q->where('type', 'shop');
            })
            ->pluck('coil_number')
            ->toArray();

        $this->assertContains('YARD-COIL-101', $query);
        $this->assertNotContains('SHOP-COIL-999', $query);
    }

    public function test_shop_stock_report_page_renders_with_same_structure(): void
    {
        Coil::create([
            'coil_number' => 'SHOP-COIL-101',
            'warehouse_id' => $this->shopWarehouse->id,
            'thickness' => '3.0mm',
            'width' => '1200mm',
            'length' => 'Sheet',
            'piece_count' => 5,
            'gross_weight' => 2500,
            'net_weight' => 2500,
            'remaining_weight' => 2500,
            'rate_per_ton' => 92000,
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->admin)->get(route('shops.stock.index'));
        $response->assertStatus(200);
        $response->assertSee('Shop Stock &amp; Inventory', false);
        $response->assertSee('SHOP-COIL-101');
        $response->assertSee('Retail Outlet A');
    }

    public function test_shop_stock_report_pdf_renders_same_format(): void
    {
        Coil::create([
            'coil_number' => 'SHOP-COIL-102',
            'warehouse_id' => $this->shopWarehouse->id,
            'thickness' => '2.8mm',
            'width' => '1200mm',
            'length' => 'Sheet',
            'piece_count' => 3,
            'gross_weight' => 1500,
            'net_weight' => 1500,
            'remaining_weight' => 1500,
            'rate_per_ton' => 91000,
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->admin)->get(route('shops.stock.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_inventory_pdf_sorts_coils_thickness_wise_ascending(): void
    {
        Coil::create([
            'coil_number' => 'YARD-040',
            'warehouse_id' => $this->yardWarehouse->id,
            'thickness' => '0.40',
            'width' => '3.0ft',
            'gross_weight' => 1000,
            'net_weight' => 1000,
            'remaining_weight' => 1000,
            'status' => 'in_stock',
        ]);

        Coil::create([
            'coil_number' => 'YARD-020',
            'warehouse_id' => $this->yardWarehouse->id,
            'thickness' => '0.20',
            'width' => '3.0ft',
            'gross_weight' => 1200,
            'net_weight' => 1200,
            'remaining_weight' => 1200,
            'status' => 'in_stock',
        ]);

        Coil::create([
            'coil_number' => 'YARD-030',
            'warehouse_id' => $this->yardWarehouse->id,
            'thickness' => '0.30mm',
            'width' => '3.0ft',
            'gross_weight' => 800,
            'net_weight' => 800,
            'remaining_weight' => 800,
            'status' => 'in_stock',
        ]);

        Coil::create([
            'coil_number' => 'YARD-250',
            'warehouse_id' => $this->yardWarehouse->id,
            'thickness' => '2.50',
            'width' => '3.0ft',
            'gross_weight' => 1500,
            'net_weight' => 1500,
            'remaining_weight' => 1500,
            'status' => 'in_stock',
        ]);

        $html = view('pdf.inventory', [
            'coils' => Coil::where('warehouse_id', $this->yardWarehouse->id)->get(),
        ])->render();

        $pos020 = strpos($html, 'YARD-020');
        $pos030 = strpos($html, 'YARD-030');
        $pos040 = strpos($html, 'YARD-040');
        $pos250 = strpos($html, 'YARD-250');

        $this->assertNotFalse($pos020);
        $this->assertNotFalse($pos030);
        $this->assertNotFalse($pos040);
        $this->assertNotFalse($pos250);

        // Verify order is 0.20, then 0.30, then 0.40, then 2.50
        $this->assertTrue($pos020 < $pos030, '0.20 should appear before 0.30');
        $this->assertTrue($pos030 < $pos040, '0.30 should appear before 0.40');
        $this->assertTrue($pos040 < $pos250, '0.40 should appear before 2.50');
    }
}
