<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\BankDetail;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PdfService;
use Carbon\Carbon;

class BalanceTransferController extends Controller
{
    /**
     * Get all liquid accounts (Cash and Bank accounts) available for transfers.
     */
    private function getLiquidAccounts()
    {
        $parentBank = ChartOfAccount::where('account_code', '1120')->first();
        $parentBankId = $parentBank?->id;

        return ChartOfAccount::active()
            ->where(function ($q) use ($parentBankId) {
                $q->where('account_code', '1110') // Cash in Hand
                  ->orWhere('account_code', '1120') // Bank primary
                  ->orWhere('parent_id', $parentBankId) // Specific bank sub-accounts
                  ->orWhereNotNull('bank_detail_id');
            })
            ->orderBy('account_code')
            ->get()
            ->map(function ($acc) {
                $acc->running_balance = (float) $acc->calculateBalance();
                return $acc;
            });
    }

    /**
     * Display a listing of all internal balance/fund transfers (Contra Vouchers).
     */
    public function index(Request $request)
    {
        $query = JournalEntry::where('reference_type', 'contra')
            ->with(['items.account', 'creator', 'reversedEntry'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('entry_date', [$request->from, $request->to]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('journal_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('items.account', function ($aq) use ($search) {
                      $aq->where('account_name', 'like', "%{$search}%")
                         ->orWhere('account_code', 'like', "%{$search}%");
                  });
            });
        }

        $transfers = $query->paginate(15)->withQueryString();

        // Calculate KPI summary metrics
        $contraBaseQuery = JournalEntry::where('reference_type', 'contra')->where('status', '!=', 'reversed');
        $totalTransfersCount = (clone $contraBaseQuery)->count();
        $totalTransferredAmount = (float) (clone $contraBaseQuery)->sum('total_debit');
        $todayTransferredAmount = (float) (clone $contraBaseQuery)->whereDate('entry_date', Carbon::today())->sum('total_debit');

        $liquidAccounts = $this->getLiquidAccounts();

