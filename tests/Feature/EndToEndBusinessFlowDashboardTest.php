<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Lot;
use App\Models\Coil;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\DailyExpense;
use App\Models\ExpenseCategory;
use App\Models\BankDetail;
use App\Models\ChartOfAccount;
use App\Models\WorkerPayout;
use App\Models\WorkerPayoutItem;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\PaymentController;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class EndToEndBusinessFlowDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $centralYard;
    private Warehouse $retailShop;
    private Vendor $vendorA;
    private Vendor $vendorB;
    private Customer $customerA;
    private Customer $customerB;
    private BankDetail $bankABC;
    private BankDetail $bankBkash;
    private ExpenseCategory $expenseCat;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Admin
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::create([
            'name' => 'Admin Controller',
            'email' => 'admin@steel.test',
            'password' => Hash::make('password123'),
            'status' => '1',
        ]);
        $this->admin->assignRole($role);
        Auth::login($this->admin);

        // 2. Chart of Accounts & Fiscal Year
        ChartOfAccount::ensureSystemAccounts();

        // 3. Setup Warehouses (Central Yard + Retail Shop)
        $this->centralYard = Warehouse::create([
            'name' => 'Sitakunda Central Yard',
            'type' => 'warehouse',
            'code' => 'WH-01',
            'location' => 'Sitakunda, Chittagong',
            'status' => 'active',
        ]);

        $this->retailShop = Warehouse::create([
            'name' => 'Agrabad Retail Outlet',
            'type' => 'shop',
            'code' => 'SH-01',
            'location' => 'Agrabad C/A',
            'status' => 'active',
        ]);

        // 4. Vendors
        $this->vendorA = Vendor::create([
            'name' => 'Apex Ship Recycling Ltd',
            'company' => 'Apex Group',
            'phone' => '01711000001',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Karnaphuli Steel Mills',
            'company' => 'Karnaphuli Steels',
            'phone' => '01711000002',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        // 5. Customers
        $this->customerA = Customer::create([
            'name' => 'Rahman Engineering Works',
            'company' => 'Rahman Works',
            'phone' => '01811000001',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        $this->customerB = Customer::create([
            'name' => 'Chittagong Builders Corp',
            'company' => 'Chittagong Builders',
            'phone' => '01811000002',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        // 6. Bank Accounts with initial balances
        $this->bankABC = BankDetail::create([
            'bank_name' => 'Dhaka Bank Corporate',
            'account_name' => 'Corporate Main Account',
            'account_number' => '100200300400',
            'account_type' => 'savings',
            'branch' => 'Principal Branch',
            'opening_balance' => 1000000.00, // ৳1,000,000 opening
            'current_balance' => 1000000.00,
            'is_active' => true,
        ]);
        $this->bankABC->resolveChartOfAccount();
        $this->bankABC->syncCurrentBalance();

        $this->bankBkash = BankDetail::create([
            'bank_name' => 'bKash Merchant',
            'account_name' => 'Merchant Primary Wallet',
            'account_number' => '01811999888',
            'account_type' => 'mfs',
            'branch' => 'Head Office',
            'opening_balance' => 200000.00, // ৳200,000 opening
            'current_balance' => 200000.00,
            'is_active' => true,
        ]);
        $this->bankBkash->resolveChartOfAccount();
        $this->bankBkash->syncCurrentBalance();

        // Initial Cash in Hand via journal opening voucher
        $cashAcc = ChartOfAccount::where('account_code', '1110')->first();
        $equityAcc = ChartOfAccount::where('account_code', '3100')->first();
        postJournalEntry([
            'entry_date' => date('Y-m-d'),
            'reference_type' => 'manual',
            'description' => 'Opening Cash Reserve',
            'items' => [
                ['account_id' => $cashAcc->id, 'debit' => 300000.00, 'credit' => 0.00],
                ['account_id' => $equityAcc->id, 'debit' => 0.00, 'credit' => 300000.00],
            ]
        ]);

        // 7. Expense Category
        $this->expenseCat = ExpenseCategory::create([
            'name' => 'Yard Utilities & Maintenance',
            'status' => 'active',
        ]);
    }

    private function getDashboardStats(): array
    {
        $ctrl = new FrontendController();
        $response = $ctrl->index();
        return $response->getData();
    }

    public function test_full_business_workflow_matches_dashboard_cards_exactly(): void
    {
        // STEP 0: Capture Initial Baseline Dashboard State
        $initial = $this->getDashboardStats();

        $this->assertEquals(0.0, $initial['todaysSalesRevenue']);
        $this->assertEquals(0.0, $initial['todaysPurchaseRevenue']);
        $this->assertEquals(0.0, $initial['todaysExpense']);
        $this->assertEquals(300000.00, $initial['liquidCash'], 'Initial Cash in Hand should be 300,000');
        $this->assertEquals(1200000.00, $initial['bankBalance'], 'Initial Total Bank Balance should be 1,200,000 (1M + 200K)');
        $this->assertEquals(0.0, $initial['receivables'], 'Initial Receivables should be 0');
        $this->assertEquals(0.0, $initial['payables'], 'Initial Payables should be 0');
        $this->assertEquals(0.0, $initial['pendingWorkerCharges'], 'Initial Worker Charges should be 0');
        $this->assertEquals(0, $initial['inStockCoilCount']);

        // STEP 1: NORMAL CENTRAL YARD PURCHASE (Ship Scrap Coils)
        // 2 Coils: 10,000 kg @ ৳80/kg = ৳800,000.
        // Paid via Bank (Dhaka Bank): ৳500,000.
        // Due to Vendor A: ৳300,000.
        $purchaseService = app(PurchaseService::class);
        $purchaseLot1 = Lot::create([
            'lot_number' => 'LOT-2026-YARD-01',
            'vendor_id' => $this->vendorA->id,
            'lot_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $batch1Result = $purchaseService->createPurchasesBatch([
            'lot_id' => $purchaseLot1->id,
            'vendor_id' => $this->vendorA->id,
            'warehouse_id' => $this->centralYard->id,
            'purchase_date' => date('Y-m-d'),
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bankABC->id,
            'payment' => 500000.00,
            'items' => [
                [
                    'coil_no' => 'CY-001',
                    'thickness' => '3.00',
                    'size' => '1250',
                    'quantity' => 1,
                    'unit_weight' => 5000.00,
                    'total_weight' => 5000.00,
                    'unit_price' => 80.00,
                    'sub_price' => 400000.00,
                ],
                [
                    'coil_no' => 'CY-002',
                    'thickness' => '3.50',
                    'size' => '1250',
                    'quantity' => 1,
                    'unit_weight' => 5000.00,
                    'total_weight' => 5000.00,
                    'unit_price' => 80.00,
                    'sub_price' => 400000.00,
                ]
            ]
        ]);

        // STEP 2: SHOP RETAIL PURCHASE (Direct Plates / Strips)
        // 1 Coil / Pack: 2,000 kg @ ৳85/kg = ৳170,000.
        // Paid via Cash: ৳100,000.
        // Due to Vendor B: ৳70,000.
        $shopLot = Lot::create([
            'lot_number' => 'LOT-2026-SHOP-01',
            'vendor_id' => $this->vendorB->id,
            'lot_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $batch2Result = $purchaseService->createPurchasesBatch([
            'lot_id' => $shopLot->id,
            'vendor_id' => $this->vendorB->id,
            'warehouse_id' => $this->retailShop->id,
            'purchase_date' => date('Y-m-d'),
            'payment_method' => 'cash',
            'payment' => 100000.00,
            'items' => [
                [
                    'coil_no' => 'SH-COIL-101',
                    'thickness' => '2.00',
                    'size' => '1000',
                    'quantity' => 1,
                    'unit_weight' => 2000.00,
                    'total_weight' => 2000.00,
                    'unit_price' => 85.00,
                    'sub_price' => 170000.00,
                ]
            ]
        ]);

        // STEP 3: NORMAL CENTRAL YARD SALE
        // Sell 4,000 kg from Yard Coil 1 @ ৳95/kg = ৳380,000.
        // Extra charges collected: Labour ৳3,000, Delivery ৳2,000 = ৳5,000.
        // Grand Total: ৳385,000.
        // Customer A pays via Bank (Dhaka Bank): ৳250,000.
        // Due from Customer A: ৳135,000.
        $saleService = app(SaleService::class);
        $coilCY1 = Coil::where('warehouse_id', $this->centralYard->id)->firstOrFail();

        $sale1 = $saleService->createSale([
            'client_type' => 'existing',
            'existing_client_id' => $this->customerA->id,
            'warehouse_id' => $this->centralYard->id,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bankABC->id,
            'advanced_payment' => 250000.00,
            'labour_cost' => 3000.00,
            'delivery_charge' => 2000.00,
            'transport_payer' => 'vendor', // customer is billed for delivery
            'coil_id' => [$coilCY1->id],
            'qty' => [4000.00],
            'unit_price' => [95.00],
        ]);

        // STEP 4: SHOP RETAIL SALE
        // Sell 1,000 kg from Shop Coil @ ৳100/kg = ৳100,000.
        // Customer B pays in Cash: ৳60,000.
        // Due from Customer B: ৳40,000.
        $coilShop = Coil::where('warehouse_id', $this->retailShop->id)->firstOrFail();

        $sale2 = $saleService->createSale([
            'client_type' => 'existing',
            'existing_client_id' => $this->customerB->id,
            'warehouse_id' => $this->retailShop->id,
            'order_date' => date('Y-m-d'),
            'payment_method' => 'cash',
            'advanced_payment' => 60000.00,
            'coil_id' => [$coilShop->id],
            'qty' => [1000.00],
            'unit_price' => [100.00],
        ]);

        // STEP 5: DAILY EXPENSES
        // Expense 1: Yard Crane Diesel & Maintenance = ৳25,000 (Paid from Dhaka Bank)
        // Expense 2: Tea & Office Refreshments = ৳5,000 (Paid from Cash in Hand)
        $exp1 = DailyExpense::create([
            'user_id' => $this->admin->id,
            'date' => date('Y-m-d'),
            'amount' => 25000.00,
            'spend_method' => 'bank',
            'bank_detail_id' => $this->bankABC->id,
            'remarks' => 'Crane Fuel & Maintenance',
            'expense_category_id' => $this->expenseCat->id,
        ]);
        (new \App\Http\Controllers\ExpenseController())->postExpenseJournal($exp1);

        $exp2 = DailyExpense::create([
            'user_id' => $this->admin->id,
            'date' => date('Y-m-d'),
            'amount' => 5000.00,
            'spend_method' => 'cash',
            'remarks' => 'Staff Tea and Refreshments',
            'expense_category_id' => $this->expenseCat->id,
        ]);
        (new \App\Http\Controllers\ExpenseController())->postExpenseJournal($exp2);

        // STEP 6: PAY VENDOR DUE (Vendor A)
        // Settle ৳50,000 of Vendor A's due from Dhaka Bank
        $payCtrl = new PaymentController();
        $vendorPaymentRequest = new \Illuminate\Http\Request([
            'vendor_id' => $this->vendorA->id,
            'amount' => 50000.00,
            'payment_method' => 'bank',
            'bank_detail_id' => $this->bankABC->id,
            'remarks' => 'Partial lot settlement to Apex Ship Recycling',
        ]);
        $payCtrl->addVendorPayment($vendorPaymentRequest);

        // STEP 7: RECEIVE CUSTOMER ADVANCE DEPOSIT
        // Customer A deposits an advance prepayment of ৳40,000 via bKash
        $advanceDepositRequest = new \Illuminate\Http\Request([
            'customer_id' => $this->customerA->id,
            'amount' => 40000.00,
            'payment_type' => 'advance_deposit',
            'payment_method' => 'mobile_banking',
            'bank_detail_id' => $this->bankBkash->id,
            'remarks' => 'Advance token deposit for next coil batch',
        ]);
        $payCtrl->payCustomerDue($advanceDepositRequest);

        // STEP 8: WORKER CHARGES PAYOUT (Labour Handler Settle)
        // Pay ৳3,000 labour charge to handler worker via Cash
        $cashAcc = ChartOfAccount::where('account_code', '1110')->first();
        $workerPayout = WorkerPayout::create([
            'payout_no' => 'WPO-' . date('Ymd') . '-001',
            'payout_date' => date('Y-m-d'),
            'charge_type' => 'labour',
            'recipient_name' => 'Kalam Labour Sardar',
            'recipient_phone' => '01819000111',
            'total_amount' => 3000.00,
            'payment_method' => 'cash',
            'payment_account_id' => $cashAcc->id,
            'notes' => 'Settlement for Sale 1 labour handling',
            'created_by' => $this->admin->id,
        ]);
        WorkerPayoutItem::create([
            'worker_payout_id' => $workerPayout->id,
            'sale_id' => $sale1->id,
            'charge_type' => 'labour',
            'amount' => 3000.00,
        ]);
        // Post Worker Payout journal: Debit 2140 Pass-Through Charges, Credit 1110 Cash
        $chargesAcc = ChartOfAccount::where('account_code', '2140')->first();
        postJournalEntry([
            'entry_date' => date('Y-m-d'),
            'reference_type' => 'worker_payout',
            'reference_id' => $workerPayout->id,
            'description' => "Worker labour payout to {$workerPayout->recipient_name}",
            'items' => [
                ['account_id' => $chargesAcc->id, 'debit' => 3000.00, 'credit' => 0.00],
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 3000.00],
            ]
        ]);

        // STEP 9: FETCH CURRENT DASHBOARD METRICS
        $current = $this->getDashboardStats();

        // -------------------------------------------------------------
        // MATHEMATICAL VERIFICATION & EXACT CARD MATCHING
        // -------------------------------------------------------------

        // 1. Sales Revenue Card:
        // Sale 1: ৳385,000 (380K goods + 5K extra charges)
        // Sale 2: ৳100,000
        // Expected Today's Sales = ৳485,000.00
        $expectedSales = 485000.00;
        $this->assertEquals($expectedSales, $current['todaysSalesRevenue'], 'Dashboard Todays Sales Revenue card mismatch');
        $this->assertEquals($expectedSales, $current['thisMonthsSalesRevenue'], 'Dashboard This Months Sales Revenue card mismatch');

        // 2. Purchase Revenue / Inward Card:
        // Batch 1 (Central Yard): ৳800,000.00
        // Batch 2 (Retail Shop):  ৳170,000.00
        // Expected Today's Purchase = ৳970,000.00
        $expectedPurchases = 970000.00;
        $this->assertEquals($expectedPurchases, $current['todaysPurchaseRevenue'], 'Dashboard Todays Purchase Revenue card mismatch');
        $this->assertEquals($expectedPurchases, $current['thisMonthsPurchaseRevenue'], 'Dashboard This Months Purchase Revenue card mismatch');

        // 3. Expense Card:
        // Expense 1: ৳25,000.00
        // Expense 2: ৳5,000.00
        // Expected Today's Expense = ৳30,000.00
        $expectedExpense = 30000.00;
        $this->assertEquals($expectedExpense, $current['todaysExpense'], 'Dashboard Todays Expense card mismatch');
        $this->assertEquals($expectedExpense, $current['thisMonthsExpense'], 'Dashboard This Months Expense card mismatch');

        // 4. Receivables Card (Customer Dues):
        // Sale 1 Customer A Due: ৳135,000.00
        // Sale 2 Customer B Due: ৳40,000.00
        // Customer A deposited advance prepayment of ৳40,000 (Credits AR / Customer ledger)
        // Net Customer Dues = (135,000 + 40,000) - 40,000 advance = ৳135,000.00
        // Or if calculated as gross invoice dues: ৳175,000.00
        // Let's verify receivables matches accounting reality:
        $expectedReceivables = 135000.00; // GL Account 1130 balance = 385K - 250K + 100K - 60K - 40K = 135K
        $this->assertEquals($expectedReceivables, $current['receivables'], 'Dashboard Customer Receivables card mismatch');

        // 5. Payables Card (Vendor Payables):
        // Purchase 1 Due to Vendor A: ৳300,000.00
        // Purchase 2 Due to Vendor B: ৳70,000.00
        // Vendor Payment to Vendor A: -৳50,000.00
        // Remaining Payables = 300,000 + 70,000 - 50,000 = ৳320,000.00
        $expectedPayables = 320000.00;
        $this->assertEquals($expectedPayables, $current['payables'], 'Dashboard Vendor Payables card mismatch');

        // 6. Cash in Hand Card (Liquid Cash):
        // Initial:                                  +৳300,000.00
        // - Shop Purchase Cash Payment (Step 2):    -৳100,000.00
        // + Shop Sale Cash Payment (Step 4):        +৳60,000.00
        // - Daily Expense 2 Cash (Step 5):          -৳5,000.00
        // - Worker Payout Cash (Step 8):            -৳3,000.00
        // Expected Liquid Cash = 300,000 - 100,000 + 60,000 - 5,000 - 3,000 = ৳252,000.00
        $expectedLiquidCash = 252000.00;
        $this->assertEquals($expectedLiquidCash, $current['liquidCash'], 'Dashboard Liquid Cash card mismatch');

        // 7. Total Bank Balance Card:
        // Initial Dhaka Bank + bKash:               +৳1,200,000.00
        // - Yard Purchase Bank Payment (Step 1):    -৳500,000.00
        // + Yard Sale Bank Payment (Step 3):        +৳250,000.00
        // - Daily Expense 1 Bank (Step 5):          -৳25,000.00
        // - Vendor A Due Payment (Step 6):          -৳50,000.00
        // + Customer A Advance Deposit (Step 7):    +৳40,000.00
        // Expected Bank Balance = 1,200,000 - 500,000 + 250,000 - 25,000 - 50,000 + 40,000 = ৳915,000.00
        $expectedBankBalance = 915000.00;
        $this->assertEquals($expectedBankBalance, $current['bankBalance'], 'Dashboard Bank Balance card mismatch');

        // Verify individual Bank Balances:
        $this->bankABC->refresh();
        $this->bankBkash->refresh();
        // Dhaka Bank: 1,000,000 - 500,000 + 250,000 - 25,000 - 50,000 = ৳675,000.00
        $this->assertEquals(675000.00, (float)$this->bankABC->current_balance);
        // bKash: 200,000 + 40,000 = ৳240,000.00
        $this->assertEquals(240000.00, (float)$this->bankBkash->current_balance);
        $this->assertEquals($expectedBankBalance, (float)$this->bankABC->current_balance + (float)$this->bankBkash->current_balance);

        // 8. Pending Worker Charges Alert Card:
        // Total Collected Extra Charges (Sale 1):    +৳5,000.00
        // Total Paid to Labour Handler (Step 8):     -৳3,000.00
        // Expected Pending Worker Charges = ৳2,000.00
        $expectedWorkerCharges = 2000.00;
        $this->assertEquals($expectedWorkerCharges, $current['pendingWorkerCharges'], 'Dashboard Pending Worker Charges card mismatch');

        // 9. Steel Yard Inventory & Stock Valuation:
        // Coils Purchased: 3 coils (CY-001: 5,000kg, CY-002: 5,000kg, SH-COIL-101: 2,000kg) = 12,000 kg total
        // Coils Sold/Reduced:
        // CY-001: 5,000 - 4,000 = 1,000 kg remaining (in stock)
        // CY-002: 5,000 kg remaining (in stock)
        // SH-COIL-101: 2,000 - 1,000 = 1,000 kg remaining (in stock)
        // In-stock coil count = 3
        // Total remaining yard weight = 1,000 + 5,000 + 1,000 = 7,000 kg = 7.00 MT
        $this->assertEquals(3, $current['inStockCoilCount']);
        $this->assertEquals(7000.00, $current['totalYardWeightKg']);
        $this->assertEquals(7.00, $current['totalYardTonnage']);

        // Stock Valuation:
        // Sum of total_price for in_stock coils per FrontendController:
        // CY-001 (400,000) + CY-002 (400,000) + SH-COIL-101 (170,000) = ৳970,000.00
        $this->assertEquals(970000.00, $current['stockValuation']);
    }
}
