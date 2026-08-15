<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Mail};
use App\Models\{Customer, Inventory, Lot, Payment, Sale, SalesItem, User, Warehouse, Coil};
use App\Mail\CreateSalesMail;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Services\SaleService;
use Carbon\Carbon;
use Twilio\Rest\Client;

class SalesController extends Controller
{
    public function __construct(private SaleService $saleService) {}
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'salesPerson', 'warehouse']);

        // Filter by date range (from - to)
        if ($request->filled('from') && $request->filled('to')) {
            $from = date('Y-m-d 00:00:00', strtotime($request->from));
            $to = date('Y-m-d 23:59:59', strtotime($request->to));
            $query->whereBetween('sales.created_at', [$from, $to]);
        }

        // Filter by Month (YYYY-MM)
        if ($request->filled('month')) {
            $year = date('Y', strtotime($request->month));
            $month = date('m', strtotime($request->month));
            $query->whereYear('sales.created_at', $year)->whereMonth('sales.created_at', $month);
        }

        // Filter by Year (YYYY)
        if ($request->filled('year')) {
            $query->whereYear('sales.created_at', $request->year);
        }

        // Filter by sale_type
        if ($request->filled('sale_type') && $request->sale_type != 'all') {
            $query->where('sales.sale_type', $request->sale_type);
        }

        // Filter by search keyword / order number
        if ($request->filled('key')) {
            $key = $request->key;
            $query->where(function ($q) use ($key) {
                $q->where('sales.order_no', 'like', '%' . $key . '%')
                  ->orWhereHas('customer', function ($cq) use ($key) {
                      $cq->where('name', 'like', '%' . $key . '%')
                         ->orWhere('phone', 'like', '%' . $key . '%');
                  })
                  ->orWhereHas('client', function ($cq) use ($key) {
                      $cq->where('name', 'like', '%' . $key . '%')
                         ->orWhere('phone', 'like', '%' . $key . '%');
                  });
            });
        }

        // Export PDF of all matching sales records
        if ($request->search_for == 'pdf' || $request->export == 'pdf') {
            $html = view('pdf.sales', compact('services', 'request'))->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'Helvetica',
            ]);
            $mpdf->WriteHTML($html);
            return response($mpdf->Output('Sales_List_Report_' . now()->format('Y_m_d_His') . '.pdf', 'I'), 200, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $services = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $users = lib_salesMan();


        // Report & Revenue Stats
        $todaysRevenue = Sale::whereDate('created_at', Carbon::today())->sum('payble');
        $thisWeeksRevenue = Sale::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('payble');
        $thisMonthsRevenue = Sale::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('payble');
        $thisYearsRevenue = Sale::whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])->sum('payble');
        $totalServiceDues = 0;

        $todaysSalesRevenue = $todaysRevenue;
        $thisWeeksSalesRevenue = $thisWeeksRevenue;
        $thisMonthsSalesRevenue = $thisMonthsRevenue;
        $thisYearsSalesRevenue = $thisYearsRevenue;
        $totalSalesDues = Sale::where('due_payment', '>', 0)->sum('due_payment');

        $todaysDailySalesRevenue = 0;
        $thisWeeksDailySalesRevenue = 0;
        $thisMonthsDailySalesRevenue = 0;
        $thisYearsDailySalesRevenue = 0;

        $monthlyRevenue = Sale::selectRaw('MONTH(created_at) as month, SUM(payble) as total')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->mapWithKeys(function ($total, $month) {
                $monthName = Carbon::createFromFormat('m', $month)->format('M');
                return [$monthName => $total];
            });

        $yearlyRevenue = Sale::selectRaw('YEAR(created_at) as year, SUM(payble) as total')
            ->whereRaw('YEAR(created_at) >= YEAR(CURDATE()) - 9')
            ->groupBy('year')
            ->pluck('total', 'year');

        return view('frontend.pages.sales.index', compact('services', 'request', 'users', 'todaysRevenue', 'thisWeeksRevenue', 'thisMonthsRevenue', 'thisYearsRevenue', 'monthlyRevenue', 'yearlyRevenue', 'todaysSalesRevenue', 'thisWeeksSalesRevenue', 'thisMonthsSalesRevenue', 'thisYearsSalesRevenue', 'totalServiceDues', 'totalSalesDues', 'todaysDailySalesRevenue', 'thisWeeksDailySalesRevenue', 'thisMonthsDailySalesRevenue', 'thisYearsDailySalesRevenue'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users  = User::get();
        $products = collect();
        $coils = Coil::where('status', 'in_stock')->where('remaining_weight', '>', 0)->with(['lot', 'warehouse'])->latest()->get();
        $existingClients = Customer::select('id', 'name', 'phone', 'address')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due_payment')
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::with(['vendor'])->where('status', 'active')->orderBy('id', 'desc')->get();

        return view('frontend.pages.sales.create', compact('products', 'coils', 'users', 'existingClients', 'warehouses', 'lots'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            $sale = $this->saleService->createSale($request->validated());

            return redirect()->route('sales.invoice.pdf', $sale->id)
                ->with('success', 'Sale created successfully! Invoice #' . $sale->order_no);

        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Sale creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->withInput();
        }
    }

    private function getPaymentStatus($advancedPayment, $payble)
    {
        if ($advancedPayment == 0) {
            return 'pending';
        } elseif ($advancedPayment > 0 && $advancedPayment < $payble) {
            return 'partial';
        } elseif ($advancedPayment >= $payble) {
            return 'paid';
        }

        return 'pending';
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sales = Sale::with(['customer', 'items.lot.vendor'])->findOrFail($id);
        $users  = User::get();
        $products = collect();
        $customer = $sales->customer;
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::with(['vendor'])->where('status', 'active')->orderBy('id', 'desc')->get();
        $items = $sales->items;

        return view('frontend.pages.sales.edit', compact('sales', 'products', 'items', 'customer', 'warehouses', 'lots', 'users'));
    }

    
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'delivery_status' => 'nullable|string|in:pending,dispatched,delivered,partial_delivered',
            'note' => 'nullable|string|max:1000',
            'coil_id' => 'nullable|array',
            'lot_id' => 'nullable|array',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0.01',
            'unit_price' => 'required|array',
            'unit_price.*' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'labour_cost' => 'nullable|numeric|min:0',
            'weight_scale_cost' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'advanced_payment' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // FirstOrCreate customer
            $customer = Customer::firstOrCreate(
                ['name' => $validated['name'], 'phone' => $validated['phone']],
                ['address' => $validated['address'] ?? null]
            );

            // Fetch sale
            $sale = Sale::findOrFail($id);

            // Delete old sale items
            SalesItem::where('order_id', $sale->id)->delete();

            // Create new sale items
            $totalBill = 0;

            foreach ($validated['qty'] as $index => $qty) {
                $unitPrice = $validated['unit_price'][$index];
                $coilId = !empty($validated['coil_id'][$index]) ? $validated['coil_id'][$index] : null;
                $lotId = !empty($validated['lot_id'][$index]) ? $validated['lot_id'][$index] : null;

                $total = $unitPrice * $qty;
                $totalBill += $total;

                SalesItem::create([
                    'order_id' => $sale->id,
                    'coil_id' => $coilId,
                    'lot_id' => $lotId,
                    'unit_price' => $unitPrice,
                    'qty' => $qty,
                    'total_price' => $total,
                    'warranty' => 0,
                ]);
            }

            // Calculate totals
            $discount = $validated['discount'] ?? 0;
            if ($discount > $totalBill) $discount = $totalBill;

            $deliveryCharge = $validated['delivery_charge'] ?? 0;
            $labourCost = $validated['labour_cost'] ?? 0;
            $weightScaleCost = $validated['weight_scale_cost'] ?? 0;
            $otherCharges = $validated['other_charges'] ?? 0;

            $payble = max(0, $totalBill - $discount + $deliveryCharge + $labourCost + $weightScaleCost + $otherCharges);
            $advancedPayment = min($request->advanced_payment ?? 0, $payble);
            $duePayment = max(0, $payble - $advancedPayment);

            // Update sale
            $sale->update([
                'bill' => $totalBill,
                'total' => $totalBill,
                'discount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'labour_cost' => $labourCost,
                'weight_scale_cost' => $weightScaleCost,
                'other_charges' => $otherCharges,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'delivery_status' => $validated['delivery_status'] ?? 'pending',
                'note' => $validated['note'] ?? null,
                'payble' => $payble,
                'advanced_payment' => $advancedPayment,
                'due_payment' => $duePayment,
                'customer_id' => $customer->id,
            ]);

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $service = Sale::where('id', $id)->first();
        if (!$service) abort(404);
        $service->delete();

        return redirect()->back()->with(['success' => getNotify(3)]);
    }

    public function makeInvoice(Request $request, $serviceId)
    {
        $sales = Sale::with(['customer', 'returns.items.product', 'returns.processedBy'])->find($serviceId);
        if (!$sales) abort(404);

        $customer = $sales->customer;
        if (!$customer) {
            $customer = (object) [
                'name' => 'N/A',
                'phone' => 'N/A',
                'address' => 'N/A',
            ];
        }

        $items = SalesItem::with(['coil', 'lot.vendor'])
            ->where('order_id', $sales->id)
            ->get();

        // Get completed returns for this sale
        $returns = $sales->returns->where('status', 'completed');

        return view('frontend.pages.sales.invoice', compact('sales', 'items', 'customer', 'returns'));
    }

    public function downloadInvoicePdf($id)
    {
        $sales = Sale::with(['customer', 'warehouse', 'salesPerson', 'items.product', 'items.lot.vendor', 'returns.items.product', 'returns.processedBy'])->find($id);
        if (!$sales) {
            abort(404);
        }

        $customer = $sales->customer;
        if (!$customer) {
            $customer = (object) [
                'name' => 'N/A',
                'phone' => 'N/A',
                'address' => 'N/A',
            ];
        }

        $items = SalesItem::with(['product', 'lot.vendor'])
            ->where('order_id', $sales->id)
            ->get();

        $returns = $sales->returns ? $sales->returns->where('status', 'completed') : collect();

        try {
            ini_set('memory_limit', '512M');
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_top' => 42,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15,
                'default_font' => 'Helvetica',
            ]);

            $html = view('frontend.pages.sales.invoice_pdf', compact('sales', 'items', 'customer', 'returns'))->render();
            $mpdf->WriteHTML($html);

            return response($mpdf->Output(($sales->order_no ?? $sales->id) . '.pdf', \Mpdf\Output\Destination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($sales->order_no ?? $sales->id) . '.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Sales invoice PDF generation failed: ' . $e->getMessage(), [
                'sale_id' => $id,
            ]);

            return redirect()->back()->with('error', 'Failed to generate sales invoice PDF.');
        }
    }

    public function payments(Request $request, $saleId = null)
    {
        // Base query: only service payments
        $paymentsQuery = Payment::where('payment_for', 2);

        $sale = null;
        if ($saleId) {
            $paymentsQuery->where('sale_id', $saleId);
            $sale = Sale::with('customer')->findOrFail($saleId);
        }

        $defaultFilter = true;

        // Filter by date
        if (!empty($request->from) && !empty($request->to)) {
            $from = date('Y-m-d 00:00:00', strtotime($request->from));
            $to = date('Y-m-d 23:59:59', strtotime($request->to));
            $paymentsQuery->whereBetween('payments.created_at', [$from, $to]);
            $defaultFilter = false;
        }

        // Filter by payment method
        if (!empty($request->payments_method)) {
            $paymentsQuery->where('payments.payment_method', $request->payments_method);
            $defaultFilter = false;
        }

        // Default filter: current month if no filters and no sale selected
        if ($defaultFilter && !$saleId) {
            $startOfMonth = date('Y-m-01 00:00:00');
            $endOfMonth = date('Y-m-t 23:59:59');
            $paymentsQuery->whereBetween('payments.created_at', [$startOfMonth, $endOfMonth]);
        }

        $payments = $paymentsQuery->get();

        // PDF export
        // if ($request->search_for === 'pdf') {
        //     $pdf = Pdf::loadView('pdf.service_payments', compact('payments', 'request'))
        //         ->setPaper('A4', 'portrait');
        //     return $pdf->download('service_payments.pdf');
        // }

        return view('frontend.pages.sales.payments', compact('payments', 'request', 'saleId', 'sale'));
    }

    public function report(Request $request)
    {
        $salesQuery = DB::table('sales_items')
            ->join('sales', 'sales.id', '=', 'sales_items.order_id')
            ->leftJoin('coils', 'coils.id', '=', 'sales_items.coil_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->select(
                DB::raw("COALESCE(coils.coil_number, CONCAT('Steel ', COALESCE(sales_items.thickness, ''), ' ', COALESCE(sales_items.size, ''))) as product_name"),
                'sales.created_at as sale_date',
                'sales_items.qty',
                'sales_items.unit_price',
                'sales_items.total_price',
                'customers.name as customer_name',
                'customers.phone as customer_phone'
            );

        if ($request->filled('coil_id')) {
            $salesQuery->where('sales_items.coil_id', $request->coil_id);
        }

        if ($request->filled('customer_id')) {
            $salesQuery->where('sales.customer_id', $request->customer_id);
        }

        if ($request->filled('from')) {
            $salesQuery->whereDate('sales.created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $salesQuery->whereDate('sales.created_at', '<=', $request->to);
        }

        $salesReport = $salesQuery->orderBy('sales.created_at', 'desc')->get();

        $products = collect();
        $coils = Coil::select('id', 'coil_number')->get();
        $customers = Customer::select('id', 'name', 'phone')->orderBy('name')->get();

        return view('frontend.pages.report.sales.index', [
            'salesReport' => $salesReport,
            'products' => $products,
            'coils' => $coils,
            'customers' => $customers,
            'request' => $request
        ]);
    }

    public function reportPdf(Request $request)
    {
        $salesQuery = DB::table('sales_items')
            ->join('sales', 'sales.id', '=', 'sales_items.order_id')
            ->leftJoin('coils', 'coils.id', '=', 'sales_items.coil_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->select(
                DB::raw("COALESCE(coils.coil_number, CONCAT('Steel ', COALESCE(sales_items.thickness, ''), ' ', COALESCE(sales_items.size, ''))) as product_name"),
                'sales.created_at as sale_date',
                'sales_items.qty',
                'sales_items.unit_price',
                'sales_items.total_price',
                'customers.name as customer_name',
                'customers.phone as customer_phone'
            );

        if ($request->filled('coil_id')) {
            $salesQuery->where('sales_items.coil_id', $request->coil_id);
        }

        if ($request->filled('customer_id')) {
            $salesQuery->where('sales.customer_id', $request->customer_id);
        }

        if ($request->filled('from')) {
            $salesQuery->whereDate('sales.created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $salesQuery->whereDate('sales.created_at', '<=', $request->to);
        }

        $salesReport = $salesQuery->orderBy('sales.created_at', 'desc')->get();

        $html = view('frontend.pages.report.sales.pdf', compact('salesReport', 'request'))->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        return response($mpdf->Output('sales-report.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function getSaleDetails($id)
    {
        // Get sale info with customer info
        $sale = Sale::select(
            'sales.*',
            'customers.name',
            'customers.phone',
            'customers.address'
        )
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sales.id', $id)
            ->firstOrFail();

        // Get items for this sale with warranty info
        $items = \DB::table('sales_items')
            ->select(
                'sales_items.*',
                'products.name',
                'products.model',
                'sales_items.warranty',
                'sales_items.unit_price',
                'sales_items.qty',
                'sales_items.total_price'
            )
            ->join('products', 'products.id', '=', 'sales_items.product_id')
            ->where('sales_items.order_id', $id)
            ->get()
            ->map(function ($item) use ($sale) {
                $warrantyStart = \Carbon\Carbon::parse($sale->created_at);
                $warrantyEnd = $warrantyStart->copy()->addDays($item->warranty);
                $daysLeft = $warrantyEnd->isFuture() ? now()->diffInDays($warrantyEnd) : 0;

                $item->warranty_days_left = $daysLeft;
                return $item;
            });

        return response()->json([
            'sale' => $sale,
            'items' => $items,
        ]);
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $sale = Sale::findOrFail($request->sale_id);
            $paymentAmount = $request->payment_amount;

            // Check if payment amount exceeds due amount
            if ($paymentAmount > $sale->due_payment) {
                return redirect()->back()->with('error', 'Payment amount cannot exceed due amount!');
            }

            // Store due before payment for record
            $dueBeforePayment = $sale->due_payment;

            // Update sale payment information
            $newAdvancedPayment = $sale->advanced_payment + $paymentAmount;
            $newDuePayment = $sale->payble - $newAdvancedPayment;

            // Determine new payment status
            $paymentStatus = $this->getPaymentStatus($newAdvancedPayment, $sale->payble);

            // Update the sale
            $sale->update([
                'advanced_payment' => $newAdvancedPayment,
                'due_payment' => $newDuePayment,
                'payment_status' => 1,
            ]);

            // Create payment record
            Payment::create([
                'customer_id' => $sale->customer_id,
                'sale_id' => $sale->id,
                'amount' => $paymentAmount,
                'due_before_payment' => $dueBeforePayment,
                'due_after_payment' => $newDuePayment,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date ?: now(),
                'notes' => $request->notes,
                'payment_for' => 2, // Sales
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Payment of ৳' . number_format($paymentAmount, 2) . ' processed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }

//     public function duePayments()
// {
//     $sales = Sale::where('due_payment', '>', 0)
//                 ->latest()
//                 ->get();

//     return view('frontend.pages.sales.due-payments', compact('sales'));
// }

public function duePayments()
{
    $sales = Sale::with('customer')
        ->where('due_payment', '>', 0)
        ->latest()
        ->get();

    return view('frontend.pages.sales.due-payments', ['sales' => $sales]);
}

public function duePaymentsPdf()
{
    $sales = Sale::with('customer')
        ->where('due_payment', '>', 0)
        ->latest()
        ->get();

    $html = view('pdf.due_payments', ['sales' => $sales])->render();
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => 'Helvetica',
    ]);
    $mpdf->WriteHTML($html);

    return response($mpdf->Output('Due_Payments_Report_' . now()->format('Y_m_d_His') . '.pdf', 'I'), 200, [
        'Content-Type' => 'application/pdf',
    ]);
}

public function extraChargesReport(Request $request)
{
    $query = Sale::with(['customer', 'payoutUser'])
        ->where(function($q) {
            $q->where('delivery_charge', '>', 0)
              ->orWhere('labour_cost', '>', 0)
              ->orWhere('weight_scale_cost', '>', 0)
              ->orWhere('other_charges', '>', 0);
        });

    if ($request->filled('from') && $request->filled('to')) {
        $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
    } else {
        $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    if ($request->filled('payout_status') && $request->payout_status !== 'all') {
        $query->where('charges_payout_status', $request->payout_status);
    }

    $sales = $query->latest()->get();

    $totalDelivery = $sales->sum('delivery_charge');
    $totalLabour = $sales->sum('labour_cost');
    $totalScale = $sales->sum('weight_scale_cost');
    $totalOther = $sales->sum('other_charges');
    $totalCharges = $totalDelivery + $totalLabour + $totalScale + $totalOther;

    $totalPaidCharges = $sales->where('charges_payout_status', 'paid')->sum(function($s) {
        return (float)$s->delivery_charge + (float)$s->labour_cost + (float)$s->weight_scale_cost + (float)$s->other_charges;
    });
    $totalUnpaidCharges = $sales->where('charges_payout_status', '!=', 'paid')->sum(function($s) {
        return (float)$s->delivery_charge + (float)$s->labour_cost + (float)$s->weight_scale_cost + (float)$s->other_charges;
    });

    $paymentAccounts = \App\Models\ChartOfAccount::whereIn('account_code', ['1110', '1120'])
        ->orWhere('account_type', 'asset')
        ->where('level', '>=', 2)
        ->get();

    return view('frontend.pages.report.extra_charges.index', compact(
        'sales', 'totalDelivery', 'totalLabour', 'totalScale', 'totalOther', 'totalCharges',
        'totalPaidCharges', 'totalUnpaidCharges', 'paymentAccounts', 'request'
    ));
}

public function updateChargesPayoutStatus(Request $request, $id)
{
    $sale = Sale::findOrFail($id);

    $totalCharges = (float)$sale->delivery_charge + (float)$sale->labour_cost + (float)$sale->weight_scale_cost + (float)$sale->other_charges;

    if ($totalCharges <= 0) {
        return redirect()->back()->with('error', 'This invoice has no extra charges to pay out.');
    }

    $payoutDate = $request->input('payout_date', date('Y-m-d'));
    $payoutNote = $request->input('payout_note');
    $accountId = $request->input('payment_account_id');

    DB::beginTransaction();
    try {
        $sale->update([
            'charges_payout_status' => 'paid',
            'charges_payout_at' => $payoutDate ? \Carbon\Carbon::parse($payoutDate)->setTimeFrom(now()) : now(),
            'charges_payout_by' => auth()->id(),
            'charges_payout_note' => $payoutNote,
        ]);

        // Post Journal Entry: Debit 2140 (Pass-Through Payable), Credit 1110 (Cash/Bank)
        try {
            $chargesAcc = \App\Models\ChartOfAccount::where('account_code', '2140')->first();
            $cashAcc = $accountId ? \App\Models\ChartOfAccount::find($accountId) : \App\Models\ChartOfAccount::where('account_code', '1110')->first();

            if ($chargesAcc && $cashAcc) {
                // Delete previous payout entry for this sale if any
                \App\Models\JournalEntry::where('reference_type', 'charges_payout')
                    ->where('reference_id', $sale->id)
                    ->delete();

                postJournalEntry([
                    'entry_date' => $payoutDate,
                    'reference_type' => 'charges_payout',
                    'reference_id' => $sale->id,
                    'description' => "Worker extra charges payout for Invoice #{$sale->order_no}" . ($payoutNote ? " ({$payoutNote})" : ""),
                    'items' => [
                        [
                            'account_id' => $chargesAcc->id,
                            'debit' => $totalCharges,
                            'credit' => 0.00,
                            'description' => "Clear pass-through liability for Invoice #{$sale->order_no}"
                        ],
                        [
                            'account_id' => $cashAcc->id,
                            'debit' => 0.00,
                            'credit' => $totalCharges,
                            'description' => "Disburse cash/bank to workers/handlers for Invoice #{$sale->order_no}"
                        ]
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Charges payout journal posting skipped: " . $e->getMessage());
        }

        DB::commit();
        return redirect()->back()->with('success', 'Extra charges marked as Paid to workers successfully!');
    } catch (\Throwable $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to update payout status: ' . $e->getMessage());
    }
}

public function revertChargesPayoutStatus($id)
{
    $sale = Sale::findOrFail($id);

    DB::beginTransaction();
    try {
        $sale->update([
            'charges_payout_status' => 'unpaid',
            'charges_payout_at' => null,
            'charges_payout_by' => null,
            'charges_payout_note' => null,
        ]);

        // Remove the payout journal voucher
        \App\Models\JournalEntry::where('reference_type', 'charges_payout')
            ->where('reference_id', $sale->id)
            ->delete();

        DB::commit();
        return redirect()->back()->with('success', 'Extra charges status reverted to Unpaid / Pending.');
    } catch (\Throwable $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to revert payout status: ' . $e->getMessage());
    }
}

public function extraChargesReportPdf(Request $request)
{
    $query = Sale::with(['customer', 'payoutUser'])
        ->where(function($q) {
            $q->where('delivery_charge', '>', 0)
              ->orWhere('labour_cost', '>', 0)
              ->orWhere('weight_scale_cost', '>', 0)
              ->orWhere('other_charges', '>', 0);
        });

    if ($request->filled('from') && $request->filled('to')) {
        $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
    } else {
        $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    if ($request->filled('payout_status') && $request->payout_status !== 'all') {
        $query->where('charges_payout_status', $request->payout_status);
    }

    $sales = $query->latest()->get();

    $totalDelivery = $sales->sum('delivery_charge');
    $totalLabour = $sales->sum('labour_cost');
    $totalScale = $sales->sum('weight_scale_cost');
    $totalOther = $sales->sum('other_charges');
    $totalCharges = $totalDelivery + $totalLabour + $totalScale + $totalOther;

    $totalPaidCharges = $sales->where('charges_payout_status', 'paid')->sum(function($s) {
        return (float)$s->delivery_charge + (float)$s->labour_cost + (float)$s->weight_scale_cost + (float)$s->other_charges;
    });
    $totalUnpaidCharges = $sales->where('charges_payout_status', '!=', 'paid')->sum(function($s) {
        return (float)$s->delivery_charge + (float)$s->labour_cost + (float)$s->weight_scale_cost + (float)$s->other_charges;
    });

    $html = view('pdf.extra_charges_report', compact(
        'sales', 'totalDelivery', 'totalLabour', 'totalScale', 'totalOther', 'totalCharges',
        'totalPaidCharges', 'totalUnpaidCharges', 'request'
    ))->render();

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => 'Helvetica',
    ]);
    $mpdf->WriteHTML($html);

    return response($mpdf->Output('Extra_Charges_Report_' . now()->format('Y_m_d_His') . '.pdf', 'I'), 200, [
        'Content-Type' => 'application/pdf',
    ]);
}
}
