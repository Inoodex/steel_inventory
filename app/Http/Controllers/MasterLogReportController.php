<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\ProductReturn;
use App\Models\DailyExpense;
use App\Models\WorkerPayout;
use App\Models\TaDa;
use App\Models\Coil;
use App\Models\ActivityLog;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class MasterLogReportController extends Controller
{
    /**
     * Display the All-in-One Master Activity & Transaction Logs Report.
     */
    public function index(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->format('Y-m-d'));
        $eventType = $request->input('event_type', 'all');
        $warehouseId = $request->input('warehouse_id', 'all');
        $search = trim($request->input('search', ''));
        $perPage = (int) $request->input('per_page', 25);
        if ($perPage < 10 || $perPage > 200) {
            $perPage = 25;
        }

        $allLogs = $this->collectMasterLogs($fromDate, $toDate, $eventType, $warehouseId, $search);

        // Calculate KPI summaries
        $kpis = [
            'total_events'       => $allLogs->count(),
            'total_inflow'       => $allLogs->sum('inflow'),
            'total_outflow'      => $allLogs->sum('outflow'),
            'net_cashflow'       => $allLogs->sum('inflow') - $allLogs->sum('outflow'),
            'total_sales_value'  => $allLogs->where('category', 'sales')->sum('amount'),
            'total_purchase_val' => $allLogs->where('category', 'purchases')->sum('amount'),
            'total_weight_in'    => $allLogs->whereIn('category', ['purchases', 'stock_in'])->sum('weight_kg'),
            'total_weight_out'   => $allLogs->whereIn('category', ['sales', 'stock_out'])->sum('weight_kg'),
        ];

        // Paginate collection
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $allLogs->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedLogs = new LengthAwarePaginator(
            $currentItems,
            $allLogs->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $warehouses = Warehouse::orderBy('name')->get();

        return view('frontend.pages.reports.master_logs', compact(
            'paginatedLogs',
            'kpis',
            'fromDate',
            'toDate',
            'eventType',
            'warehouseId',
            'search',
            'warehouses'
        ));
    }

    /**
     * Generate and stream the Consolidated Master Log PDF report.
     */
    public function pdf(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->format('Y-m-d'));
        $eventType = $request->input('event_type', 'all');
        $warehouseId = $request->input('warehouse_id', 'all');
        $search = trim($request->input('search', ''));

        $allLogs = $this->collectMasterLogs($fromDate, $toDate, $eventType, $warehouseId, $search);

        $kpis = [
            'total_events'       => $allLogs->count(),
            'total_inflow'       => $allLogs->sum('inflow'),
            'total_outflow'      => $allLogs->sum('outflow'),
            'net_cashflow'       => $allLogs->sum('inflow') - $allLogs->sum('outflow'),
            'total_sales_value'  => $allLogs->where('category', 'sales')->sum('amount'),
            'total_purchase_val' => $allLogs->where('category', 'purchases')->sum('amount'),
            'total_weight_in'    => $allLogs->whereIn('category', ['purchases', 'stock_in'])->sum('weight_kg'),
            'total_weight_out'   => $allLogs->whereIn('category', ['sales', 'stock_out'])->sum('weight_kg'),
        ];

        $warehouseName = 'All Locations (Yards & Shops)';
        if ($warehouseId !== 'all') {
            $wh = Warehouse::find($warehouseId);
            if ($wh) {
                $warehouseName = $wh->name . ($wh->type === 'shop' ? ' (Retail Shop)' : ' (Warehouse)');
            }
        }

        $html = view('pdf.master_logs', compact(
            'allLogs',
            'kpis',
            'fromDate',
            'toDate',
            'eventType',
            'warehouseName',
            'search'
        ))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L', // Landscape for wide log columns
            'default_font'  => 'Helvetica',
            'margin_top'    => 35,
            'margin_bottom' => 20,
            'margin_left'   => 12,
            'margin_right'  => 12,
        ]);

        $mpdf->WriteHTML($html);
        $filename = 'Master_Activity_Logs_' . Carbon::parse($fromDate)->format('Ymd') . '_to_' . Carbon::parse($toDate)->format('Ymd') . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Unified Collector of all business events and logs.
     */
    protected function collectMasterLogs(string $fromDate, string $toDate, string $eventType, $warehouseId, string $search): Collection
    {
        $start = Carbon::parse($fromDate)->startOfDay();
        $end = Carbon::parse($toDate)->endOfDay();
        $logs = collect();

        // 1. SALES INVOICES & ORDERS
        if (in_array($eventType, ['all', 'sales', 'financial'])) {
            $salesQ = Sale::whereBetween('created_at', [$start, $end])
                ->whereNull('deleted_at')
                ->with(['customer', 'warehouse', 'salesBy']);

            if ($warehouseId !== 'all') {
                $salesQ->where('warehouse_id', $warehouseId);
            }

            $sales = $salesQ->get();
            foreach ($sales as $s) {
                $isShop = $s->warehouse && $s->warehouse->type === 'shop';
                $totalBill = (float)($s->payble ?? $s->payable_amount ?? $s->total ?? 0);
                $paid = (float)($s->advanced_payment ?? $s->paid_amount ?? 0);
                $due = (float)($s->due_payment ?? $s->due_amount ?? 0);
                $weight = (float)($s->weight ?? 0);

                $logs->push([
                    'id'             => 'sale-' . $s->id,
                    'timestamp'      => $s->created_at,
                    'category'       => 'sales',
                    'type_label'     => $isShop ? 'Shop POS Sale' : 'Yard Steel Sale',
                    'badge_class'    => $isShop ? 'bg-primary' : 'bg-info',
                    'icon'           => 'fe fe-shopping-bag',
                    'reference_no'   => $s->order_no ?: 'ORD-' . $s->id,
                    'party_name'     => $s->customer?->name ?: 'Walk-in Customer',
                    'party_role'     => 'Customer',
                    'party_phone'    => $s->customer?->phone,
                    'location'       => $s->warehouse?->name ?: 'Main Stockyard',
                    'warehouse_id'   => $s->warehouse_id,
                    'amount'         => $totalBill,
                    'inflow'         => $paid,
                    'outflow'        => 0,
                    'due_amount'     => $due,
                    'weight_kg'      => $weight,
                    'payment_method' => ucfirst($s->payment_method ?: 'Cash'),
                    'description'    => "Sale Order for {$s->customer?->name}. Bill: ৳" . number_format($totalBill, 2) . ($due > 0 ? " (Due: ৳" . number_format($due, 2) . ")" : " (Full Paid)"),
                    'operator'       => $s->salesBy?->name ?: 'System Admin',
                    'view_url'       => route('sales.show', $s->id),
                ]);
            }
        }

        // 2. PURCHASES & INWARD INTAKES
        if (in_array($eventType, ['all', 'purchases', 'financial'])) {
            $purchaseQ = Purchase::whereBetween('created_at', [$start, $end])
                ->whereNull('deleted_at')
                ->with(['vendor', 'lot', 'warehouse']);

            if ($warehouseId !== 'all') {
                $purchaseQ->where('warehouse_id', $warehouseId);
            }

            $purchases = $purchaseQ->get();
            foreach ($purchases as $p) {
                $isShop = $p->warehouse && $p->warehouse->type === 'shop';
                $totalCost = (float)($p->total_price ?? 0);
                $paid = (float)($p->payment ?? 0);
                $due = (float)($p->due ?? 0);
                $weight = (float)($p->total_weight ?? 0);

                $logs->push([
                    'id'             => 'purchase-' . $p->id,
                    'timestamp'      => $p->created_at,
                    'category'       => 'purchases',
                    'type_label'     => $isShop ? 'Shop Stock Intake' : 'Ship Lot Purchase',
                    'badge_class'    => $isShop ? 'bg-indigo' : 'bg-purple',
                    'icon'           => 'fe fe-download',
                    'reference_no'   => $p->lot?->lot_number ?: ($p->lot_number ?: 'LOT-' . $p->id),
                    'party_name'     => $p->vendor?->name ?: 'Multiple / Direct Supplier',
                    'party_role'     => 'Vendor / Supplier',
                    'party_phone'    => $p->vendor?->phone,
                    'location'       => $p->warehouse?->name ?: 'Yard Depot',
                    'warehouse_id'   => $p->warehouse_id,
                    'amount'         => $totalCost,
                    'inflow'         => 0,
                    'outflow'        => $paid,
                    'due_amount'     => $due,
                    'weight_kg'      => $weight,
                    'payment_method' => ucfirst($p->payment_method ?: 'Cash'),
                    'description'    => "Material Intake: " . number_format($weight, 2) . " kg steel. Total Cost: ৳" . number_format($totalCost, 2) . ($due > 0 ? " (Due: ৳" . number_format($due, 2) . ")" : " (Paid)"),
                    'operator'       => 'Procurement Team',
                    'view_url'       => route('purchase.show', $p->id),
                ]);
            }
        }

        // 3. PAYMENTS & MONEY COLLECTIONS
        if (in_array($eventType, ['all', 'payments', 'financial'])) {
            $paymentsQ = Payment::whereBetween('created_at', [$start, $end])
                ->with(['customer', 'vendor', 'sale', 'purchase']);

            $payments = $paymentsQ->get();
            foreach ($payments as $pm) {
                $isCustomer = (bool) $pm->customer_id;
                $amount = (float)$pm->amount;
                $forText = $pm->payment_for ?: ($isCustomer ? 'Customer Collection / Advance' : 'Vendor Payment');

                $logs->push([
                    'id'             => 'payment-' . $pm->id,
                    'timestamp'      => $pm->created_at,
                    'category'       => 'payments',
                    'type_label'     => $isCustomer ? 'Customer Payment Received' : 'Vendor Payment Disbursed',
                    'badge_class'    => $isCustomer ? 'bg-success' : 'bg-warning text-dark',
                    'icon'           => $isCustomer ? 'fe fe-arrow-down-left' : 'fe fe-arrow-up-right',
                    'reference_no'   => $pm->transaction_ref ?: ($pm->transaction_id ?: 'TRX-' . str_pad($pm->id, 5, '0', STR_PAD_LEFT)),
                    'party_name'     => $isCustomer ? ($pm->customer?->name ?: 'Customer #' . $pm->customer_id) : ($pm->vendor?->name ?: 'Vendor #' . $pm->vendor_id),
                    'party_role'     => $isCustomer ? 'Customer' : 'Vendor',
                    'party_phone'    => $isCustomer ? $pm->customer?->phone : $pm->vendor?->phone,
                    'location'       => 'Central Accounts',
                    'warehouse_id'   => null,
                    'amount'         => $amount,
                    'inflow'         => $isCustomer ? $amount : 0,
                    'outflow'        => !$isCustomer ? $amount : 0,
                    'due_amount'     => 0,
                    'weight_kg'      => 0,
                    'payment_method' => ucfirst($pm->payment_method ?: 'Cash'),
                    'description'    => "{$forText}. Mode: " . ucfirst($pm->payment_method) . ($pm->notes ? " • Note: " . $pm->notes : ''),
                    'operator'       => 'Accounts Desk',
                    'view_url'       => $isCustomer ? route('customers.ledger', $pm->customer_id) : route('vendors.show', $pm->vendor_id),
                ]);
            }
        }

        // 4. SALES RETURNS & REFUNDS
        if (in_array($eventType, ['all', 'returns', 'financial'])) {
            $returnsQ = ProductReturn::whereBetween('created_at', [$start, $end])
                ->with(['sale', 'customer', 'items']);

            $returns = $returnsQ->get();
            foreach ($returns as $ret) {
                $refund = (float)($ret->total_refund_amount ?? 0);
                $retWeight = (float)($ret->items ? $ret->items->sum('return_weight') : 0);

                $logs->push([
                    'id'             => 'return-' . $ret->id,
                    'timestamp'      => $ret->created_at,
                    'category'       => 'returns',
                    'type_label'     => 'Sales Return & Restock',
                    'badge_class'    => 'bg-danger',
                    'icon'           => 'fe fe-rotate-ccw',
                    'reference_no'   => $ret->return_no ?: 'RET-' . str_pad($ret->id, 4, '0', STR_PAD_LEFT),
                    'party_name'     => $ret->customer?->name ?: 'Customer',
                    'party_role'     => 'Customer',
                    'party_phone'    => $ret->customer?->phone,
                    'location'       => 'Stock Restock Depot',
                    'warehouse_id'   => null,
                    'amount'         => $refund,
                    'inflow'         => 0,
                    'outflow'        => $refund,
                    'due_amount'     => 0,
                    'weight_kg'      => $retWeight,
                    'payment_method' => 'Refund',
                    'description'    => "Sales Return for Order #{$ret->sale?->order_no}. Restocked: " . number_format($retWeight, 2) . " kg. Refund: ৳" . number_format($refund, 2) . " • Status: " . ucfirst($ret->status),
                    'operator'       => 'Inventory Quality Desk',
                    'view_url'       => route('returns.show', $ret->id),
                ]);
            }
        }

        // 5. OPERATIONAL EXPENSES, WORKER PAYOUTS & LOGISTICS CHARGES
        if (in_array($eventType, ['all', 'expenses', 'financial'])) {
            // Daily Expenses
            $expenses = DailyExpense::whereBetween('created_at', [$start, $end])
                ->with(['category', 'employee', 'user'])
                ->get();

            foreach ($expenses as $exp) {
                $expAmount = (float)$exp->amount;
                $logs->push([
                    'id'             => 'expense-' . $exp->id,
                    'timestamp'      => $exp->created_at,
                    'category'       => 'expenses',
                    'type_label'     => 'Operational Expense',
                    'badge_class'    => 'bg-secondary',
                    'icon'           => 'fe fe-pocket',
                    'reference_no'   => 'EXP-' . str_pad($exp->id, 4, '0', STR_PAD_LEFT),
                    'party_name'     => $exp->category?->name ?: 'General Expense',
                    'party_role'     => 'Expense Head',
                    'party_phone'    => $exp->employee?->phone,
                    'location'       => 'Head Office / Yard',
                    'warehouse_id'   => null,
                    'amount'         => $expAmount,
                    'inflow'         => 0,
                    'outflow'        => $expAmount,
                    'due_amount'     => 0,
                    'weight_kg'      => 0,
                    'payment_method' => ucfirst($exp->spend_method ?: 'Cash'),
                    'description'    => "Expense: {$exp->category?->name}. " . ($exp->remarks ?: 'No remarks'),
                    'operator'       => $exp->user?->name ?: 'Finance Officer',
                    'view_url'       => route('dailyExpenses.index'),
                ]);
            }

            // Worker Extra Charges & Payouts
            $payouts = WorkerPayout::whereBetween('created_at', [$start, $end])
                ->with(['items'])
                ->get();

            foreach ($payouts as $wpo) {
                $wpoAmount = (float)$wpo->total_amount;
                $logs->push([
                    'id'             => 'wpo-' . $wpo->id,
                    'timestamp'      => $wpo->created_at,
                    'category'       => 'expenses',
                    'type_label'     => 'Labour / Driver Settlement',
                    'badge_class'    => 'bg-dark',
                    'icon'           => 'fe fe-truck',
                    'reference_no'   => $wpo->payout_no ?: 'WPO-' . $wpo->id,
                    'party_name'     => $wpo->recipient_name ?: 'Transport / Labour Team',
                    'party_role'     => 'Worker / Transporter',
                    'party_phone'    => $wpo->recipient_phone,
                    'location'       => 'Yard Logistics',
                    'warehouse_id'   => null,
                    'amount'         => $wpoAmount,
                    'inflow'         => 0,
                    'outflow'        => $wpoAmount,
                    'due_amount'     => 0,
                    'weight_kg'      => 0,
                    'payment_method' => ucfirst($wpo->payment_method ?: 'Cash'),
                    'description'    => "Worker Settlement for " . ucfirst($wpo->charge_type ?: 'Labour/Transport') . " • Recipient: {$wpo->recipient_name}",
                    'operator'       => 'Yard Dispatcher',
                    'view_url'       => route('worker-payouts.index'),
                ]);
            }
        }

        // Keyword filter if search string is present
        if (!empty($search)) {
            $kw = strtolower($search);
            $logs = $logs->filter(function ($item) use ($kw) {
                return str_contains(strtolower($item['reference_no'] ?? ''), $kw)
                    || str_contains(strtolower($item['party_name'] ?? ''), $kw)
                    || str_contains(strtolower($item['party_phone'] ?? ''), $kw)
                    || str_contains(strtolower($item['description'] ?? ''), $kw)
                    || str_contains(strtolower($item['type_label'] ?? ''), $kw)
                    || str_contains(strtolower($item['operator'] ?? ''), $kw)
                    || str_contains(strtolower($item['location'] ?? ''), $kw);
            });
        }

        // Sort all consolidated logs by timestamp DESC
        return $logs->sortByDesc('timestamp')->values();
    }
}
