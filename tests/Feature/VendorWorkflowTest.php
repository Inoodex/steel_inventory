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
}
