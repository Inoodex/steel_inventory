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
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SaleWorkflowTest extends TestCase
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
            'name' => 'Sales Officer',
            'email' => 'sales@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->customer = Customer::create([
            'name' => 'Standard Steel Fabricators',
            'company' => 'Standard Group',
            'phone' => '01912345678',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Dhaka Depot',
            'location' => 'Tejgaon',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Global Steels',
            'company' => 'Global Steels Ltd',
            'phone' => '01611111111',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->lot = Lot::create([
            'lot_number' => 'LOT-2026-002',
            'vendor_id' => $this->vendor->id,
            'status' => 'active',
        ]);

        $this->coil = Coil::create([
            'coil_number' => 'COIL-DH-01',
            'lot_id' => $this->lot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness' => 3.00,
            'width' => 1200.00,
            'gross_weight' => 10.000,
            'tare_weight' => 0.000,
            'net_weight' => 10.000,
            'remaining_weight' => 10.000,
            'rate_per_ton' => 90000.00,
            'total_price' => 900000.00,
            'status' => 'in_stock',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_sale_creation_and_customer_balance_impact(): void
    {
        $sale = Sale::create([
            'order_no' => 'INV-202610-0001',
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'qty' => 4.000,
            'subtotal' => 400000.00,
            'total' => 400000.00,
            'payble' => 400000.00,
            'bill' => 400000.00,
            'discount' => 0.00,
            'advanced_payment' => 150000.00,
            'due_payment' => 250000.00,
            'payment_method' => 'cash',
            'sales_by' => $this->admin->id,
            'status' => 'partial',
            'warehouse_id' => $this->warehouse->id,
            'delivery_status' => 'delivered',
        ]);

        SalesItem::create([
            'order_id' => $sale->id,
            'coil_id' => $this->coil->id,
            'lot_id' => $this->lot->id,
            'unit_price' => 100000.00,
            'qty' => 4.000,
            'total_price' => 400000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Payment::create([
            'customer_id' => $this->customer->id,
            'sale_id' => $sale->id,
            'amount' => 150000.00,
            'payment_date' => now()->toDateString(),
            'payment_type' => 'customer',
            'method' => 'cash',
        ]);

        $this->customer->refresh();

        $this->assertEquals(250000.00, $this->customer->sales_due);
        $this->assertEquals(250000.00, $this->customer->effective_due);
        $this->assertEquals(0.00, $this->customer->advance_credit);
    }
}
