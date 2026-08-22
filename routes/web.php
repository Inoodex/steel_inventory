<?php

use App\Http\Controllers\{
    CustomerController, EmployeeController,
    EmployeeTaDaController, ExpenseCategoryController, ExpenseController,
    FrontendController, InventoryController, LotController,
    PurchaseController,
    RevenueController, RoleController, PermissionController,
    SalaryController, SalesController,
    TaDaController, UserController, VendorController, BankDetailController,
    CompanyDetailController, PaymentController, ReturnController,
    ChartOfAccountController, JournalEntryController, LedgerController,
    TrialBalanceController, FinancialStatementController, ContraEntryController,
    ReconciliationController, FiscalYearController, CoilController, WarehouseController
};

use Illuminate\Support\Facades\{Auth, Route};

Auth::routes(['register' => false, 'reset' => false, 'verify' => false]);
Route::get('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout.get');

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('index');

// 1. DASHBOARD + EMPLOYEE-ONLY ROUTES → accessible by Super Admin AND Employee
Route::middleware(['auth', 'role:Super Admin|Employee'])->group(function () {

    // Dashboard - accessible by both roles
    Route::get('/dashboard', [FrontendController::class, 'index'])->name('dashboard');

    // Employee TA/DA section (they need this too)
    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('tada', [EmployeeTaDaController::class, 'index'])->name('tada.index');
        Route::get('tada/create', [EmployeeTaDaController::class, 'create'])->name('tada.create');
        Route::post('tada/store', [EmployeeTaDaController::class, 'store'])->name('tada.store');
        Route::get('tada/{id}/edit', [EmployeeTaDaController::class, 'edit'])->name('tada.edit');
        Route::put('tada/{id}', [EmployeeTaDaController::class, 'update'])->name('tada.update');
    });
});

