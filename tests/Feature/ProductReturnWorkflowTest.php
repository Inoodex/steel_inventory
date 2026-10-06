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
use App\Models\ReturnItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProductReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Warehouse $warehouse;
    private Vendor $vendor;
    private Lot $lot;
    private Coil $coil;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Returns Admin',
            'email' => 'returns@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->customer = Customer::create([
            'name' => 'Delta Engineering',
            'company' => 'Delta Group',
            'phone' => '01500000000',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Khulna Depot',
            'location' => 'Khulna',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name' => 'National Steel Co',
            'company' => 'National Steel',
            'phone' => '01400000000',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->lot = Lot::create([
            'lot_number' => 'LOT-2026-003',
            'vendor_id' => $this->vendor->id,
            'status' => 'active',
        ]);

        $this->coil = Coil::create([
            'coil_number' => 'COIL-KL-01',
            'lot_id' => $this->lot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness' => 4.00,
            'width' => 1500.00,
            'gross_weight' => 10.000,
            'tare_weight' => 0.000,
            'net_weight' => 10.000,
            'remaining_weight' => 6.000, // 4 tons were sold
            'rate_per_ton' => 80000.00,
            'total_price' => 800000.00,
            'status' => 'in_stock',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_product_return_status_helpers_and_calculations(): void
    {
        $sale = Sale::create([
            'order_no' => 'INV-202610-0002',
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'qty' => 4.000,
            'subtotal' => 320000.00,
            'total' => 320000.00,
            'payble' => 320000.00,
            'bill' => 320000.00,
            'discount' => 0.00,
            'advanced_payment' => 0.00,
            'due_payment' => 320000.00,
            'payment_method' => 'cash',
            'sales_by' => $this->admin->id,
            'status' => 'credit',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $salesItem = SalesItem::create([
            'order_id' => $sale->id,
            'coil_id' => $this->coil->id,
            'lot_id' => $this->lot->id,
            'unit_price' => 80000.00,
            'qty' => 4.000,
            'total_price' => 320000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $return = ProductReturn::create([
            'sale_id' => $sale->id,
            'customer_id' => $this->customer->id,
            'return_date' => now()->toDateString(),
            'total_refund_amount' => 80000.00,
            'status' => 'pending',
            'reason' => 'Defective sheet thickness',
        ]);

        ReturnItem::create([
            'return_id' => $return->id,
            'sales_item_id' => $salesItem->id,
            'quantity' => 1,
            'unit_price' => 80000.00,
            'total_price' => 80000.00,
            'condition' => 'good',
        ]);

        $this->assertTrue($return->isPending());
        $this->assertFalse($return->isApproved());
        $this->assertEquals(80000.00, $return->calculateTotalRefund());
    }
}
