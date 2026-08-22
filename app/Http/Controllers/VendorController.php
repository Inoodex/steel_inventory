<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Service, User, Vendor, Purchase};
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $vendors = Vendor::latest()->get();
        $customers = $vendors; // Alias for compatibility
        return view('frontend.pages.vendor.index', compact('vendors', 'customers'));
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
            'name' => 'required',
            'phone' => 'required|numeric|unique:vendors,phone',
            'email' => 'nullable|email',
            'address' => 'required|string',
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
        $vendor->status = '1';
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
            'name' => 'required',
            'phone' => 'required|numeric|unique:vendors,phone,'. $id,
            'email' => 'nullable|email',
            'address' => 'required|string',
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
        $vendors = Vendor::all();
        $html = view('pdf.vendors', compact('vendors'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('vendor-list.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
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
        ini_set('memory_limit', '512M');
        $vendor = Vendor::findOrFail($id);
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $ledgerData = $this->getVendorLedgerData($vendor, $fromDate, $toDate);

        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
        $ledgerData['padBase64'] = $padBase64;

        $html = view('pdf.vendor_ledger', $ledgerData)->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
            'margin_top' => 45,
            'margin_bottom' => 25,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        $mpdf->WriteHTML($html);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $vendor->name);
        $filename = "Vendor_Ledger_{$safeName}_" . date('Ymd') . '.pdf';
        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
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

            $openingBalance += ($priorPurchases - $priorPayments);
        }

        $purchasesQuery = \App\Models\Purchase::where('vendor_id', $vendor->id);
        $paymentsQuery = \App\Models\Payment::where('vendor_id', $vendor->id);

        if ($fromDate) {
            $purchasesQuery->whereDate('created_at', '>=', $fromDate);
            $paymentsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '>=', $fromDate);
        }
        if ($toDate) {
            $purchasesQuery->whereDate('created_at', '<=', $toDate);
            $paymentsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '<=', $toDate);
        }

        $purchases = $purchasesQuery->get();
        $payments = $paymentsQuery->get();

        $transactions = collect();

        foreach ($purchases as $p) {
            $lotStr = $p->lot ? " (Lot: {$p->lot->lot_number})" : "";
            $transactions->push([
                'date' => $p->created_at->format('Y-m-d'),
                'created_at' => $p->created_at,
                'type' => 'Purchase Bill',
                'badge' => 'primary',
                'ref' => "PO #{$p->id}",
                'url' => route('purchase.show', $p->id),
                'description' => "Purchase intake {$p->quantity} qty ({$p->total_weight} tons)" . $lotStr,
                'debit' => 0.00,
                'credit' => (float)$p->total_price, // Credit increases vendor payable
            ]);
        }

        foreach ($payments as $pay) {
            $methodLabel = ucfirst($pay->payment_method ?? 'cash');
            $refStr = $pay->transaction_ref ? " [Ref: {$pay->transaction_ref}]" : "";
            $transactions->push([
                'date' => $pay->payment_date ? $pay->payment_date : $pay->created_at->format('Y-m-d'),
                'created_at' => $pay->created_at,
                'type' => 'Disbursement',
                'badge' => 'success',
                'ref' => "DISB-{$pay->id}" . $refStr,
                'url' => null,
                'description' => ($pay->remarks ?: "Disbursement via {$methodLabel}") . $refStr,
                'debit' => (float)$pay->amount, // Debit reduces vendor payable
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