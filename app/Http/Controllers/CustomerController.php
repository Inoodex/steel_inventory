<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $customers = Customer::latest()->get();
        return view('frontend.pages.customer.index', compact('customers'));
    }

    public function downloadPdf()
    {
        ini_set('memory_limit', '512M');
        $customers = Customer::latest()->get();
        $html = view('pdf.customers', compact('customers'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('Customer_List_' . now()->format('Y_m_d_His') . '.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       return view('frontend.pages.customer.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    
        $attributes = $request->all();
        $rules = [
            'name' => 'required',
            'phone' => 'required|numeric|unique:customers,phone',
            'email' => 'nullable|email',
            'address' => 'required|string',
        ];
        $validation = Validator::make($attributes, $rules);
        if ($validation->fails()) {
            return redirect()->back()->with(['error' => getNotify(4), 'error_code' => 'edit'])->withErrors($validation)->withInput();
        }

        $customer = new Customer;
        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->email = $request->email;
        $customer->address = $request->address;
        $customer->status = '1';
        $customer->save();
    
        return redirect()->route('customers.index')->with(['success' => getNotify(1)]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);
        $sales = \App\Models\Sale::where('customer_id', $id)->latest()->get();
        return view('frontend.pages.customer.show', compact('customer', 'sales'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('frontend.pages.customer.edit',compact('customer'));
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $attributes = $request->all();
        $rules = [
            'name' => 'required',
            'phone' => 'required|numeric|unique:customers,phone,'. $id,
            'email' => 'nullable|email',
            'address' => 'required|string',
        ];
        $validation = Validator::make($attributes, $rules);
        if ($validation->fails()) {
            return redirect()->back()->with(['error' => getNotify(4), 'error_code' => 'edit'])->withErrors($validation)->withInput();
        }

        $customer = Customer::findOrFail($id);
        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->email = $request->email;
        $customer->address = $request->address;
        $customer->save();
    
        return redirect()->route('customers.index')->with(['success' => getNotify(2)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
       $customer = Customer::findOrFail($id);
       $customer->delete();
       return redirect()->back()->with(['success' => getNotify(3)]);
    }

    /**
     * Display Customer Party Ledger Statement
     */
    public function ledger(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $ledgerData = $this->getCustomerLedgerData($customer, $fromDate, $toDate);

        return view('frontend.pages.customer.ledger', $ledgerData);
    }

    /**
     * Download Customer Party Ledger as mPDF
     */
    public function ledgerPdf(Request $request, string $id)
    {
        ini_set('memory_limit', '512M');
        $customer = Customer::findOrFail($id);
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $ledgerData = $this->getCustomerLedgerData($customer, $fromDate, $toDate);

        $padPath = public_path('assets/invoice/inoodex_invoice.jpg');
        $padBase64 = file_exists($padPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($padPath)) : (function_exists('getInvoicePadBase64') ? getInvoicePadBase64() : '');
        $ledgerData['padBase64'] = $padBase64;

        $html = view('pdf.customer_ledger', $ledgerData)->render();

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

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $customer->name);
        $filename = "Customer_Ledger_{$safeName}_" . date('Ymd') . '.pdf';
        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Helper to compute customer ledger transactions & running balance
     */
    private function getCustomerLedgerData(Customer $customer, ?string $fromDate = null, ?string $toDate = null): array
    {
        $openingBalance = (float)($customer->opening_balance ?? 0.00);

        // If fromDate is set, compute prior transactions before fromDate
        if ($fromDate) {
            $priorSales = \App\Models\Sale::where('customer_id', $customer->id)
                ->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(order_date, created_at)'), '<', $fromDate)
                ->sum('payble');

            $priorPayments = \App\Models\Payment::where('customer_id', $customer->id)
                ->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '<', $fromDate)
                ->sum('amount');

            $priorReturns = \App\Models\ProductReturn::where('customer_id', $customer->id)
                ->where('status', '!=', 'rejected')
                ->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(return_date, created_at)'), '<', $fromDate)
                ->sum('total_refund_amount');

            $openingBalance += ($priorSales - $priorPayments - $priorReturns);
        }

        $salesQuery = \App\Models\Sale::where('customer_id', $customer->id);
        $paymentsQuery = \App\Models\Payment::where('customer_id', $customer->id);
        $returnsQuery = \App\Models\ProductReturn::where('customer_id', $customer->id)->where('status', '!=', 'rejected');

        if ($fromDate) {
            $salesQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(order_date, created_at)'), '>=', $fromDate);
            $paymentsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '>=', $fromDate);
            $returnsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(return_date, created_at)'), '>=', $fromDate);
        }
        if ($toDate) {
            $salesQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(order_date, created_at)'), '<=', $toDate);
            $paymentsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(payment_date, created_at)'), '<=', $toDate);
            $returnsQuery->whereDate(\Illuminate\Support\Facades\DB::raw('COALESCE(return_date, created_at)'), '<=', $toDate);
        }

        $sales = $salesQuery->get();
        $payments = $paymentsQuery->get();
        $returns = $returnsQuery->get();

        $transactions = collect();

        foreach ($sales as $s) {
            $transactions->push([
                'date' => $s->order_date ? $s->order_date : $s->created_at->format('Y-m-d'),
                'created_at' => $s->created_at,
                'type' => 'Sales Invoice',
                'badge' => 'primary',
                'ref' => $s->order_no,
                'url' => route('sales.show', $s->id),
                'description' => "Invoice for {$s->qty} pcs/units" . ($s->note ? " - {$s->note}" : ""),
                'debit' => (float)($s->payble ?: $s->total),
                'credit' => 0.00,
            ]);
        }

        foreach ($payments as $p) {
            $methodLabel = ucfirst($p->payment_method ?? 'cash');
            $refStr = $p->transaction_ref ? " [Ref: {$p->transaction_ref}]" : "";
            $transactions->push([
                'date' => $p->payment_date ? $p->payment_date : $p->created_at->format('Y-m-d'),
                'created_at' => $p->created_at,
                'type' => 'Payment Receipt',
                'badge' => 'success',
                'ref' => "RCPT-{$p->id}" . $refStr,
                'url' => $p->sale_id ? route('sales.show', $p->sale_id) : null,
                'description' => ($p->remarks ?: "Collection via {$methodLabel}") . $refStr,
                'debit' => 0.00,
                'credit' => (float)$p->amount,
            ]);
        }

        foreach ($returns as $r) {
            $transactions->push([
                'date' => $r->return_date ? $r->return_date : $r->created_at->format('Y-m-d'),
                'created_at' => $r->created_at,
                'type' => 'Sales Return',
                'badge' => 'warning',
                'ref' => "RET-{$r->id}",
                'url' => route('returns.show', $r->id),
                'description' => "Return Credit" . ($r->reason ? " - {$r->reason}" : ""),
                'debit' => 0.00,
                'credit' => (float)$r->total_refund_amount,
            ]);
        }

        // Sort chronologically
        $sortedTransactions = $transactions->sortBy(function ($item) {
            return $item['date'] . ' ' . $item['created_at'];
        })->values();

        $runningBalance = $openingBalance;
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        $ledgerRows = $sortedTransactions->map(function ($item) use (&$runningBalance, &$totalDebit, &$totalCredit) {
            $runningBalance += ($item['debit'] - $item['credit']);
            $totalDebit += $item['debit'];
            $totalCredit += $item['credit'];
            $item['balance'] = $runningBalance;
            return $item;
        });

        $closingBalance = $runningBalance;

        return compact(
            'customer',
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