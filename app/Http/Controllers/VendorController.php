<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Service, User, Vendor, Purchase};
use Illuminate\Support\Facades\Validator;
use App\Services\PdfService;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $vendors = Vendor::withSum(['purchases' => function($q) {
            $q->whereNull('deleted_at');
        }], 'due')
        ->withSum(['purchases' => function($q) {
            $q->whereNull('deleted_at');
        }], 'total_price')
        ->withSum('payments', 'amount')
        ->latest()
        ->get()
        ->map(function ($v) {
            $fin = $v->calculateFinancials(
                (float)($v->purchases_sum_total_price ?? 0),
                (float)($v->payments_sum_amount ?? 0)
            );
            $v->opening_due = max(0, (float)($v->opening_balance ?? 0));
            $v->purchase_due = (float)($v->purchases_sum_due ?? 0);
            $v->advance_balance = $fin['advance_balance'];
            $v->net_due = $fin['payable_due'];
            return $v;
        });

        $customers = $vendors; // Alias for compatibility
        $bankAccounts = \App\Models\BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.vendor.index', compact('vendors', 'customers', 'bankAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       return view('frontend.pages.vendor.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $attributes = $request->all();
        $rules = [
            'name'            => 'required|string|max:255',
            'phone'           => 'required|numeric|unique:vendors,phone',
            'email'           => 'nullable|email|max:255',
            'opening_balance' => 'nullable|numeric',
            'opening_due'     => 'nullable|numeric|min:0',
            'address'         => 'required|string',
        ];
        $validation = Validator::make($attributes, $rules);
        if ($validation->fails()) {
            return redirect()->back()->with(['error' => getNotify(4), 'error_code' => 'edit'])->withErrors($validation)->withInput();
        }

        $vendor = new Vendor;
        $vendor->name = $request->name;
        $vendor->phone = $request->phone;
        $vendor->email = $request->email;
        $vendor->address = $request->address;

        if ($request->filled('opening_due')) {
            $vendor->opening_balance = abs((float)$request->opening_due);
        } elseif ($request->filled('opening_balance')) {
            $vendor->opening_balance = (float)$request->opening_balance;
        } else {
            $vendor->opening_balance = 0.00;
        }

        $vendor->status = $request->status ?? '1';
        $vendor->save();

        return redirect()->route('vendors.index')->with('success', 'Vendor added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $vendor = Vendor::findOrFail($id);
        $customer = $vendor; // Alias for compatibility
        $purchases = Purchase::where('vendor_id', $id)->with(['lot', 'coils', 'warehouse'])->latest()->get();
        $payments = \App\Models\Payment::with(['bankDetail', 'purchase'])->where('vendor_id', $id)->latest()->get();
        $bankAccounts = \App\Models\BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.vendor.show', compact('vendor', 'customer', 'purchases', 'payments', 'bankAccounts'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $vendor = Vendor::findOrFail($id);
        $customer = $vendor; // Alias
        return view('frontend.pages.vendor.edit', compact('vendor', 'customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $attributes = $request->all();
        $rules = [
            'name'            => 'required|string|max:255',
            'phone'           => 'required|numeric|unique:vendors,phone,'. $id,
            'email'           => 'nullable|email|max:255',
            'opening_balance' => 'nullable|numeric',
            'opening_due'     => 'nullable|numeric|min:0',
            'address'         => 'required|string',
        ];
        $validation = Validator::make($attributes, $rules);
        if ($validation->fails()) {
            return redirect()->back()->with(['error' => getNotify(4), 'error_code' => 'edit'])->withErrors($validation)->withInput();
        }

        $vendor = Vendor::findOrFail($id);
        $vendor->name = $request->name;
        $vendor->phone = $request->phone;
        $vendor->email = $request->email;
        $vendor->address = $request->address;

        if ($request->filled('opening_due')) {
            $vendor->opening_balance = abs((float)$request->opening_due);
        } elseif ($request->filled('opening_balance')) {
            $vendor->opening_balance = (float)$request->opening_balance;
        } else {
            $vendor->opening_balance = 0.00;
        }

        if ($request->has('status')) {
            $vendor->status = $request->status;
        }
        $vendor->save();

        return redirect()->route('vendors.index')->with('success', 'Vendor data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
       $vendor = Vendor::findOrFail($id);
       $vendor->delete();
       return redirect()->back()->with(['success' => getNotify(3)]);
    }

    public function downloadPdf()
    {
        ini_set('memory_limit', '512M');
        $vendors = Vendor::withSum(['purchases' => function($q) {
            $q->whereNull('deleted_at');
        }], 'due')
        ->withSum(['purchases' => function($q) {
            $q->whereNull('deleted_at');
        }], 'total_price')
        ->withSum('payments', 'amount')
        ->latest()
        ->get()
        ->map(function ($v) {
            $fin = $v->calculateFinancials(
                (float)($v->purchases_sum_total_price ?? 0),
                (float)($v->payments_sum_amount ?? 0)
            );
            $v->opening_due = max(0, (float)($v->opening_balance ?? 0));
            $v->purchase_due = (float)($v->purchases_sum_due ?? 0);
            $v->advance_balance = $fin['advance_balance'];
            $v->net_due = $fin['payable_due'];
            return $v;
        });

        return PdfService::viewToResponse('pdf.vendors', compact('vendors'), 'Vendor_List_' . now()->format('Y_m_d_His') . '.pdf');
    }

    /**
     * Display Vendor Party Ledger Statement
     */
    public function ledger(Request $request, string $id)
    {
        $vendor = Vendor::findOrFail($id);
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $ledgerData = $this->getVendorLedgerData($vendor, $fromDate, $toDate);

        return view('frontend.pages.vendor.ledger', $ledgerData);
    }

    /**
     * Download Vendor Party Ledger as mPDF
     */
    public function ledgerPdf(Request $request, string $id)
    {
        $vendor = Vendor::findOrFail($id);
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $ledgerData = $this->getVendorLedgerData($vendor, $fromDate, $toDate);
        $ledgerData['padBase64'] = function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '';

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $vendor->name);
        $filename = "Vendor_Ledger_{$safeName}_" . date('Ymd') . '.pdf';

        return PdfService::viewToResponse('pdf.vendor_ledger', $ledgerData, $filename, [
            'margin_top'    => 45,
            'margin_bottom' => 25,
            'margin_left'   => 15,
            'margin_right'  => 15,
        ]);
    }

    /**
     * Helper to compute vendor ledger transactions & running balance
     */
    private function getVendorLedgerData(Vendor $vendor, ?string $fromDate = null, ?string $toDate = null): array
    {
        $openingBalance = (float)($vendor->opening_balance ?? 0.00);

        if ($fromDate) {
            $priorPurchases = \App\Models\Purchase::where('vendor_id', $vendor->id)
                ->whereDate('created_at', '<', $fromDate)
                ->sum('total_price');

            $priorPayments = \App\Models\Payment::where('vendor_id', $vendor->id)
                ->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '<', $fromDate)
                ->sum('amount');

            $priorReturns = \App\Models\PurchaseReturn::where('vendor_id', $vendor->id)
                ->where('status', '!=', 'cancelled')
                ->whereDate('return_date', '<', $fromDate)
                ->sum('total_refund_amount');

            $openingBalance += ($priorPurchases - $priorPayments - $priorReturns);
        }

        $purchasesQuery = \App\Models\Purchase::with('lot')->where('vendor_id', $vendor->id);
        $paymentsQuery = \App\Models\Payment::with('bankDetail')->where('vendor_id', $vendor->id);
        $returnsQuery = \App\Models\PurchaseReturn::where('vendor_id', $vendor->id)->where('status', '!=', 'cancelled');

        if ($fromDate) {
            $purchasesQuery->whereDate('created_at', '>=', $fromDate);
            $paymentsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '>=', $fromDate);
            $returnsQuery->whereDate('return_date', '>=', $fromDate);
        }
        if ($toDate) {
            $purchasesQuery->whereDate('created_at', '<=', $toDate);
            $paymentsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '<=', $toDate);
            $returnsQuery->whereDate('return_date', '<=', $toDate);
        }

        $purchases = $purchasesQuery->get();
        $payments = $paymentsQuery->get();
        $returns = $returnsQuery->get();

        $transactions = collect();

        foreach ($purchases as $p) {
            $lotStr = $p->lot ? " (Lot: {$p->lot->lot_number})" : "";
            $specs = array_filter([$p->product_name, $p->thickness ? "Thick: {$p->thickness}" : null, $p->size ? "Size: {$p->size}" : null]);
            $specStr = !empty($specs) ? implode(' | ', $specs) : '';

            if ($p->notes) {
                $desc = $p->notes . ($specStr ? " [{$specStr}]" : "") . $lotStr;
            } elseif ($specStr) {
                $desc = $specStr . $lotStr;
            } else {
                $desc = "Purchase PO #{$p->id}" . $lotStr;
            }

            $transactions->push([
                'date' => $p->created_at->format('Y-m-d'),
                'created_at' => $p->created_at,
                'type' => 'Purchase Bill',
                'badge' => 'primary',
                'ref' => "PO #{$p->id}",
                'url' => route('purchase.show', $p->id),
                'description' => $desc,
                'debit' => 0.00,
                'credit' => (float)$p->total_price, // Credit increases vendor payable
            ]);
        }

        foreach ($payments as $pay) {
            $method = strtolower($pay->payment_method ?? 'cash');
            $bank = $pay->bankDetail;

            if ($bank) {
                $accNo = $bank->account_number ? " - {$bank->account_number}" : "";
                $methodDesc = "{$bank->bank_name}{$accNo}";
            } elseif ($method === 'cash') {
                $methodDesc = 'Cash';
            } else {
                $methodDesc = ucfirst(str_replace('_', ' ', $method));
            }

            $refStr = $pay->transaction_ref ? " [Ref: {$pay->transaction_ref}]" : "";
            $remarksStr = $pay->remarks ? " - {$pay->remarks}" : "";

            $transactions->push([
                'date' => $pay->payment_date ? $pay->payment_date : $pay->created_at->format('Y-m-d'),
                'created_at' => $pay->created_at,
                'type' => 'Disbursement',
                'badge' => 'success',
                'ref' => "DISB-{$pay->id}" . $refStr,
                'url' => null,
                'description' => $methodDesc . $refStr . $remarksStr,
                'debit' => (float)$pay->amount, // Debit reduces vendor payable
                'credit' => 0.00,
            ]);
        }

        foreach ($returns as $pr) {
            $desc = "Return {$pr->return_weight} kg" . ($pr->unit_price > 0 ? " @ ৳{$pr->unit_price}" : "") . ($pr->reason ? " ({$pr->reason})" : "") . ($pr->notes ? " - {$pr->notes}" : "");

            $transactions->push([
                'date' => $pr->return_date ? $pr->return_date->format('Y-m-d') : $pr->created_at->format('Y-m-d'),
                'created_at' => $pr->created_at,
                'type' => 'Purchase Return',
                'badge' => 'warning',
                'ref' => $pr->return_no,
                'url' => route('purchase-returns.show', $pr->id),
                'description' => $desc,
                'debit' => (float)$pr->total_refund_amount, // Debit reduces vendor payable
                'credit' => 0.00,
            ]);
        }

        $sortedTransactions = $transactions->sortBy(function ($item) {
            return $item['date'] . ' ' . $item['created_at'];
        })->values();

        $runningBalance = $openingBalance;
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        $ledgerRows = $sortedTransactions->map(function ($item) use (&$runningBalance, &$totalDebit, &$totalCredit) {
            // For Vendor: Net Payable = Opening + Credit (Purchases) - Debit (Disbursements)
            $runningBalance += ($item['credit'] - $item['debit']);
            $totalDebit += $item['debit'];
            $totalCredit += $item['credit'];
            $item['balance'] = $runningBalance;
            return $item;
        });

        $closingBalance = $runningBalance;

        return compact(
            'vendor',
            'fromDate',
            'toDate',
            'openingBalance',
            'ledgerRows',
            'totalDebit',
            'totalCredit',
            'closingBalance'
        );
    }
}