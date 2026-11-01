<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Coil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SalesExecutiveRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $salesRole = Role::firstOrCreate(['name' => 'Sales Executive', 'guard_name' => 'web']);
        $salesPerm = Permission::firstOrCreate(['name' => 'Sales Only Access', 'guard_name' => 'web']);
        $salesRole->syncPermissions([$salesPerm]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->salesUser = User::create([
            'name' => 'Sales Officer',
            'email' => 'sales@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->salesUser->assignRole($salesRole);
    }

    public function test_sales_executive_can_access_regular_and_shop_sales_routes(): void
    {
        Warehouse::create([
            'name' => 'Main Shop',
            'type' => 'shop',
            'status' => 'active',
        ]);

        $this->actingAs($this->salesUser)
            ->get(route('sales.index'))
            ->assertStatus(200);

        $this->actingAs($this->salesUser)
            ->get(route('sales.create'))
            ->assertStatus(200);

        $this->actingAs($this->salesUser)
            ->get(route('shops.sales.index'))
            ->assertStatus(200);

        $this->actingAs($this->salesUser)
            ->get(route('shops.sales.create'))
            ->assertStatus(200);
    }

    public function test_sales_executive_lands_directly_on_sales_list_from_root_and_dashboard(): void
    {
        $this->actingAs($this->salesUser)
            ->get('/')
            ->assertRedirect(route('sales.index'));

        $this->actingAs($this->salesUser)
            ->get('/dashboard')
            ->assertRedirect(route('sales.index'));
    }

    public function test_sales_executive_is_strictly_forbidden_from_other_modules(): void
    {
        // 1. Procurement & Purchases
        $this->actingAs($this->salesUser)
            ->get(route('purchase.index'))
            ->assertStatus(403);

        // 2. Inventory & Warehouse
        $this->actingAs($this->salesUser)
            ->get(route('inventory.index'))
            ->assertStatus(403);

        // 3. Customer Directory
        $this->actingAs($this->salesUser)
            ->get(route('customers.index'))
            ->assertStatus(403);

        // 4. Regular Sales Returns
        $this->actingAs($this->salesUser)
            ->get(route('returns.index'))
            ->assertStatus(403);

        // 5. Shop Returns
        $this->actingAs($this->salesUser)
            ->get(route('shops.returns.index'))
            ->assertStatus(403);

        // 6. Shop Purchases
        $this->actingAs($this->salesUser)
            ->get(route('shops.purchases.index'))
            ->assertStatus(403);

        // 7. Shop Settings
        $this->actingAs($this->salesUser)
            ->get(route('shops.settings'))
            ->assertStatus(403);

        // 8. Accounts & Daily Expenses
        $this->actingAs($this->salesUser)
            ->get(route('dailyExpenses.index'))
            ->assertStatus(403);

        // 9. Administration
        $this->actingAs($this->salesUser)
            ->get(route('role.index'))
            ->assertStatus(403);
    }

    public function test_sales_executive_can_create_new_customer_during_sale(): void
    {
        $wh = Warehouse::create([
            'name' => 'Main Yard',
            'type' => 'warehouse',
            'status' => 'active',
        ]);

        $coil = Coil::create([
            'coil_number' => 'C-TEST-001',
            'gross_weight' => 1000,
            'tare_weight' => 0,
            'net_weight' => 1000,
            'remaining_weight' => 1000,
            'cost_per_kg' => 100,
            'warehouse_id' => $wh->id,
            'status' => 'in_stock',
        ]);

        $payload = [
            'client_type' => 'new',
            'name' => 'Brand New Customer',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'warehouse_id' => $wh->id,
            'delivery_status' => 'delivered',
            'coil_id' => [$coil->id],
            'lot_id' => [''],
            'thickness' => ['2.00'],
            'size' => ['4x8'],
            'size_type' => ['ft'],
            'custom_size' => ['4x8'],
            'qty' => [100],
            'unit_price' => [150],
            'subTotal' => 15000,
            'grandTotal' => 15000,
            'advanced_payment' => 15000,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($this->salesUser)
            ->from(route('sales.create'))
            ->post(route('sales.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', [
            'name' => 'Brand New Customer',
            'phone' => '01700000000',
        ]);
    }
}