        return view('frontend.pages.accounts.balance-transfers.index', compact(
            'transfers',
            'totalTransfersCount',
            'totalTransferredAmount',
            'todayTransferredAmount',
            'liquidAccounts'
        ));
    }

    /**
     * Show the form for creating a new balance transfer.
     */
    public function create()
    {
        $accounts = $this->getLiquidAccounts();
        $today = date('Y-m-d');
        $suggestedNo = 'TRF-' . date('Ymd') . '-' . str_pad((JournalEntry::where('reference_type', 'contra')->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('frontend.pages.accounts.balance-transfers.create', compact('accounts', 'today', 'suggestedNo'));
    }

    /**
     * Store a newly created balance transfer and post double-entry contra voucher.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transfer_date'   => 'required|date',
            'from_account_id' => 'required|exists:chart_of_accounts,id',
            'to_account_id'   => 'required|exists:chart_of_accounts,id|different:from_account_id',
            'amount'          => 'required|numeric|min:0.01',
            'reference_no'    => 'nullable|string|max:100',
            'notes'           => 'nullable|string|max:1000',
        ], [
            'to_account_id.different' => 'The destination account must be different from the source account.',
            'amount.min'              => 'The transfer amount must be greater than zero.',
        ]);

        $fromAccount = ChartOfAccount::findOrFail($validated['from_account_id']);
        $toAccount   = ChartOfAccount::findOrFail($validated['to_account_id']);
        $amount      = (float) $validated['amount'];

        $sourceBalance = (float) $fromAccount->calculateBalance();
        if (round($amount, 2) > round($sourceBalance, 2)) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Insufficient funds in {$fromAccount->account_name}. Available balance is ৳" . number_format($sourceBalance, 2) . ", cannot transfer ৳" . number_format($amount, 2) . ".");
        }

        $refNo = !empty($validated['reference_no']) ? trim($validated['reference_no']) : null;
        $notes = !empty($validated['notes']) ? trim($validated['notes']) : null;

        try {
            $journalEntry = DB::transaction(function () use ($validated, $fromAccount, $toAccount, $amount, $refNo, $notes) {
                $refNoText = $refNo ? " [Ref/TrxID: {$refNo}]" : '';
                $notesText = $notes ? " • Note: {$notes}" : '';
                $description = "Fund Transfer: {$fromAccount->account_name} → {$toAccount->account_name}{$refNoText}{$notesText}";

                $items = [
                    [
                        'account_id'  => $toAccount->id,
                        'debit'       => $amount,
                        'credit'      => 0.00,
                        'description' => "Fund received from {$fromAccount->account_name}" . ($refNo ? " ({$refNo})" : ''),
                    ],
                    [
                        'account_id'  => $fromAccount->id,
                        'debit'       => 0.00,
                        'credit'      => $amount,
                        'description' => "Fund sent to {$toAccount->account_name}" . ($refNo ? " ({$refNo})" : ''),
                    ],
                ];

                $entry = postJournalEntry([
                    'entry_date'     => $validated['transfer_date'],
                    'reference_type' => 'contra',
                    'description'    => $description,
                    'status'         => 'approved',
                    'created_by'     => Auth::id() ?? \App\Models\User::value('id'),
                    'items'          => $items,
                ]);

                // Sync current_balance if either account is linked to a BankDetail record
                if ($fromAccount->bank_detail_id) {
                    BankDetail::find($fromAccount->bank_detail_id)?->syncCurrentBalance();
                }
                if ($toAccount->bank_detail_id) {
                    BankDetail::find($toAccount->bank_detail_id)?->syncCurrentBalance();
                }

                return $entry;
            });

            return redirect()->route('balance-transfers.show', $journalEntry->id)
                ->with('success', "Balance transfer of ৳" . number_format($amount, 2) . " executed successfully! Contra Voucher [{$journalEntry->journal_no}] posted.");
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error processing balance transfer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transfer details & voucher.
     */
    public function show($id)
    {
        $transfer = JournalEntry::where('reference_type', 'contra')
            ->with(['items.account', 'creator', 'approver', 'reversedEntry'])
            ->findOrFail($id);

        $toItem   = $transfer->items->firstWhere('debit', '>', 0);
        $fromItem = $transfer->items->firstWhere('credit', '>', 0);

        return view('frontend.pages.accounts.balance-transfers.show', compact('transfer', 'toItem', 'fromItem'));
    }

    /**
     * Export Balance Transfer Voucher as PDF.
     */
    public function downloadPdf($id)
    {
        $transfer = JournalEntry::where('reference_type', 'contra')
            ->with(['items.account', 'creator', 'approver'])
            ->findOrFail($id);

        $toItem   = $transfer->items->firstWhere('debit', '>', 0);
        $fromItem = $transfer->items->firstWhere('credit', '>', 0);

        $filename = 'Transfer_Voucher_' . $transfer->journal_no . '.pdf';

        return PdfService::viewToResponse('pdf.balance_transfer', compact('transfer', 'toItem', 'fromItem'), $filename);
    }

    /**
     * Reverse an existing balance transfer.
     */
    public function reverse(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $transfer = JournalEntry::where('reference_type', 'contra')->findOrFail($id);

        try {
            $reversalVoucher = reverseJournalEntry($transfer->id, $request->reason);

            // Re-sync bank balances
            foreach ($transfer->items as $item) {
                if ($item->account?->bank_detail_id) {
                    BankDetail::find($item->account->bank_detail_id)?->syncCurrentBalance();
                }
            }

            return redirect()->route('balance-transfers.show', $transfer->id)
                ->with('success', "Balance Transfer has been reversed successfully with Voucher [{$reversalVoucher->journal_no}].");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to reverse balance transfer: ' . $e->getMessage());
        }
    }
}
