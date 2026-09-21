<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Mail};
use App\Models\{Customer, Inventory, Lot, Payment, Sale, SalesItem, User, Warehouse, Coil, BankDetail};
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

        // Filter by search keyword / order number
        if ($request->filled('key')) {
            $key = $request->key;
            $query->where(function ($q) use ($key) {
                $q->where('sales.order_no', 'like', '%' . $key . '%')
                  ->orWhereHas('customer', function ($cq) use ($key) {
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
            return response($mpdf->Output('Sales_List_Report_' . now()->format('Y_m_d_His') . '.pdf', 'S'), 200, [
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
        $existingClients = Customer::select('id', 'name', 'phone', 'address', 'opening_balance')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due_payment')
            ->orderBy('name')
            ->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::with(['vendor'])->where('status', 'active')->orderBy('id', 'desc')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.sales.create', compact('products', 'coils', 'users', 'existingClients', 'warehouses', 'lots', 'bankAccounts'));
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
    public function show(string $id)
    {
        $sale = Sale::with([
            'customer' => function ($q) {
                $q->withSum('sales', 'due_payment');
            },
            'items.coil.warehouse',
            'items.lot.vendor',
            'warehouse',
            'salesPerson',
            'bankDetail',
            'payments' => function ($q) {
                $q->orderBy('id', 'desc');
            },
            'returns.items.product'
        ])->findOrFail($id);

        return view('frontend.pages.sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $sales = Sale::with([
            'customer' => function ($q) {
                $q->withSum('sales', 'due_payment');
            },
            'items.lot.vendor',
            'items.coil.warehouse',
            'bankDetail'
        ])->findOrFail($id);

        $users = User::get();
        $customer = $sales->customer;
        $items = $sales->items;

        $existingClients = Customer::select('id', 'name', 'phone', 'address', 'opening_balance')
            ->withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due_payment')
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $lots = Lot::with(['vendor'])->where('status', 'active')->orderBy('id', 'desc')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        // Get all in-stock coils + coils currently present in this sale order
        $coils = Coil::where(function($q) use ($sales) {
                $q->where('status', 'in_stock')->where('remaining_weight', '>', 0)
                  ->orWhereIn('id', $sales->items->pluck('coil_id')->filter());
            })
            ->with(['lot', 'warehouse'])
            ->latest()
            ->get();

        // Adjust memory available weight for coils already in this order so the user sees effective available stock
        $itemQuantities = $sales->items->groupBy('coil_id')->map(function ($rows) {
            return $rows->sum('qty');
        });
        foreach ($coils as $c) {
            if (isset($itemQuantities[$c->id])) {
                $c->remaining_weight = (float)$c->remaining_weight + (float)$itemQuantities[$c->id];
            }
        }

        return view('frontend.pages.sales.edit', compact('sales', 'items', 'customer', 'coils', 'existingClients', 'warehouses', 'lots', 'users', 'bankAccounts'));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'client_type' => 'nullable|in:new,existing',
            'existing_client_id' => 'nullable|exists:customers,id',
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'delivery_status' => 'nullable|string|in:pending,dispatched,delivered,partial_delivered',
            'note' => 'nullable|string|max:1000',
            'coil_id' => 'nullable|array',
            'lot_id' => 'nullable|array',
            'thickness' => 'nullable|array',
            'size' => 'nullable|array',
            'size_type' => 'nullable|array',
            'custom_size' => 'nullable|array',
            'qty' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',
            'unit_price' => 'required|array|min:1',
            'unit_price.*' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'labour_cost' => 'nullable|numeric|min:0',
            'weight_scale_cost' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'advanced_payment' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id' => 'nullable|exists:bank_details,id',
            'transaction_ref' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $sale = Sale::with('items')->findOrFail($id);

            // 1. Resolve Customer
            if (!empty($validated['existing_client_id'])) {
                $customer = Customer::findOrFail($validated['existing_client_id']);
            } else {
                $customer = Customer::firstOrCreate(
                    ['name' => $validated['name'], 'phone' => $validated['phone']],
                    ['address' => $validated['address'] ?? null]
                );
            }

            // 2. Restore coil weight for old sale items
            foreach ($sale->items as $oldItem) {
                if ($oldItem->coil_id && $oldItem->qty > 0) {
                    $oldCoil = Coil::find($oldItem->coil_id);
                    if ($oldCoil) {
                        $oldCoil->remaining_weight = (float)$oldCoil->remaining_weight + (float)$oldItem->qty;
                        if ($oldCoil->status === 'exhausted' && $oldCoil->remaining_weight > 0) {
                            $oldCoil->status = 'in_stock';
                        }
                        $oldCoil->save();
                    }
                }
            }

            // 3. Delete old sale items
            SalesItem::where('order_id', $sale->id)->delete();

            // 4. Create new sale items and deduct stock
            $totalBill = 0;
            $totalQty = 0;

            foreach ($validated['qty'] as $index => $qty) {
                $unitPrice = (float) $validated['unit_price'][$index];
                $qty = (float) $qty;
                $coilId = !empty($validated['coil_id'][$index]) ? $validated['coil_id'][$index] : null;
                $lotId = !empty($validated['lot_id'][$index]) ? $validated['lot_id'][$index] : null;
                $thickness = $request->thickness[$index] ?? null;
                $size = $request->size[$index] ?? null;
                $sizeType = $request->size_type[$index] ?? 'ft';
                $customSize = $request->custom_size[$index] ?? null;

                $purchasePrice = 0;
                if ($coilId) {
                    $coil = Coil::find($coilId);
                    if ($coil) {
                        $purchasePrice = (float) $coil->rate_per_ton;
                        $thickness = $thickness ?: $coil->thickness;
                        $size = $size ?: $coil->width;
                        $sizeType = $sizeType ?: $coil->length;
                        $lotId = $lotId ?: $coil->lot_id;

                        // Deduct new coil weight
                        $newRemaining = max(0, (float)$coil->remaining_weight - $qty);
                        $coil->remaining_weight = $newRemaining;
                        if ($newRemaining <= 0) {
                            $coil->status = 'exhausted';
                        }
                        $coil->save();
                    }
                }

                $total = $unitPrice * $qty;
                $totalBill += $total;
                $totalQty += $qty;
                $profit = ($unitPrice - $purchasePrice) * $qty;

                SalesItem::create([
                    'order_id' => $sale->id,
                    'coil_id' => $coilId,
                    'lot_id' => $lotId,
                    'thickness' => $thickness,
                    'size' => $size,
                    'size_type' => $sizeType,
                    'custom_size' => $customSize,
                    'unit_price' => $unitPrice,
                    'qty' => $qty,
                    'total_price' => $total,
                    'purchase_price' => $purchasePrice,
                    'profit' => $profit,
                ]);
            }

            // 5. Calculate totals
            $discount = (float)($validated['discount'] ?? 0);
            if ($discount > $totalBill) $discount = $totalBill;

            $vatPercent = (float)($validated['vat'] ?? 0);
            $taxPercent = (float)($validated['tax'] ?? 0);
            $vatAmount = round(($totalBill * $vatPercent) / 100, 2);
            $taxAmount = round(($totalBill * $taxPercent) / 100, 2);

            $deliveryCharge = (float)($validated['delivery_charge'] ?? 0);
            $labourCost = (float)($validated['labour_cost'] ?? 0);
            $weightScaleCost = (float)($validated['weight_scale_cost'] ?? 0);
            $otherCharges = (float)($validated['other_charges'] ?? 0);

            $extraCharges = $vatAmount + $taxAmount + $deliveryCharge + $labourCost + $weightScaleCost + $otherCharges;
            $total = max(0, round($totalBill + $extraCharges, 2));
            $payble = max(0, round($total - $discount, 2));

            $advancedPayment = (float)($request->advanced_payment ?? 0);
            $duePayment = max(0, round($payble - $advancedPayment, 2));

            $status = match(true) {
                $duePayment <= 0 => 'paid',
                $advancedPayment > 0 => 'partial',
                default => 'credit',
            };

            // 6. Update sale record
            $sale->update([
                'customer_id' => $customer->id,
                'qty' => $totalQty,
                'subtotal' => $totalBill,
                'bill' => $totalBill,
                'total' => $total,
                'discount' => $discount,
                'vat' => $vatPercent,
                'tax' => $taxPercent,
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
                'status' => $status,
                'payment_method' => $validated['payment_method'] ?? $sale->payment_method ?? 'cash',
                'bank_detail_id' => !empty($validated['bank_detail_id']) ? $validated['bank_detail_id'] : null,
                'transaction_ref' => $validated['transaction_ref'] ?? null,
            ]);

            // 7. Sync initial payment entry
            $initialPayment = Payment::where('sale_id', $sale->id)->where('payment_for', 2)->first();
            if ($advancedPayment > 0) {
                if ($initialPayment) {
                    $initialPayment->update([
                        'customer_id' => $customer->id,
                        'amount' => $advancedPayment,
                        'payment_method' => $sale->payment_method,
                        'bank_detail_id' => $sale->bank_detail_id,
                        'transaction_ref' => $sale->transaction_ref,
                    ]);
                } else {
                    Payment::create([
                        'customer_id' => $customer->id,
                        'sale_id' => $sale->id,
                        'payment_for' => 2,
                        'payment_method' => $sale->payment_method ?? 'cash',
                        'bank_detail_id' => $sale->bank_detail_id,
                        'transaction_ref' => $sale->transaction_ref,
                        'amount' => $advancedPayment,
                        'remarks' => 'Initial receipt for ' . $sale->order_no,
                        'status' => 1,
                        'created_by' => Auth::id(),
                    ]);
                }
            } elseif ($initialPayment) {
                $initialPayment->delete();
            }

            DB::commit();

            return redirect()->route('sales.show', $sale->id)->with('success', 'Sale order #' . $sale->order_no . ' updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with(['error' => $e->getMessage()])->withInput();
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
        return $this->downloadInvoicePdf($serviceId);
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

        // Calculate customer previous due prior to this sale
        $previousDue = 0.00;
        if ($sales->customer_id) {
            $priorSalesDues = (float) Sale::where('customer_id', $sales->customer_id)
                ->where('id', '<', $sales->id)
                ->whereNull('deleted_at')
                ->sum('due_payment');

            $custOpeningBalance = (float) ($sales->customer->opening_balance ?? 0.00);
            $previousDue = max(0.00, $priorSalesDues + $custOpeningBalance);
        }
        $sales->previous_due = $previousDue;

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

            $pdfContent = $mpdf->Output(($sales->order_no ?? $sales->id) . '.pdf', \Mpdf\Output\Destination::STRING_RETURN);

            return response($pdfContent, 200, [
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
        return response($mpdf->Output('sales-report.pdf', 'S'), 200, [
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

        // Get items for this sale — join coils (not products) for steel system
        $items = DB::table('sales_items')
            ->select(
                'sales_items.*',
                'coils.coil_number as name',
                'coils.thickness as model',
                'sales_items.unit_price',
                'sales_items.qty',
                'sales_items.total_price'
            )
            ->leftJoin('coils', 'coils.id', '=', 'sales_items.coil_id')
            ->where('sales_items.order_id', $id)
            ->get()
            ->map(function ($item) use ($sale) {
                $item->warranty_days_left = 0;
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
        $sales = Sale::with(['customer', 'warehouse'])
            ->where('due_payment', '>', 0)
            ->latest()
            ->get();

        $customersWithDue = Customer::withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due_payment')
            ->get()
            ->map(function($c) {
                $c->opening_due = (float)($c->opening_balance ?? 0);
                $c->sales_due = (float)($c->sales_sum_due_payment ?? 0);
                $c->total_due = $c->opening_due + $c->sales_due;
                return $c;
            })
            ->filter(function($c) {
                return $c->total_due > 0;
            })
            ->sortByDesc('total_due')
            ->values();

        $totalOpeningDues = (float) Customer::sum('opening_balance');
        $totalInvoiceDues = (float) Sale::where('due_payment', '>', 0)->sum('due_payment');
        $grandTotalDues = $totalOpeningDues + $totalInvoiceDues;
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.sales.due-payments', compact(
            'sales',
            'customersWithDue',
            'totalOpeningDues',
            'totalInvoiceDues',
            'grandTotalDues',
            'bankAccounts'
        ));
    }

    public function duePaymentsPdf()
    {
        $sales = Sale::with(['customer', 'warehouse'])
            ->where('due_payment', '>', 0)
            ->latest()
            ->get();

        $customersWithDue = Customer::withSum(['sales' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due_payment')
            ->get()
            ->map(function($c) {
                $c->opening_due = (float)($c->opening_balance ?? 0);
                $c->sales_due = (float)($c->sales_sum_due_payment ?? 0);
                $c->total_due = $c->opening_due + $c->sales_due;
                return $c;
            })
            ->filter(function($c) {
                return $c->total_due > 0;
            })
            ->sortByDesc('total_due')
            ->values();

        $totalOpeningDues = (float) Customer::sum('opening_balance');
        $totalInvoiceDues = (float) Sale::where('due_payment', '>', 0)->sum('due_payment');
        $grandTotalDues = $totalOpeningDues + $totalInvoiceDues;

        $html = view('pdf.due_payments', compact(
            'sales',
            'customersWithDue',
            'totalOpeningDues',
            'totalInvoiceDues',
            'grandTotalDues'
        ))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'Helvetica',
        ]);
        $mpdf->WriteHTML($html);
        $filename = 'Due_Payments_Report_' . now()->format('Y_m_d_His') . '.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
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

    return response($mpdf->Output('Extra_Charges_Report_' . now()->format('Y_m_d_His') . '.pdf', 'S'), 200, [
        'Content-Type' => 'application/pdf',
    ]);
}
}