// 2. EVERYTHING ELSE → ONLY Super Admin
Route::middleware(['auth', 'role:Super Admin'])->group(function () {

    // === Administration ===
    Route::resource('users', UserController::class);
    Route::resource('role', RoleController::class);
    Route::resource('permission', PermissionController::class);
    Route::get('/user/pin', [UserController::class, 'pin'])->name('users.pin');
    Route::post('/user/pin', [UserController::class, 'pinStore'])->name('users.pin_store');
    Route::get('/inventory/pdf', [InventoryController::class, 'downloadPdf'])->name('inventory.pdf');
    Route::resource('inventory', InventoryController::class)->only(['index', 'show']);
    Route::resource('warehouses', WarehouseController::class);

    // === Ship Steel Coils & Plates Registry ===
    Route::get('coils', [CoilController::class, 'index'])->name('coils.index');
    Route::post('coils/{id}/status', [CoilController::class, 'updateStatus'])->name('coils.update_status');

    Route::get('/customers/pdf', [CustomerController::class, 'downloadPdf'])->name('customers.pdf');
    Route::get('/customers/{id}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
    Route::get('/customers/{id}/ledger/pdf', [CustomerController::class, 'ledgerPdf'])->name('customers.ledger.pdf');
    Route::resource('customers', CustomerController::class);
    Route::get('/vendors/pdf', [VendorController::class, 'downloadPdf'])->name('vendors.pdf');
    Route::get('/vendors/{id}/ledger', [VendorController::class, 'ledger'])->name('vendors.ledger');
    Route::get('/vendors/{id}/ledger/pdf', [VendorController::class, 'ledgerPdf'])->name('vendors.ledger.pdf');
    Route::resource('vendors', VendorController::class);

    Route::post('lots/quick-store', [LotController::class, 'quickStore'])->name('lots.quick_store');
    Route::resource('lots', LotController::class)->except(['create', 'edit']);

    Route::resource('purchase', PurchaseController::class);
    Route::get('purchase/latest-price/{id}', [PurchaseController::class, 'getLatestPrice'])->name('purchase.latest_price');

    Route::resource('sales', SalesController::class);
    Route::get('sales/invoice/{id}', [SalesController::class, 'makeInvoice'])->name('sales.invoice');
    Route::get('sales/invoice/{id}/pdf', [SalesController::class, 'downloadInvoicePdf'])->name('sales.invoice.pdf');
    Route::get('/sales/payments/{saleId?}', [SalesController::class, 'payments'])->name('sales.payments');
    Route::get('sales/{id}/details', [SalesController::class, 'getSaleDetails'])->name('sales.details');

    // === HR (Super Admin only) ===
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{id}', [EmployeeController::class, 'show'])->name('employees.view');
    Route::resource('salary', SalaryController::class);
    Route::resource('daily-expenses', ExpenseController::class)->names('dailyExpenses');
    Route::resource('expense-categories', ExpenseCategoryController::class);

    Route::get('/employee/{id}/advance-sum-by-month', [EmployeeController::class, 'getAdvanceSumByMonth']);
    Route::get('/employee/{id}/advance-sum', [ExpenseController::class, 'getAdvanceSum']);

    // === Reports & Payments ===
    Route::get('purchase-report', [PurchaseController::class, 'reportIndex'])->name('purchase.report');
    Route::get('purchase/report', [PurchaseController::class, 'report'])->name('purchase.report.get');
    Route::get('purchase/report/pdf', [PurchaseController::class, 'reportPdf'])->name('purchase.report.pdf');
    Route::get('sales-report', [SalesController::class, 'report'])->name('sales.report');
    Route::get('sales-report/pdf', [SalesController::class, 'reportPdf'])->name('sales.report.pdf');
    Route::get('extra-charges-report', [SalesController::class, 'extraChargesReport'])->name('sales.extra-charges-report');
    Route::get('extra-charges-report/pdf', [SalesController::class, 'extraChargesReportPdf'])->name('sales.extra-charges-report.pdf');
    Route::post('extra-charges/{id}/payout', [SalesController::class, 'updateChargesPayoutStatus'])->name('sales.extra-charges.payout');
    Route::post('extra-charges/{id}/revert', [SalesController::class, 'revertChargesPayoutStatus'])->name('sales.extra-charges.revert');
    Route::get('/revenues/pdf', [RevenueController::class, 'downloadPdf'])->name('revenues.pdf');
    Route::get('/revenues', [RevenueController::class, 'index'])->name('revenues.index');
    Route::post('/revenues/generate', [RevenueController::class, 'generate'])->name('revenues.generate');
    Route::get('/revenues/export/{id}', [RevenueController::class, 'export'])->name('revenues.export');
    Route::get('/due-payments', [SalesController::class, 'duePayments'])->name('due-payments.index');
    Route::get('/due-payments/pdf', [SalesController::class, 'duePaymentsPdf'])->name('due-payments.pdf');
    Route::get('/vendor-due-payments', [PurchaseController::class, 'duePayments'])->name('vendor-due-payments.index');
    Route::get('/vendor-due-payments/pdf', [PurchaseController::class, 'duePaymentsPdf'])->name('vendor-due-payments.pdf');

    // Customer Sale Payments
    Route::post('/add-payment', [PaymentController::class, 'addPayment'])->name('add.payment');
    Route::delete('/delete-payment/{id}', [PaymentController::class, 'deletePayment'])->name('delete.payment');
    Route::post('/sales/process-payment', [SalesController::class, 'processPayment'])->name('sales.process-payment');
    Route::get('/sales/search-orders', [SalesController::class, 'searchOrders'])->name('sales.search-orders');

    // Vendor Due Payments & Disbursements
    Route::post('/vendor-payments', [PaymentController::class, 'addVendorPayment'])->name('vendor-payments.store');
    Route::delete('/vendor-payments/{id}', [PaymentController::class, 'deleteVendorPayment'])->name('vendor-payments.destroy');

    Route::resource('bank-details', BankDetailController::class);
    Route::post('bank-details/{bankDetail}/set-default', [BankDetailController::class, 'setDefault'])->name('bank-details.set-default');
    
    Route::resource('company-details', CompanyDetailController::class);
    Route::post('company-details/{companyDetail}/set-default', [CompanyDetailController::class, 'setDefault'])->name('company-details.set-default');

        Route::get('product-returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::get('product-returns/create', [ReturnController::class, 'create'])->name('returns.create');
        Route::post('product-returns', [ReturnController::class, 'store'])->name('returns.store');
        Route::get('product-returns/sale-items/{saleId}', [ReturnController::class, 'getSaleItems'])->name('returns.sale.items');
        Route::get('product-returns/{id}', [ReturnController::class, 'show'])->name('returns.show');
        Route::delete('product-returns/{id}', [ReturnController::class, 'destroy'])->name('returns.destroy');
        Route::patch('product-returns/{id}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
        Route::patch('product-returns/{id}/complete', [ReturnController::class, 'complete'])->name('returns.complete');
        Route::patch('product-returns/{id}/reject', [ReturnController::class, 'reject'])->name('returns.reject');

        // =========================================================================
        // DOUBLE-ENTRY ACCOUNTS & BOOKKEEPING (SUPER ADMIN)
        // =========================================================================
        Route::prefix('accounts')->group(function () {
            // Redirect legacy accounts dashboard to Main Unified Dashboard
            Route::get('dashboard', function () {
                return redirect('/');
            })->name('accounts.dashboard');

            // Chart of Accounts
            Route::resource('chart-of-accounts', ChartOfAccountController::class);

            // Journal Entries & Vouchers
            Route::get('journal-entries/csv', [JournalEntryController::class, 'exportCsv'])->name('journal-entries.csv');
            Route::get('journal-entries/{journalEntry}/pdf', [JournalEntryController::class, 'downloadPdf'])->name('journal-entries.pdf');
            Route::post('journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse');
            Route::resource('journal-entries', JournalEntryController::class)->except(['edit', 'update', 'destroy']);

            // General Ledger
            Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
            Route::get('ledger/pdf', [LedgerController::class, 'downloadPdf'])->name('ledger.pdf');
            Route::get('ledger/csv', [LedgerController::class, 'exportCsv'])->name('ledger.csv');

            // Trial Balance
            Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
            Route::get('trial-balance/pdf', [TrialBalanceController::class, 'downloadPdf'])->name('trial-balance.pdf');
            Route::get('trial-balance/csv', [TrialBalanceController::class, 'exportCsv'])->name('trial-balance.csv');

            // Financial Statements & Reports
            Route::get('reports/profit-loss', [FinancialStatementController::class, 'profitLoss'])->name('reports.profit-loss');
            Route::get('reports/profit-loss/pdf', [FinancialStatementController::class, 'profitLossPdf'])->name('reports.profit-loss.pdf');
            Route::get('reports/profit-loss/csv', [FinancialStatementController::class, 'profitLossCsv'])->name('reports.profit-loss.csv');
            Route::get('reports/balance-sheet', [FinancialStatementController::class, 'balanceSheet'])->name('reports.balance-sheet');
            Route::get('reports/balance-sheet/pdf', [FinancialStatementController::class, 'balanceSheetPdf'])->name('reports.balance-sheet.pdf');
            Route::get('reports/balance-sheet/csv', [FinancialStatementController::class, 'balanceSheetCsv'])->name('reports.balance-sheet.csv');
            Route::get('reports/cash-flow', [FinancialStatementController::class, 'cashFlow'])->name('reports.cash-flow');

            // Fiscal Years & Year-End Close
            Route::get('fiscal-years', [FiscalYearController::class, 'index'])->name('fiscal-years.index');
            Route::post('fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
            Route::post('fiscal-years/{fiscalYear}/set-active', [FiscalYearController::class, 'setActive'])->name('fiscal-years.set-active');
            Route::post('fiscal-years/{fiscalYear}/close', [FiscalYearController::class, 'closeYear'])->name('fiscal-years.close');
        });
});

