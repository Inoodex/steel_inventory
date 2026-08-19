<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntryItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerController extends Controller
{
    /**
     * Display the General Ledger and Sub-Ledger view.
     */
    public function index(Request $request)
    {
        $data = $this->getLedgerData($request);
        return view('frontend.pages.accounts.ledger.index', $data);
    }

    /**
     * Export General Ledger or Sub-Ledger to PDF.
     */
    public function downloadPdf(Request $request)
    {
        $data = $this->getLedgerData($request);
        $selectedAccount = $data['selectedAccount'];
        $selectedParty = $data['selectedParty'];
        $partyType = $data['partyType'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $openingBalance = $data['openingBalance'];
        $closingBalance = $data['closingBalance'];
        $ledgerItems = $data['ledgerItems'];

        if (!$selectedAccount) {
            return redirect()->route('ledger.index')->with('error', 'Please select an account to export PDF.');
        }

        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';

        $html = view('pdf.accounts.ledger', compact(
            'selectedAccount',
            'selectedParty',
            'partyType',
            'ledgerItems',
            'openingBalance',
            'closingBalance',
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

        $partyPrefix = $selectedParty ? "-{$data['partyName']}" : '';
        $pdfContent = $mpdf->Output("Ledger-{$selectedAccount->account_code}{$partyPrefix}.pdf", 'S');
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"Ledger-{$selectedAccount->account_code}{$partyPrefix}.pdf\"",
        ]);
    }

    /**
     * Export General Ledger or Sub-Ledger to CSV/Excel.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $data = $this->getLedgerData($request);
        $selectedAccount = $data['selectedAccount'];
        $selectedParty = $data['selectedParty'];
        $ledgerItems = $data['ledgerItems'];
        $openingBalance = $data['openingBalance'];
        $closingBalance = $data['closingBalance'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];

        $accTitle = $selectedAccount ? "[{$selectedAccount->account_code}] {$selectedAccount->account_name}" : 'General Ledger';
        $partyTitle = $selectedParty ? " - Party: {$selectedParty->name}" : '';
        $filename = 'Ledger_' . ($selectedAccount ? $selectedAccount->account_code : 'all') . '_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($selectedAccount, $selectedParty, $ledgerItems, $openingBalance, $closingBalance, $fromDate, $toDate, $accTitle, $partyTitle) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [$accTitle . $partyTitle]);
            fputcsv($handle, ["Period: {$fromDate} to {$toDate}"]);
            fputcsv($handle, ["Opening Balance", number_format($openingBalance, 2)]);
            fputcsv($handle, []); // empty row

            fputcsv($handle, ['Date', 'Journal / Voucher #', 'Reference Type', 'Description / Particulars', 'Debit (৳)', 'Credit (৳)', 'Running Balance (৳)']);

            $runningBal = $openingBalance;
            $isDebitNormal = $selectedAccount ? $selectedAccount->isDebitNormal() : true;

            foreach ($ledgerItems as $item) {
                $debit = (float) $item->debit;
                $credit = (float) $item->credit;

                if ($isDebitNormal) {
                    $runningBal += ($debit - $credit);
                } else {
                    $runningBal += ($credit - $debit);
                }

                $journal = $item->journalEntry;
                $date = $journal ? date('Y-m-d', strtotime($journal->entry_date)) : '';
                $voucherNo = $journal ? $journal->journal_no : 'N/A';
                $refType = $journal ? ucfirst(str_replace('_', ' ', $journal->reference_type)) : '';
                $desc = $item->description ?: ($journal ? $journal->description : '');

                fputcsv($handle, [
                    $date,
                    $voucherNo,
                    $refType,
                    $desc,
                    $debit > 0 ? number_format($debit, 2, '.', '') : '',
                    $credit > 0 ? number_format($credit, 2, '.', '') : '',
                    number_format($runningBal, 2, '.', '')
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', 'Closing Balance', '', '', number_format($closingBalance, 2, '.', '')]);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Shared helper to retrieve ledger query data with party sub-ledger filtering.
     */
    protected function getLedgerData(Request $request): array
    {
        $accountId = $request->query('account_id');
        $partyType = $request->query('party_type'); // 'customer' or 'vendor'
        $partyId = $request->query('party_id');
        $fromDate = $request->query('from_date', date('Y-01-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));

        $accounts = ChartOfAccount::active()->orderBy('account_code')->get();
        $customers = Customer::orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();

        $selectedAccount = null;
        $selectedParty = null;
        $partyName = null;

        if ($partyType === 'customer' && $partyId) {
            $selectedParty = Customer::find($partyId);
            $partyName = $selectedParty ? $selectedParty->name : null;
            if (!$accountId) {
                $arAcc = ChartOfAccount::where('account_code', '1130')->first();
                $accountId = $arAcc ? $arAcc->id : null;
            }
        } elseif ($partyType === 'vendor' && $partyId) {
            $selectedParty = Vendor::find($partyId);
            $partyName = $selectedParty ? $selectedParty->name : null;
            if (!$accountId) {
                $apAcc = ChartOfAccount::where('account_code', '2110')->first();
                $accountId = $apAcc ? $apAcc->id : null;
            }
        }

        $ledgerItems = collect();
        $openingBalance = 0.00;
        $closingBalance = 0.00;

        if ($accountId) {
            $selectedAccount = ChartOfAccount::findOrFail($accountId);

            // Calculate Opening Balance prior to fromDate
            $openingBalance = $this->calculatePartyOpeningBalance($selectedAccount, $fromDate, $partyType, $partyId, $partyName);

            // Query items in period
            $query = JournalEntryItem::with(['journalEntry', 'account'])
                ->where('account_id', $accountId)
                ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                    $q->whereIn('status', ['posted', 'approved'])
                      ->whereBetween('entry_date', [$fromDate, $toDate]);
                })
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
                ->orderBy('journal_entries.entry_date', 'asc')
                ->orderBy('journal_entries.id', 'asc')
                ->select('journal_entry_items.*');

            // Apply party sub-ledger filters
            if ($partyType === 'customer' && $partyId && $partyName) {
                $customerSaleIds = \App\Models\Sale::where('customer_id', $partyId)->pluck('id')->toArray();
                $customerPaymentIds = \App\Models\Payment::whereIn('sale_id', $customerSaleIds)->pluck('id')->toArray();
                $customerReturnIds = \App\Models\ProductReturn::where('customer_id', $partyId)->pluck('id')->toArray();

                $query->where(function ($q) use ($customerSaleIds, $customerPaymentIds, $customerReturnIds, $partyName) {
                    $q->where(function ($sub) use ($customerSaleIds) {
                        $sub->where('journal_entries.reference_type', 'sale')
                            ->whereIn('journal_entries.reference_id', $customerSaleIds);
                    })->orWhere(function ($sub) use ($customerPaymentIds) {
                        $sub->where('journal_entries.reference_type', 'payment')
                            ->whereIn('journal_entries.reference_id', $customerPaymentIds);
                    })->orWhere(function ($sub) use ($customerReturnIds) {
                        $sub->where('journal_entries.reference_type', 'return')
                            ->whereIn('journal_entries.reference_id', $customerReturnIds);
                    })->orWhere('journal_entries.description', 'like', "%{$partyName}%")
                      ->orWhere('journal_entry_items.description', 'like', "%{$partyName}%");
                });
            } elseif ($partyType === 'vendor' && $partyId && $partyName) {
                $vendorPurchaseIds = \App\Models\Purchase::where('vendor_id', $partyId)->pluck('id')->toArray();
                $vendorPaymentIds = \App\Models\Payment::whereIn('purchase_id', $vendorPurchaseIds)->pluck('id')->toArray();

                $query->where(function ($q) use ($vendorPurchaseIds, $vendorPaymentIds, $partyName) {
                    $q->where(function ($sub) use ($vendorPurchaseIds) {
                        $sub->where('journal_entries.reference_type', 'purchase')
                            ->whereIn('journal_entries.reference_id', $vendorPurchaseIds);
                    })->orWhere(function ($sub) use ($vendorPaymentIds) {
                        $sub->where('journal_entries.reference_type', 'vendor_payment')
                            ->whereIn('journal_entries.reference_id', $vendorPaymentIds);
                    })->orWhere('journal_entries.description', 'like', "%{$partyName}%")
                      ->orWhere('journal_entry_items.description', 'like', "%{$partyName}%");
                });
            }

            $ledgerItems = $query->get();

            $periodDebit = (float) $ledgerItems->sum('debit');
            $periodCredit = (float) $ledgerItems->sum('credit');

            if ($selectedAccount->isDebitNormal()) {
                $closingBalance = $openingBalance + ($periodDebit - $periodCredit);
            } else {
                $closingBalance = $openingBalance + ($periodCredit - $periodDebit);
            }
        }

        return [
            'accounts' => $accounts,
            'customers' => $customers,
            'vendors' => $vendors,
            'selectedAccount' => $selectedAccount,
            'selectedParty' => $selectedParty,
            'partyType' => $partyType,
            'partyId' => $partyId,
            'partyName' => $partyName,
            'ledgerItems' => $ledgerItems,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'accountId' => $accountId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ];
    }

    /**
     * Calculate opening balance considering party filters.
     */
    protected function calculatePartyOpeningBalance(ChartOfAccount $account, string $fromDate, ?string $partyType, ?int $partyId, ?string $partyName): float
    {
        $cutoffDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));

        $query = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($cutoffDate) {
                $q->whereIn('status', ['posted', 'approved'])
                  ->where('entry_date', '<=', $cutoffDate);
            })
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id');

        if ($partyType === 'customer' && $partyId && $partyName) {
            $customerSaleIds = \App\Models\Sale::where('customer_id', $partyId)->pluck('id')->toArray();
            $customerPaymentIds = \App\Models\Payment::whereIn('sale_id', $customerSaleIds)->pluck('id')->toArray();
            $customerReturnIds = \App\Models\ProductReturn::where('customer_id', $partyId)->pluck('id')->toArray();

            $query->where(function ($q) use ($customerSaleIds, $customerPaymentIds, $customerReturnIds, $partyName) {
                $q->where(function ($sub) use ($customerSaleIds) {
                    $sub->where('journal_entries.reference_type', 'sale')
                        ->whereIn('journal_entries.reference_id', $customerSaleIds);
                })->orWhere(function ($sub) use ($customerPaymentIds) {
                    $sub->where('journal_entries.reference_type', 'payment')
                        ->whereIn('journal_entries.reference_id', $customerPaymentIds);
                })->orWhere(function ($sub) use ($customerReturnIds) {
                    $sub->where('journal_entries.reference_type', 'return')
                        ->whereIn('journal_entries.reference_id', $customerReturnIds);
                })->orWhere('journal_entries.description', 'like', "%{$partyName}%")
                  ->orWhere('journal_entry_items.description', 'like', "%{$partyName}%");
            });
        } elseif ($partyType === 'vendor' && $partyId && $partyName) {
            $vendorPurchaseIds = \App\Models\Purchase::where('vendor_id', $partyId)->pluck('id')->toArray();
            $vendorPaymentIds = \App\Models\Payment::whereIn('purchase_id', $vendorPurchaseIds)->pluck('id')->toArray();

            $query->where(function ($q) use ($vendorPurchaseIds, $vendorPaymentIds, $partyName) {
                $q->where(function ($sub) use ($vendorPurchaseIds) {
                    $sub->where('journal_entries.reference_type', 'purchase')
                        ->whereIn('journal_entries.reference_id', $vendorPurchaseIds);
                })->orWhere(function ($sub) use ($vendorPaymentIds) {
                    $sub->where('journal_entries.reference_type', 'vendor_payment')
                        ->whereIn('journal_entries.reference_id', $vendorPaymentIds);
                })->orWhere('journal_entries.description', 'like', "%{$partyName}%")
                  ->orWhere('journal_entry_items.description', 'like', "%{$partyName}%");
            });
        }

        $totalDebit = (float) (clone $query)->sum('journal_entry_items.debit');
        $totalCredit = (float) (clone $query)->sum('journal_entry_items.credit');

        $opening = ($partyType ? 0.0 : (float) $account->opening_balance);

        if ($account->isDebitNormal()) {
            return $opening + ($totalDebit - $totalCredit);
        } else {
            return $opening + ($totalCredit - $totalDebit);
        }
    }
}
