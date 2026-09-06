<?php

namespace App\Http\Controllers;

use App\Models\BankDetail;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\WorkerPayout;
use App\Models\WorkerPayoutItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

class WorkerPayoutController extends Controller
{
    public function index(Request $request)
    {
        // 1. Overall System KPI Metrics
        $allSalesWithCharges = Sale::where(function ($q) {
            $q->where('delivery_charge', '>', 0)
              ->orWhere('labour_cost', '>', 0)
              ->orWhere('weight_scale_cost', '>', 0)
              ->orWhere('other_charges', '>', 0);
        })->get();

        $totalCollectedDelivery = (float) $allSalesWithCharges->sum('delivery_charge');
        $totalCollectedLabour   = (float) $allSalesWithCharges->sum('labour_cost');
        $totalCollectedScale    = (float) $allSalesWithCharges->sum('weight_scale_cost');
        $totalCollectedOther    = (float) $allSalesWithCharges->sum('other_charges');
        $totalCollectedCharges  = $totalCollectedDelivery + $totalCollectedLabour + $totalCollectedScale + $totalCollectedOther;

        // Paid amounts from WorkerPayoutItem (safely checked if table exists)
        $totalPaidLabour   = 0.00;
        $totalPaidDelivery = 0.00;
        $totalPaidScale    = 0.00;
        $totalPaidOther    = 0.00;
        $totalPaidCharges  = 0.00;

        if (\Illuminate\Support\Facades\Schema::hasTable('worker_payout_items')) {
            $totalPaidLabour   = (float) WorkerPayoutItem::where('charge_type', 'labour')->sum('amount');
            $totalPaidDelivery = (float) WorkerPayoutItem::where('charge_type', 'delivery')->sum('amount');
            $totalPaidScale    = (float) WorkerPayoutItem::where('charge_type', 'weight_scale')->sum('amount');
            $totalPaidOther    = (float) WorkerPayoutItem::where('charge_type', 'other')->sum('amount');
            $totalPaidCharges  = $totalPaidLabour + $totalPaidDelivery + $totalPaidScale + $totalPaidOther;
        }

        $totalDueLabour    = max(0, $totalCollectedLabour - $totalPaidLabour);
        $totalDueDelivery  = max(0, $totalCollectedDelivery - $totalPaidDelivery);
        $totalDueScale     = max(0, $totalCollectedScale - $totalPaidScale);
        $totalDueOther     = max(0, $totalCollectedOther - $totalPaidOther);
        $totalDueCharges   = max(0, $totalCollectedCharges - $totalPaidCharges);

        // 2. Filtered Unsettled Sales Query
        $salesQuery = Sale::with(['customer'])
            ->where(function ($q) {
                $q->where('delivery_charge', '>', 0)
                  ->orWhere('labour_cost', '>', 0)
                  ->orWhere('weight_scale_cost', '>', 0)
                  ->orWhere('other_charges', '>', 0);
            });

        if (\Illuminate\Support\Facades\Schema::hasTable('worker_payout_items')) {
            $salesQuery->with('workerPayoutItems');
        }

        // Preset / Date Filter
        $datePreset = $request->input('date_preset', 'all');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($datePreset === 'today') {
            $salesQuery->whereDate('order_date', Carbon::today());
        } elseif ($datePreset === 'this_week') {
            $salesQuery->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($datePreset === 'this_month') {
            $salesQuery->whereBetween('order_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        } elseif ($fromDate || $toDate) {
            if ($fromDate && $toDate) {
                $salesQuery->whereBetween('order_date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $salesQuery->whereDate('order_date', '>=', $fromDate);
            } elseif ($toDate) {
                $salesQuery->whereDate('order_date', '<=', $toDate);
            }
        }

        // Status Filter
        $status = $request->input('status');
        if ($request->filled('status') && $status !== 'all') {
            if ($status === 'pending') {
                $salesQuery->where('charges_payout_status', '!=', 'paid');
            } elseif ($status === 'unpaid') {
                $salesQuery->where('charges_payout_status', 'unpaid');
            } elseif ($status === 'partial') {
                $salesQuery->where('charges_payout_status', 'partial');
            } elseif ($status === 'paid') {
                $salesQuery->where('charges_payout_status', 'paid');
            }
        }

        // Search Filter (Order No or Customer Name)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $salesQuery->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $salesQuery->latest('order_date')->latest('id')->get();

        // 3. Payouts History Ledger
        $payouts = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('worker_payouts')) {
            $payoutsQuery = WorkerPayout::with(['items.sale', 'paymentAccount', 'creator', 'journalEntry']);

            if ($request->filled('history_charge_type') && $request->history_charge_type !== 'all') {
                $payoutsQuery->where('charge_type', $request->history_charge_type);
            }
            if ($request->filled('history_search')) {
                $hSearch = trim($request->input('history_search'));
                $payoutsQuery->where(function ($q) use ($hSearch) {
                    $q->where('payout_no', 'like', "%{$hSearch}%")
                      ->orWhere('recipient_name', 'like', "%{$hSearch}%")
                      ->orWhere('recipient_phone', 'like', "%{$hSearch}%");
                });
            }

            $payouts = $payoutsQuery->latest('payout_date')->latest('id')->paginate(15)->withQueryString();
        }

        // 4. Accounts & Banks for Payment Modals
        $paymentAccounts = ChartOfAccount::whereIn('account_code', ['1110', '1120'])
            ->orWhere(function ($q) {
                $q->where('account_type', 'asset')->where('level', '>=', 2);
            })
            ->where('is_active', true)
            ->get();

        $bankDetails = BankDetail::where('status', 'active')->get();

        return view('frontend.pages.worker_payouts.index', compact(
            'sales',
            'payouts',
            'totalCollectedCharges',
            'totalCollectedLabour',
            'totalCollectedDelivery',
            'totalCollectedScale',
            'totalCollectedOther',
            'totalPaidCharges',
            'totalPaidLabour',
            'totalPaidDelivery',
            'totalPaidScale',
            'totalPaidOther',
            'totalDueCharges',
            'totalDueLabour',
            'totalDueDelivery',
            'totalDueScale',
            'totalDueOther',
            'paymentAccounts',
            'bankDetails',
            'datePreset',
            'status'
        ));
    }

    public function batchSettle(Request $request)
    {
        $request->validate([
            'sale_ids'           => 'required|array|min:1',
            'sale_ids.*'         => 'exists:sales,id',
            'charge_type'        => 'required|in:labour,delivery,weight_scale,other,all',
            'recipient_name'     => 'required|string|max:191',
            'recipient_phone'    => 'nullable|string|max:50',
            'payout_date'        => 'required|date',
            'payment_method'     => 'required|string',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
            'bank_detail_id'     => 'nullable|exists:bank_details,id',
            'notes'              => 'nullable|string',
        ]);

        $chargeType = $request->input('charge_type');
        $saleIds = $request->input('sale_ids');
        $payoutDate = $request->input('payout_date');

        DB::beginTransaction();
        try {
            $sales = Sale::whereIn('id', $saleIds)->lockForUpdate()->get();

            $payoutItemsData = [];
            $totalDisbursed = 0.00;

            foreach ($sales as $sale) {
                if ($chargeType === 'all') {
                    $typesToSettle = ['labour', 'delivery', 'weight_scale', 'other'];
                } else {
                    $typesToSettle = [$chargeType];
                }

                foreach ($typesToSettle as $type) {
                    $due = match ($type) {
                        'labour'       => $sale->due_labour_cost,
                        'delivery'     => $sale->due_delivery_charge,
                        'weight_scale' => $sale->due_weight_scale_cost,
                        'other'        => $sale->due_other_charges,
                        default        => 0.00
                    };

                    if ($due > 0.001) {
                        $payoutItemsData[] = [
                            'sale_id'     => $sale->id,
                            'charge_type' => $type,
                            'amount'      => $due,
                        ];
                        $totalDisbursed += $due;
                    }
                }
            }

            if ($totalDisbursed <= 0.001) {
                DB::rollBack();
                return redirect()->back()->with('error', 'None of the selected sales have unpaid dues for the selected charge type.');
            }

            $payoutNo = WorkerPayout::generatePayoutNo($payoutDate);

            $payout = WorkerPayout::create([
                'payout_no'          => $payoutNo,
                'payout_date'        => $payoutDate,
                'charge_type'        => $chargeType === 'all' ? 'mixed' : $chargeType,
                'recipient_name'     => $request->input('recipient_name'),
                'recipient_phone'    => $request->input('recipient_phone'),
                'total_amount'       => $totalDisbursed,
                'payment_method'     => $request->input('payment_method'),
                'bank_detail_id'     => $request->input('bank_detail_id'),
                'payment_account_id' => $request->input('payment_account_id'),
                'notes'              => $request->input('notes'),
                'created_by'         => Auth::id(),
            ]);

            foreach ($payoutItemsData as $itemData) {
                WorkerPayoutItem::create([
                    'worker_payout_id' => $payout->id,
                    'sale_id'          => $itemData['sale_id'],
                    'charge_type'      => $itemData['charge_type'],
                    'amount'           => $itemData['amount'],
                ]);
            }

            // Sync status on all affected sales
            foreach ($sales as $sale) {
                $sale->syncChargesPayoutStatus();
            }

            // Double-Entry Journal Entry Posting:
            // Debit: 2140 (Pass-Through Extra Charges Payable)
            // Credit: Selected Payment Account (Cash / Bank)
            try {
                $chargesAcc = ChartOfAccount::where('account_code', '2140')->first();
                $cashAcc = ChartOfAccount::find($request->input('payment_account_id'));

                if ($chargesAcc && $cashAcc) {
                    $typeLabel = ucwords(str_replace('_', ' ', $chargeType));
                    $salesCount = count($sales);
                    $journal = postJournalEntry([
                        'entry_date'     => $payoutDate,
                        'reference_type' => 'worker_payout',
                        'reference_id'   => $payout->id,
                        'description'    => "Worker extra charges payout [{$typeLabel}] Voucher #{$payout->payout_no} to {$payout->recipient_name} ({$salesCount} sales invoices)",
                        'items'          => [
                            [
                                'account_id'  => $chargesAcc->id,
                                'debit'       => $totalDisbursed,
                                'credit'      => 0.00,
                                'description' => "Clear pass-through liability for Voucher #{$payout->payout_no}"
                            ],
                            [
                                'account_id'  => $cashAcc->id,
                                'debit'       => 0.00,
                                'credit'      => $totalDisbursed,
                                'description' => "Disburse funds from {$cashAcc->account_name} to {$payout->recipient_name}"
                            ]
                        ]
                    ]);

                    $payout->update(['journal_entry_id' => $journal->id]);
                }
            } catch (\Throwable $e) {
                Log::warning("Worker payout journal entry posting failed: " . $e->getMessage());
            }

            DB::commit();

            return redirect()->route('worker-payouts.index')
                ->with('success', "Payout voucher #{$payout->payout_no} for ৳ " . number_format($totalDisbursed, 2) . " disbursed successfully!")
                ->with('last_payout_id', $payout->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Batch settle error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Settlement failed: ' . $e->getMessage());
        }
    }

    public function singleSettle(Request $request, $saleId)
    {
        $request->validate([
            'recipient_name'     => 'required|string|max:191',
            'recipient_phone'    => 'nullable|string|max:50',
            'payout_date'        => 'required|date',
            'payment_method'     => 'required|string',
            'payment_account_id' => 'required|exists:chart_of_accounts,id',
            'bank_detail_id'     => 'nullable|exists:bank_details,id',
            'notes'              => 'nullable|string',
            'charges'            => 'required|array',
            'charges.*'          => 'nullable|numeric|min:0',
        ]);

        $sale = Sale::findOrFail($saleId);
        $payoutDate = $request->input('payout_date');
        $charges = $request->input('charges');

        DB::beginTransaction();
        try {
            $totalDisbursed = 0.00;
            $itemsData = [];
            $settledTypes = [];

            foreach ($charges as $type => $amount) {
                $amt = (float) $amount;
                if ($amt > 0.001) {
                    $due = match ($type) {
                        'labour'       => $sale->due_labour_cost,
                        'delivery'     => $sale->due_delivery_charge,
                        'weight_scale' => $sale->due_weight_scale_cost,
                        'other'        => $sale->due_other_charges,
                        default        => 0.00
                    };

                    if ($amt > $due + 0.01) {
                        throw new \Exception("Entered amount for " . ucwords(str_replace('_', ' ', $type)) . " (৳ {$amt}) exceeds available due (৳ {$due}).");
                    }

                    $itemsData[] = [
                        'sale_id'     => $sale->id,
                        'charge_type' => $type,
                        'amount'      => $amt,
                    ];
                    $totalDisbursed += $amt;
                    $settledTypes[] = $type;
                }
            }

            if ($totalDisbursed <= 0.001) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Please enter at least one charge amount greater than 0.');
            }

            $chargeTypeLabel = count($settledTypes) === 1 ? $settledTypes[0] : 'mixed';
            $payoutNo = WorkerPayout::generatePayoutNo($payoutDate);

            $payout = WorkerPayout::create([
                'payout_no'          => $payoutNo,
                'payout_date'        => $payoutDate,
                'charge_type'        => $chargeTypeLabel,
                'recipient_name'     => $request->input('recipient_name'),
                'recipient_phone'    => $request->input('recipient_phone'),
                'total_amount'       => $totalDisbursed,
                'payment_method'     => $request->input('payment_method'),
                'bank_detail_id'     => $request->input('bank_detail_id'),
                'payment_account_id' => $request->input('payment_account_id'),
                'notes'              => $request->input('notes'),
                'created_by'         => Auth::id(),
            ]);

            foreach ($itemsData as $item) {
                WorkerPayoutItem::create([
                    'worker_payout_id' => $payout->id,
                    'sale_id'          => $item['sale_id'],
                    'charge_type'      => $item['charge_type'],
                    'amount'           => $item['amount'],
                ]);
            }

            $sale->syncChargesPayoutStatus();

            // Journal Entry
            try {
                $chargesAcc = ChartOfAccount::where('account_code', '2140')->first();
                $cashAcc = ChartOfAccount::find($request->input('payment_account_id'));

                if ($chargesAcc && $cashAcc) {
                    $journal = postJournalEntry([
                        'entry_date'     => $payoutDate,
                        'reference_type' => 'worker_payout',
                        'reference_id'   => $payout->id,
                        'description'    => "Worker extra charges payout Voucher #{$payout->payout_no} for Invoice #{$sale->order_no} to {$payout->recipient_name}",
                        'items'          => [
                            [
                                'account_id'  => $chargesAcc->id,
                                'debit'       => $totalDisbursed,
                                'credit'      => 0.00,
                                'description' => "Clear pass-through liability for Invoice #{$sale->order_no}"
                            ],
                            [
                                'account_id'  => $cashAcc->id,
                                'debit'       => 0.00,
                                'credit'      => $totalDisbursed,
                                'description' => "Disburse funds from {$cashAcc->account_name} to {$payout->recipient_name}"
                            ]
                        ]
                    ]);

                    $payout->update(['journal_entry_id' => $journal->id]);
                }
            } catch (\Throwable $e) {
                Log::warning("Single payout journal entry posting failed: " . $e->getMessage());
            }

            DB::commit();

            return redirect()->route('worker-payouts.index')
                ->with('success', "Payout voucher #{$payout->payout_no} for ৳ " . number_format($totalDisbursed, 2) . " disbursed successfully!")
                ->with('last_payout_id', $payout->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Single settlement failed: ' . $e->getMessage());
        }
    }

    public function voidPayout($id)
    {
        $payout = WorkerPayout::with('items')->findOrFail($id);

        DB::beginTransaction();
        try {
            $affectedSaleIds = $payout->items->pluck('sale_id')->unique()->toArray();

            // Remove journal entry if linked
            if ($payout->journal_entry_id) {
                JournalEntry::where('id', $payout->journal_entry_id)->delete();
            } else {
                JournalEntry::where('reference_type', 'worker_payout')
                    ->where('reference_id', $payout->id)
                    ->delete();
            }

            $payoutNo = $payout->payout_no;
            $payout->delete(); // cascades worker_payout_items

            // Re-sync each affected sale
            $affectedSales = Sale::whereIn('id', $affectedSaleIds)->get();
            foreach ($affectedSales as $sale) {
                $sale->syncChargesPayoutStatus();
            }

            DB::commit();

            return redirect()->route('worker-payouts.index')
                ->with('success', "Payout voucher #{$payoutNo} has been voided/reverted and liability restored.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to void payout: ' . $e->getMessage());
        }
    }

    public function voucherPdf($id)
    {
        $payout = WorkerPayout::with(['items.sale.customer', 'paymentAccount', 'bankDetail', 'creator', 'journalEntry'])->findOrFail($id);

        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');

        $html = view('pdf.worker_payout_voucher', compact('payout', 'padBase64'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
            'margin_top' => 45,
            'margin_bottom' => 25,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $pdfContent = $mpdf->Output("Voucher-{$payout->payout_no}.pdf", 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"Voucher-{$payout->payout_no}.pdf\"",
        ]);
    }
}
