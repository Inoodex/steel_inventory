<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class TrialBalanceController extends Controller
{
    public function index(Request $request)
    {
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));

        $accounts = ChartOfAccount::active()
            ->orderBy('account_code')
            ->get();

        $rows = [];
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($accounts as $account) {
            $balance = $account->calculateBalance($asOfDate);

            if (abs($balance) > 0.001) {
                if ($account->isDebitNormal()) {
                    $debit = $balance >= 0 ? $balance : 0;
                    $credit = $balance < 0 ? abs($balance) : 0;
                } else {
                    $debit = $balance < 0 ? abs($balance) : 0;
                    $credit = $balance >= 0 ? $balance : 0;
                }

                $rows[] = [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                ];

                $totalDebit += $debit;
                $totalCredit += $credit;
            }
        }

        $isBalanced = abs($totalDebit - $totalCredit) < 0.01;

        return view('frontend.pages.accounts.reports.trial-balance', compact(
            'rows',
            'totalDebit',
            'totalCredit',
            'isBalanced',
            'asOfDate'
        ));
    }

    public function downloadPdf(Request $request)
    {
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));

        $accounts = ChartOfAccount::active()->orderBy('account_code')->get();

        $rows = [];
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($accounts as $account) {
            $balance = $account->calculateBalance($asOfDate);

            if (abs($balance) > 0.001) {
                if ($account->isDebitNormal()) {
                    $debit = $balance >= 0 ? $balance : 0;
                    $credit = $balance < 0 ? abs($balance) : 0;
                } else {
                    $debit = $balance < 0 ? abs($balance) : 0;
                    $credit = $balance >= 0 ? $balance : 0;
                }

                $rows[] = [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                ];

                $totalDebit += $debit;
                $totalCredit += $credit;
            }
        }

        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';

        $html = view('pdf.accounts.trial-balance', compact(
            'rows',
            'totalDebit',
            'totalCredit',
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

        $pdfContent = $mpdf->Output("Trial-Balance-{$asOfDate}.pdf", 'S');
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"Trial-Balance-{$asOfDate}.pdf\"",
        ]);
    }

    /**
     * Export Trial Balance to CSV/Excel.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));
        $accounts = ChartOfAccount::active()->orderBy('account_code')->get();

        $rows = [];
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($accounts as $account) {
            $balance = $account->calculateBalance($asOfDate);
            if (abs($balance) > 0.001) {
                if ($account->isDebitNormal()) {
                    $debit = $balance >= 0 ? $balance : 0;
                    $credit = $balance < 0 ? abs($balance) : 0;
                } else {
                    $debit = $balance < 0 ? abs($balance) : 0;
                    $credit = $balance >= 0 ? $balance : 0;
                }

                $rows[] = [
                    'code' => $account->account_code,
                    'name' => $account->account_name,
                    'type' => ucfirst($account->account_type),
                    'debit' => $debit,
                    'credit' => $credit,
                ];

                $totalDebit += $debit;
                $totalCredit += $credit;
            }
        }

        $filename = "Trial_Balance_{$asOfDate}.csv";

        return response()->stream(function () use ($rows, $totalDebit, $totalCredit, $asOfDate) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Trial Balance Summary Report']);
            fputcsv($handle, ["As of Date: {$asOfDate}"]);
            fputcsv($handle, []);

            fputcsv($handle, ['Account Code', 'Account Name', 'Account Type', 'Debit Balance (৳)', 'Credit Balance (৳)']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['code'],
                    $row['name'],
                    $row['type'],
                    $row['debit'] > 0 ? number_format($row['debit'], 2, '.', '') : '',
                    $row['credit'] > 0 ? number_format($row['credit'], 2, '.', '') : ''
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['TOTALS', '', '', number_format($totalDebit, 2, '.', ''), number_format($totalCredit, 2, '.', '')]);
            fputcsv($handle, ['EQUILIBRIUM STATUS', '', '', abs($totalDebit - $totalCredit) < 0.01 ? 'PERFECTLY BALANCED' : 'OUT OF BALANCE', '']);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
