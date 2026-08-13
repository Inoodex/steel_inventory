<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->hasRole('Employee')) {
            return view('frontend.pages.dashboard_employee', [
                'title' => 'Employee Dashboard',
                'data'  => [],
            ]);
        }

        $currentYear = date('Y');
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Monthly Sales & Purchase Revenue (current year)
        $monthlySales = [];
        $monthlyPurchases = [];
        foreach ($months as $key => $monthName) {
            $monthNumber = $key + 1;
            $monthlySales[$monthName] = (float) Sale::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $monthNumber)
                ->sum('payble');
            $monthlyPurchases[$monthName] = (float) Purchase::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $monthNumber)
                ->sum('total_price');
        }

        // Yearly Sales Revenue (last 6 years)
        $currentYearInt = (int) $currentYear;
        $yearlyRev = [];
        for ($i = 5; $i >= 0; $i--) {
            $yr = $currentYearInt - $i;
            $yearlyRev[$yr] = (float) Sale::whereYear('created_at', $yr)->sum('payble');
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
                foreach ($bankAccounts as $b) {
                    $b->balance = $b->calculateBalance($today);
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
