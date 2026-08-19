<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class FinancialStatementController extends Controller
{
    /**
     * Profit and Loss Statement (Income Statement)
     */
    public function profitLoss(Request $request)
    {
        $fromDate = $request->query('from_date', date('Y-01-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));

        // Revenues (Class 4000)
        $revenueAccounts = ChartOfAccount::active()
            ->byType('revenue')
            ->whereNotNull('parent_id')
            ->orderBy('account_code')
            ->get();

        $revenueData = [];
        $totalRevenue = 0.00;

        foreach ($revenueAccounts as $account) {
            $amount = $account->calculateBalance($toDate, $fromDate);
            if (abs($amount) > 0.001) {
                $revenueData[] = [
                    'account' => $account,
                    'amount' => $amount,
                ];
                $totalRevenue += $amount;
            }
        }

        // Expenses (Class 5000)
        $expenseAccounts = ChartOfAccount::active()
            ->byType('expense')
            ->whereNotNull('parent_id')
            ->orderBy('account_code')
            ->get();

        $expenseData = [];
        $totalExpense = 0.00;

        foreach ($expenseAccounts as $account) {
            $amount = $account->calculateBalance($toDate, $fromDate);
            if (abs($amount) > 0.001) {
                $expenseData[] = [
                    'account' => $account,
                    'amount' => $amount,
                ];
                $totalExpense += $amount;
            }
        }

        $netProfit = $totalRevenue - $totalExpense;

        return view('frontend.pages.accounts.reports.profit-loss', compact(
            'revenueData',
            'expenseData',
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'fromDate',
            'toDate'
        ));
    }

    public function profitLossPdf(Request $request)
    {
        $fromDate = $request->query('from_date', date('Y-01-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));

        $revenueAccounts = ChartOfAccount::active()->byType('revenue')->whereNotNull('parent_id')->get();
        $revenueData = [];
        $totalRevenue = 0.00;
        foreach ($revenueAccounts as $account) {
            $amount = $account->calculateBalance($toDate, $fromDate);
            if (abs($amount) > 0.001) {
                $revenueData[] = ['account' => $account, 'amount' => $amount];
                $totalRevenue += $amount;
            }
        }

        $expenseAccounts = ChartOfAccount::active()->byType('expense')->whereNotNull('parent_id')->get();
        $expenseData = [];
        $totalExpense = 0.00;
        foreach ($expenseAccounts as $account) {
            $amount = $account->calculateBalance($toDate, $fromDate);
            if (abs($amount) > 0.001) {
                $expenseData[] = ['account' => $account, 'amount' => $amount];
                $totalExpense += $amount;
            }
        }

        $netProfit = $totalRevenue - $totalExpense;

        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';

        $html = view('pdf.accounts.profit-loss', compact(
            'revenueData',
            'expenseData',
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'fromDate',
            'toDate',
            'padBase64'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
            'margin_top' => 45,
            'margin_bottom' => 25,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output("Profit-Loss-{$toDate}.pdf", 'S');
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"Profit-Loss-{$toDate}.pdf\"",
        ]);
    }

    /**
     * Balance Sheet (Statement of Financial Position)
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));

        // Assets (1000)
        $assetAccounts = ChartOfAccount::active()->byType('asset')->whereNotNull('parent_id')->get();
        $assetData = [];
        $totalAssets = 0.00;
        foreach ($assetAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $assetData[] = ['account' => $account, 'amount' => $amount];
                $totalAssets += $amount;
            }
        }

        // Liabilities (2000)
        $liabilityAccounts = ChartOfAccount::active()->byType('liability')->whereNotNull('parent_id')->get();
        $liabilityData = [];
        $totalLiabilities = 0.00;
        foreach ($liabilityAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $liabilityData[] = ['account' => $account, 'amount' => $amount];
                $totalLiabilities += $amount;
            }
        }

        // Equity (3000)
        $equityAccounts = ChartOfAccount::active()->byType('equity')->whereNotNull('parent_id')->get();
        $equityData = [];
        $totalEquity = 0.00;
        foreach ($equityAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $equityData[] = ['account' => $account, 'amount' => $amount];
                $totalEquity += $amount;
            }
        }

        // Net Earnings from Revenue - Expense for period
        $revTotal = 0.00;
        foreach (ChartOfAccount::active()->byType('revenue')->whereNotNull('parent_id')->get() as $a) {
            $revTotal += $a->calculateBalance($asOfDate);
        }
        $expTotal = 0.00;
        foreach (ChartOfAccount::active()->byType('expense')->whereNotNull('parent_id')->get() as $a) {
            $expTotal += $a->calculateBalance($asOfDate);
        }
        $currentEarnings = $revTotal - $expTotal;

        $totalEquityWithEarnings = $totalEquity + $currentEarnings;
        $totalLiabAndEquity = $totalLiabilities + $totalEquityWithEarnings;
        $isBalanced = abs($totalAssets - $totalLiabAndEquity) < 0.01;

        return view('frontend.pages.accounts.reports.balance-sheet', compact(
            'assetData',
            'liabilityData',
            'equityData',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'currentEarnings',
            'totalEquityWithEarnings',
            'totalLiabAndEquity',
            'isBalanced',
            'asOfDate'
        ));
    }

    public function balanceSheetPdf(Request $request)
    {
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));

        $assetAccounts = ChartOfAccount::active()->byType('asset')->whereNotNull('parent_id')->get();
        $assetData = [];
        $totalAssets = 0.00;
        foreach ($assetAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $assetData[] = ['account' => $account, 'amount' => $amount];
                $totalAssets += $amount;
            }
        }

        $liabilityAccounts = ChartOfAccount::active()->byType('liability')->whereNotNull('parent_id')->get();
        $liabilityData = [];
        $totalLiabilities = 0.00;
        foreach ($liabilityAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $liabilityData[] = ['account' => $account, 'amount' => $amount];
                $totalLiabilities += $amount;
            }
        }

        $equityAccounts = ChartOfAccount::active()->byType('equity')->whereNotNull('parent_id')->get();
        $equityData = [];
        $totalEquity = 0.00;
        foreach ($equityAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $equityData[] = ['account' => $account, 'amount' => $amount];
                $totalEquity += $amount;
            }
        }

        $revTotal = 0.00;
        foreach (ChartOfAccount::active()->byType('revenue')->whereNotNull('parent_id')->get() as $a) {
            $revTotal += $a->calculateBalance($asOfDate);
        }
        $expTotal = 0.00;
        foreach (ChartOfAccount::active()->byType('expense')->whereNotNull('parent_id')->get() as $a) {
            $expTotal += $a->calculateBalance($asOfDate);
        }
        $currentEarnings = $revTotal - $expTotal;
        $totalEquityWithEarnings = $totalEquity + $currentEarnings;
        $totalLiabAndEquity = $totalLiabilities + $totalEquityWithEarnings;

        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';

        $html = view('pdf.accounts.balance-sheet', compact(
            'assetData',
            'liabilityData',
            'equityData',
            'totalAssets',
            'totalLiabilities',
            'totalEquityWithEarnings',
            'currentEarnings',
            'totalLiabAndEquity',
            'asOfDate',
            'padBase64'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
            'margin_top' => 45,
            'margin_bottom' => 25,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output("Balance-Sheet-{$asOfDate}.pdf", 'S');
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"Balance-Sheet-{$asOfDate}.pdf\"",
        ]);
    }

    /**
     * Export Profit and Loss Statement to CSV/Excel.
     */
    public function profitLossCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $fromDate = $request->query('from_date', date('Y-01-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));

        $revenueAccounts = ChartOfAccount::active()->byType('revenue')->whereNotNull('parent_id')->orderBy('account_code')->get();
        $revenueData = [];
        $totalRevenue = 0.00;
        foreach ($revenueAccounts as $account) {
            $amount = $account->calculateBalance($toDate, $fromDate);
            if (abs($amount) > 0.001) {
                $revenueData[] = ['account' => $account, 'amount' => $amount];
                $totalRevenue += $amount;
            }
        }

        $expenseAccounts = ChartOfAccount::active()->byType('expense')->whereNotNull('parent_id')->orderBy('account_code')->get();
        $expenseData = [];
        $totalExpense = 0.00;
        foreach ($expenseAccounts as $account) {
            $amount = $account->calculateBalance($toDate, $fromDate);
            if (abs($amount) > 0.001) {
                $expenseData[] = ['account' => $account, 'amount' => $amount];
                $totalExpense += $amount;
            }
        }

        $netProfit = $totalRevenue - $totalExpense;
        $filename = "Profit_Loss_{$fromDate}_to_{$toDate}.csv";

        return response()->stream(function () use ($revenueData, $expenseData, $totalRevenue, $totalExpense, $netProfit, $fromDate, $toDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Profit and Loss Statement (Income Statement)']);
            fputcsv($handle, ["Period: {$fromDate} to {$toDate}"]);
            fputcsv($handle, []);

            fputcsv($handle, ['--- OPERATING REVENUES (Class 4000) ---']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Amount (৳)']);
            foreach ($revenueData as $rev) {
                fputcsv($handle, [$rev['account']->account_code, $rev['account']->account_name, number_format($rev['amount'], 2, '.', '')]);
            }
            fputcsv($handle, ['TOTAL REVENUE', '', number_format($totalRevenue, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['--- OPERATING EXPENSES (Class 5000) ---']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Amount (৳)']);
            foreach ($expenseData as $exp) {
                fputcsv($handle, [$exp['account']->account_code, $exp['account']->account_name, number_format($exp['amount'], 2, '.', '')]);
            }
            fputcsv($handle, ['TOTAL EXPENSE', '', number_format($totalExpense, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['NET OPERATING INCOME / (LOSS)', '', number_format($netProfit, 2, '.', '')]);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export Balance Sheet to CSV/Excel.
     */
    public function balanceSheetCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));

        // Assets
        $assetAccounts = ChartOfAccount::active()->byType('asset')->whereNotNull('parent_id')->get();
        $assetData = [];
        $totalAssets = 0.00;
        foreach ($assetAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $assetData[] = ['account' => $account, 'amount' => $amount];
                $totalAssets += $amount;
            }
        }

        // Liabilities
        $liabilityAccounts = ChartOfAccount::active()->byType('liability')->whereNotNull('parent_id')->get();
        $liabilityData = [];
        $totalLiabilities = 0.00;
        foreach ($liabilityAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $liabilityData[] = ['account' => $account, 'amount' => $amount];
                $totalLiabilities += $amount;
            }
        }

        // Equity
        $equityAccounts = ChartOfAccount::active()->byType('equity')->whereNotNull('parent_id')->get();
        $equityData = [];
        $totalEquity = 0.00;
        foreach ($equityAccounts as $account) {
            $amount = $account->calculateBalance($asOfDate);
            if (abs($amount) > 0.001) {
                $equityData[] = ['account' => $account, 'amount' => $amount];
                $totalEquity += $amount;
            }
        }

        $revTotal = 0.00;
        foreach (ChartOfAccount::active()->byType('revenue')->whereNotNull('parent_id')->get() as $a) {
            $revTotal += $a->calculateBalance($asOfDate);
        }
        $expTotal = 0.00;
        foreach (ChartOfAccount::active()->byType('expense')->whereNotNull('parent_id')->get() as $a) {
            $expTotal += $a->calculateBalance($asOfDate);
        }
        $currentEarnings = $revTotal - $expTotal;
        $totalEquityWithEarnings = $totalEquity + $currentEarnings;
        $totalLiabAndEquity = $totalLiabilities + $totalEquityWithEarnings;

        $filename = "Balance_Sheet_{$asOfDate}.csv";

        return response()->stream(function () use ($assetData, $liabilityData, $equityData, $totalAssets, $totalLiabilities, $totalEquity, $currentEarnings, $totalEquityWithEarnings, $totalLiabAndEquity, $asOfDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['Balance Sheet (Statement of Financial Position)']);
            fputcsv($handle, ["As of Date: {$asOfDate}"]);
            fputcsv($handle, []);

            fputcsv($handle, ['--- ASSETS (Class 1000) ---']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Balance (৳)']);
            foreach ($assetData as $a) {
                fputcsv($handle, [$a['account']->account_code, $a['account']->account_name, number_format($a['amount'], 2, '.', '')]);
            }
            fputcsv($handle, ['TOTAL ASSETS', '', number_format($totalAssets, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['--- LIABILITIES (Class 2000) ---']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Balance (৳)']);
            foreach ($liabilityData as $l) {
                fputcsv($handle, [$l['account']->account_code, $l['account']->account_name, number_format($l['amount'], 2, '.', '')]);
            }
            fputcsv($handle, ['TOTAL LIABILITIES', '', number_format($totalLiabilities, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['--- OWNER EQUITY (Class 3000) ---']);
            fputcsv($handle, ['Account Code', 'Account Name', 'Balance (৳)']);
            foreach ($equityData as $e) {
                fputcsv($handle, [$e['account']->account_code, $e['account']->account_name, number_format($e['amount'], 2, '.', '')]);
            }
            fputcsv($handle, ['Current Period Earnings / (Loss)', '', number_format($currentEarnings, 2, '.', '')]);
            fputcsv($handle, ['TOTAL EQUITY', '', number_format($totalEquityWithEarnings, 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['TOTAL LIABILITIES & EQUITY', '', number_format($totalLiabAndEquity, 2, '.', '')]);
            fputcsv($handle, ['ACCOUNTING EQUATION BALANCE', '', abs($totalAssets - $totalLiabAndEquity) < 0.01 ? 'PERFECTLY BALANCED' : 'OUT OF BALANCE']);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Cash Flow Statement (Direct Method)
     */
    public function cashFlow(Request $request)
    {
        $fromDate = $request->query('from_date', date('Y-01-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));

        // Liquid accounts (Cash in hand + Bank accounts)
        $liquidAccounts = ChartOfAccount::whereIn('account_code', ['1110', '1120'])
            ->orWhere('parent_id', function ($q) {
                $q->select('id')->from('chart_of_accounts')->where('account_code', '1120')->limit(1);
            })
            ->pluck('id');

        $openingCash = 0.00;
        foreach ($liquidAccounts as $accId) {
            $openingCash += getAccountBalance($accId, date('Y-m-d', strtotime($fromDate . ' -1 day')));
        }

        // Operating Inflows
        $inflows = \App\Models\JournalEntryItem::whereIn('account_id', $liquidAccounts)
            ->where('debit', '>', 0)
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->whereIn('status', ['posted', 'approved'])
                  ->whereBetween('entry_date', [$fromDate, $toDate])
                  ->where('reference_type', '!=', 'contra');
            })
            ->sum('debit');

        // Operating Outflows
        $outflows = \App\Models\JournalEntryItem::whereIn('account_id', $liquidAccounts)
            ->where('credit', '>', 0)
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->whereIn('status', ['posted', 'approved'])
                  ->whereBetween('entry_date', [$fromDate, $toDate])
                  ->where('reference_type', '!=', 'contra');
            })
            ->sum('credit');

        $netCashFlow = $inflows - $outflows;
        $closingCash = $openingCash + $netCashFlow;

        return view('frontend.pages.accounts.reports.cash-flow', compact(
            'openingCash',
            'inflows',
            'outflows',
            'netCashFlow',
            'closingCash',
            'fromDate',
            'toDate'
        ));
    }
}
