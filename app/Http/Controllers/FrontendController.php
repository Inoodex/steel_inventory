<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\DailyExpense;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Employee;
use App\Models\Coil;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\WorkerPayoutItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FrontendController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->hasRole('Employee')) {
            return view('frontend.pages.dashboard_employee', [
                'title' => 'Employee Dashboard',
                'data'  => [],
            ]);
        }

        $currentYear = date('Y');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Monthly Sales & Purchase Revenue (current year) - Grouped in 2 queries
        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? "strftime('%m', created_at)" : "MONTH(created_at)";
        $yearExpr  = $isSqlite ? "strftime('%Y', created_at)" : "YEAR(created_at)";

        $salesByMonth = Sale::whereRaw("{$yearExpr} = ?", [$currentYear])
            ->selectRaw("{$monthExpr} as m, SUM(payble) as total")
            ->groupBy('m')
            ->pluck('total', 'm');

        $purchasesByMonth = Purchase::whereRaw("{$yearExpr} = ?", [$currentYear])
            ->selectRaw("{$monthExpr} as m, SUM(total_price) as total")
            ->groupBy('m')
            ->pluck('total', 'm');

        $monthlySales = [];
        $monthlyPurchases = [];
        foreach ($months as $key => $monthName) {
            $mNum = $key + 1;
            $mKey = str_pad((string)$mNum, 2, '0', STR_PAD_LEFT);
            $monthlySales[$monthName] = (float) ($salesByMonth->get($mNum) ?? $salesByMonth->get($mKey) ?? 0.0);
            $monthlyPurchases[$monthName] = (float) ($purchasesByMonth->get($mNum) ?? $purchasesByMonth->get($mKey) ?? 0.0);
        }

        // Yearly Sales Revenue (last 6 years) - Grouped in 1 query
        $currentYearInt = (int) $currentYear;
        $startYear = $currentYearInt - 5;
        $salesByYear = Sale::whereRaw("{$yearExpr} >= ?", [$startYear])
            ->selectRaw("{$yearExpr} as yr, SUM(payble) as total")
            ->groupBy('yr')
            ->pluck('total', 'yr');

        $yearlyRev = [];
        for ($i = 5; $i >= 0; $i--) {
            $yr = $currentYearInt - $i;
            $yearlyRev[$yr] = (float) ($salesByYear->get((string)$yr) ?? $salesByYear->get($yr) ?? 0.0);
        }

        // Accounting & Financial Balances
        $today = date('Y-m-d');
        $liquidCash          = getAccountBalance('1110', $today);
        $receivables         = getAccountBalance('1130', $today);
        $inventoryValuation  = getAccountBalance('1140', $today);
        $payables            = getAccountBalance('2110', $today);

        // Bank Balance
        $bankAccountParent = ChartOfAccount::where('account_code', '1120')->first();
        $bankBalance  = 0.00;
        $bankAccounts = collect();
        if ($bankAccountParent) {
            $bankAccounts = ChartOfAccount::where('parent_id', $bankAccountParent->id)->get();
            if ($bankAccounts->count() > 0) {
                $balances = ChartOfAccount::batchCalculateBalances($today);
                foreach ($bankAccounts as $b) {
                    $b->balance = $balances[$b->id] ?? 0.0;
                    $bankBalance += $b->balance;
                }
            } else {
                $bankBalance = $bankAccountParent->calculateBalance($today);
            }
        }

        // Steel Yard & Stock Summary
        $inStockCoilCount   = Coil::where('status', 'in_stock')->count();
        $totalYardWeightKg  = (float) Coil::where('status', 'in_stock')->sum('remaining_weight');
        $totalYardTonnage   = $totalYardWeightKg / 1000;
        $stockValuation     = (float) Coil::where('status', 'in_stock')->sum('total_price');

        // Procurement & Operations
        $totalLots          = Lot::count();
        $totalWarehouses    = Warehouse::count();
        $totalVendors       = Vendor::count();
        $totalCustomers     = Customer::count();
        $totalEmployees     = Employee::count();

        // Recent Operations Lists
        $recentSales = Sale::with('customer')->latest()->take(6)->get();
        $recentCoils = Coil::with(['lot', 'warehouse'])->where('status', 'in_stock')->latest()->take(6)->get();
        $recentJournalEntries = JournalEntry::with('creator')->latest('entry_date')->latest('id')->take(6)->get();

        // Worker Extra Charges & Payouts Liability
        $chargesSum = Sale::selectRaw('SUM(labour_cost) as lab, SUM(delivery_charge) as del, SUM(weight_scale_cost) as scale, SUM(other_charges) as other')->first();
        $totalCollectedCharges = (float) (($chargesSum->lab ?? 0) + ($chargesSum->del ?? 0) + ($chargesSum->scale ?? 0) + ($chargesSum->other ?? 0));
        $totalPaidCharges = Schema::hasTable('worker_payout_items')
            ? (float) WorkerPayoutItem::sum('amount')
            : 0;
        $pendingWorkerCharges = max(0, $totalCollectedCharges - $totalPaidCharges);

        $stats = [
            // Sales KPIs
            'todaysSalesRevenue'     => (float) Sale::whereDate('created_at', Carbon::today())->sum('payble'),
            'thisWeeksSalesRevenue'  => (float) Sale::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('payble'),
            'thisMonthsSalesRevenue' => (float) Sale::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('payble'),
            'thisYearsSalesRevenue'  => (float) Sale::whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])->sum('payble'),

            // Purchase KPIs
            'todaysPurchaseRevenue'     => (float) Purchase::whereDate('created_at', Carbon::today())->sum('total_price'),
            'thisWeeksPurchaseRevenue'  => (float) Purchase::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total_price'),
            'thisMonthsPurchaseRevenue' => (float) Purchase::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('total_price'),
            'thisYearsPurchaseRevenue'  => (float) Purchase::whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])->sum('total_price'),

            // Expense KPIs
            'todaysExpense'     => (float) DailyExpense::whereDate('date', Carbon::today())->sum('amount'),
            'thisWeeksExpense'  => (float) DailyExpense::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('amount'),
            'thisMonthsExpense' => (float) DailyExpense::whereBetween('date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount'),
            'thisYearsExpense'  => (float) DailyExpense::whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])->sum('amount'),

            // Entity Counts
            'totalCustomers'    => $totalCustomers,
            'totalVendors'      => $totalVendors,
            'totalEmployees'    => $totalEmployees,
            'totalLots'         => $totalLots,
            'totalWarehouses'   => $totalWarehouses,

            // Charts
            'monthlyRevenue'    => $monthlySales,
            'monthlyPurchases'  => $monthlyPurchases,
            'yearlyRevenue'     => $yearlyRev,

            // Accounting
            'liquidCash'           => $liquidCash,
            'bankBalance'          => $bankBalance,
            'receivables'          => $receivables,
            'payables'             => $payables,
            'pendingWorkerCharges' => $pendingWorkerCharges,
            'inventoryValuation'   => $inventoryValuation,
            'bankAccounts'         => $bankAccounts,
            'recentJournalEntries' => $recentJournalEntries,

            // Ship Steel Yard
            'totalYardTonnage'  => $totalYardTonnage,
            'totalYardWeightKg' => $totalYardWeightKg,
            'inStockCoilCount'  => $inStockCoilCount,
            'stockValuation'    => $stockValuation,
            'recentSales'       => $recentSales,
            'recentCoils'       => $recentCoils,
        ];

        return view('frontend.pages.dashboard', $stats);
    }
}
