<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Lot;
use App\Models\Purchase;
use App\Models\Coil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vendor $vendor;
    private Warehouse $warehouse;
    private Lot $lot;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Procurement Officer',
            'email' => 'buyer@steel.test',
            'password' => Hash::make('secret123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);

        $this->vendor = Vendor::create([
            'name' => 'Shipbreakers Ltd',
            'company' => 'Shipbreakers Ltd',
            'phone' => '01711111111',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Chittagong Yard A',
            'location' => 'Sitakunda',
            'status' => 'active',
        ]);

        $this->lot = Lot::create([
            'lot_number' => 'LOT-2026-001',
            'vendor_id' => $this->vendor->id,
            'status' => 'active',
        ]);
    }

    public function test_purchase_creation_and_coil_inventory(): void
    {
        $purchase = Purchase::create([
            'lot_id' => $this->lot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness' => '2.50',
            'size' => '1250',
            'size_type' => 'mm',
            'unit_weight' => 5.200,
            'total_weight' => 5.200,
            'quantity' => 1,
            'unit_price' => 85000.00,
            'sub_price' => 442000.00,
            'total_price' => 442000.00,
            'payment' => 400000.00,
            'due' => 42000.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        $coil = Coil::create([
            'coil_number' => 'COIL-001-A',
            'purchase_id' => $purchase->id,
            'lot_id' => $this->lot->id,
            'vendor_id' => $this->vendor->id,
            'warehouse_id' => $this->warehouse->id,
            'thickness' => 2.50,
            'width' => 1250.00,
            'gross_weight' => 5.200,
            'tare_weight' => 0.000,
            'net_weight' => 5.200,
            'remaining_weight' => 5.200,
            'rate_per_ton' => 85000.00,
            'total_price' => 442000.00,
            'status' => 'in_stock',
            'created_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'total_price' => 442000.00,
            'due' => 42000.00,
        ]);

        $this->assertDatabaseHas('coils', [
            'id' => $coil->id,
            'status' => 'in_stock',
            'remaining_weight' => 5.200,
        ]);

        $this->assertEquals($purchase->id, $coil->purchase->id);
    }
}
