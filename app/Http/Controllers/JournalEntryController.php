<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $from = $request->query('from');
        $to = $request->query('to');
        $refType = $request->query('reference_type');

        $query = JournalEntry::with(['items.account', 'creator', 'approver'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($refType) {
            $query->where('reference_type', $refType);
        }

        if ($from && $to) {
            $query->whereBetween('entry_date', [$from, $to]);
        }

        $entries = $query->paginate(15)->withQueryString();

        return view('frontend.pages.accounts.journal-entries.index', compact('entries', 'status', 'from', 'to', 'refType'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::active()
            ->orderBy('account_code')
            ->get();

        $journalNo = JournalEntry::generateJournalNo();
        $today = date('Y-m-d');

        return view('frontend.pages.accounts.journal-entries.create', compact('accounts', 'journalNo', 'today'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'reference_type' => 'required|string',
            'description' => 'required|string|max:1000',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string|max:500',
        ]);

        try {
            $journalEntry = postJournalEntry([
                'entry_date' => $validated['entry_date'],
                'reference_type' => $validated['reference_type'],
                'description' => $validated['description'],
                'status' => 'approved',
                'created_by' => Auth::id() ?? \App\Models\User::value('id'),
                'items' => $validated['items'],
            ]);

            return redirect()->route('journal-entries.show', $journalEntry->id)
                ->with('success', "Journal Entry [{$journalEntry->journal_no}] posted successfully!");
        } catch (\DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error posting journal entry: ' . $e->getMessage());
        }
    }

    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load(['items.account', 'creator', 'approver', 'reversedEntry']);
        return view('frontend.pages.accounts.journal-entries.show', compact('journalEntry'));
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $reversalVoucher = reverseJournalEntry($journalEntry->id, $request->reason);

            return redirect()->route('journal-entries.show', $reversalVoucher->id)
                ->with('success', "Original voucher reversed. Reversal voucher [{$reversalVoucher->journal_no}] created.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Reversal failed: ' . $e->getMessage());
        }
    }

    public function downloadPdf(JournalEntry $journalEntry)
    {
        $journalEntry->load(['items.account', 'creator', 'approver']);

        $padBase64 = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';

        $html = view('pdf.accounts.voucher', compact('journalEntry', 'padBase64'))->render();

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

        $pdfContent = $mpdf->Output("Voucher-{$journalEntry->journal_no}.pdf", 'S');
        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"Voucher-{$journalEntry->journal_no}.pdf\"",
        ]);
    }

    /**
     * Export list of Journal Entries to CSV/Excel.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $status = $request->query('status');
        $from = $request->query('from');
        $to = $request->query('to');
        $refType = $request->query('reference_type');

        $query = JournalEntry::with(['items.account', 'creator'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($status) {
            $query->where('status', $status);
        }
        if ($refType) {
            $query->where('reference_type', $refType);
        }
        if ($from && $to) {
            $query->whereBetween('entry_date', [$from, $to]);
        }

        $entries = $query->get();
        $filename = "Journal_Entries_" . date('Ymd_His') . ".csv";

        return response()->stream(function () use ($entries, $status, $from, $to, $refType) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['Journal Entries Registry Report']);
            fputcsv($handle, ['Filter Date Range: ' . ($from && $to ? "{$from} to {$to}" : 'All Dates')]);
            fputcsv($handle, ['Filter Status: ' . ($status ?: 'All Statuses')]);
            fputcsv($handle, []);

            fputcsv($handle, ['Journal / Voucher #', 'Date', 'Reference Type', 'Description / Narration', 'Total Debit (৳)', 'Total Credit (৳)', 'Status', 'Created By', 'Line Items Details']);

            foreach ($entries as $entry) {
                $lines = [];
                foreach ($entry->items as $item) {
                    $accName = $item->account ? "[{$item->account->account_code}] {$item->account->account_name}" : 'Account';
                    if ($item->debit > 0) {
                        $lines[] = "Dr: {$accName} (৳" . number_format($item->debit, 2) . ")";
                    }
                    if ($item->credit > 0) {
                        $lines[] = "Cr: {$accName} (৳" . number_format($item->credit, 2) . ")";
                    }
                }

                fputcsv($handle, [
                    $entry->journal_no,
                    date('Y-m-d', strtotime($entry->entry_date)),
                    ucfirst(str_replace('_', ' ', $entry->reference_type)),
                    $entry->description,
                    number_format($entry->total_debit, 2, '.', ''),
                    number_format($entry->total_credit, 2, '.', ''),
                    ucfirst($entry->status),
                    $entry->creator ? $entry->creator->name : 'System',
                    implode(' | ', $lines)
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
