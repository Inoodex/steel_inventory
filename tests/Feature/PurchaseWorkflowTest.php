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

    public function test_purchase_batch_with_bank_payment_creates_journal_entry_and_decrements_bank_balance(): void
    {
        $bank = \App\Models\BankDetail::create([
            'bank_name'       => 'Islami Bank',
            'account_name'    => 'Steel Trading Operations',
            'account_number'  => '987654321',
            'account_type'    => 'Bank',
            'opening_balance' => 50000.00,
            'current_balance' => 50000.00,
            'is_default'      => true,
            'is_active'       => true,
        ]);

        $service = app(\App\Services\PurchaseService::class);

        $purchaseData = [
            'lot_id'         => $this->lot->id,
            'warehouse_id'   => $this->warehouse->id,
            'purchase_date'  => date('Y-m-d'),
            'payment_method' => 'bank',
            'bank_detail_id' => $bank->id,
            'total_payment'  => 10000.00,
            'items'          => [
                [
                    'vendor_id'    => $this->vendor->id,
                    'thickness'    => '3.00',
                    'size'         => '1500',
                    'size_type'    => 'mm',
                    'coil_qty'     => 1,
                    'unit_weight'  => 2.0,
                    'total_weight' => 2.0,
                    'unit_price'   => 50000.00,
                    'sub_price'    => 100000.00,
                ],
            ],
        ];

        $purchases = $service->createPurchasesBatch($purchaseData);
        $this->assertCount(1, $purchases);

        $primaryPurchase = $purchases[0];
        $this->assertEquals(100000.00, (float) $primaryPurchase->total_price);
        $this->assertEquals(10000.00, (float) $primaryPurchase->payment);
        $this->assertEquals(90000.00, (float) $primaryPurchase->due);

        // Verify Journal Entry was auto-posted
        $jv = \App\Models\JournalEntry::where('reference_type', 'purchase')
            ->where('reference_id', $primaryPurchase->id)
            ->with('items')
            ->first();

        $this->assertNotNull($jv, 'Journal voucher for purchase should be auto-posted');
        $this->assertEquals(100000.00, (float) $jv->total_debit);
        $this->assertEquals(100000.00, (float) $jv->total_credit);

        // Verify bank balance was properly decremented by 10,000 (from 50,000 to 40,000)
        $bank->refresh();
        $this->assertEquals(40000.00, (float) $bank->current_balance);
    }

    public function test_purchase_batch_with_hand_cash_creates_journal_entry_and_credits_cash_account(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);
        $service = app(\App\Services\PurchaseService::class);

        $cashAccount = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
        $this->assertNotNull($cashAccount);

        $purchaseData = [
            'vendor_id'       => $this->vendor->id,
            'warehouse_id'    => $this->warehouse->id,
            'lot_id'          => $this->lot->id,
            'purchase_date'   => now()->format('Y-m-d'),
            'payment'         => 15000.00,
            'payment_method'  => 'cash',
            'bank_detail_id'  => null,
            'items'           => [
                [
                    'vendor_id'    => $this->vendor->id,
                    'coil_number'  => 'COIL-CASH-001',
                    'thickness'    => '2.5',
                    'width'        => '1200',
                    'length'       => '3000',
                    'size'         => '1200',
                    'size_type'    => 'mm',
                    'coil_qty'     => 1,
                    'unit_weight'  => 1.0,
                    'total_weight' => 1.0,
                    'unit_price'   => 50000.00,
                    'sub_price'    => 50000.00,
                ],
            ],
        ];

        $purchases = $service->createPurchasesBatch($purchaseData);
        $this->assertCount(1, $purchases);
        $primaryPurchase = $purchases[0];

        $this->assertEquals(50000.00, (float) $primaryPurchase->total_price);
        $this->assertEquals(15000.00, (float) $primaryPurchase->payment);
        $this->assertEquals(35000.00, (float) $primaryPurchase->due);

        $jv = \App\Models\JournalEntry::where('reference_type', 'purchase')
            ->where('reference_id', $primaryPurchase->id)
            ->with('items')
            ->first();

        $this->assertNotNull($jv);
        $this->assertEquals(50000.00, (float) $jv->total_debit);
        $this->assertEquals(50000.00, (float) $jv->total_credit);

        // Credit to Cash in Hand [1110]
        $cashItem = $jv->items->firstWhere('account_id', $cashAccount->id);
        $this->assertNotNull($cashItem);
        $this->assertEquals(15000.00, (float) $cashItem->credit);
    }
}
