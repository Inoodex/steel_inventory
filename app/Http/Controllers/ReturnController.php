<?php

namespace App\Http\Controllers;

use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Inventory;
use App\Http\Requests\StoreReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    /**
     * Display a listing of returns.
     */
    public function index(Request $request)
    {
        $query = ProductReturn::with(['sale', 'customer', 'items.salesItem'])
            ->latest();

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('return_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('return_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('sale', function ($sq) use ($search) {
                        $sq->where('order_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $returns = $query->get();
        $customers = Customer::all();

        return view('frontend.pages.returns.index', compact('returns', 'customers'));
    }

    /**
     * Show form to create a new return.
     */
    public function create(Request $request)
    {
        $sale = null;
        $saleItems = [];

        if ($request->filled('sale_id')) {
            $sale = Sale::with(['customer', 'items'])->find($request->sale_id);
            if ($sale) {
                $saleItems = $sale->items;
            }
        }

        $sales = Sale::with('customer')
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->get();

        $customers = Customer::all();
        $products = collect();

        return view('frontend.pages.returns.create', compact('sale', 'saleItems', 'sales', 'customers', 'products'));
    }

    /**
     * Store a newly created return.
     */
    public function store(StoreReturnRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $sale = Sale::findOrFail($validated['sale_id']);

            // Create return
            $return = ProductReturn::create([
                'sale_id'             => $sale->id,
                'customer_id'         => $sale->customer_id,
                'return_date'         => $validated['return_date'],
                'status'              => 'pending',
                'reason'              => $validated['reason'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'total_refund_amount' => 0
            ]);

            // Create return items
            foreach ($validated['items'] as $item) {
                $salesItemId = $item['sales_item_id'] ?? null;
                $productId = $item['product_id'] ?? null;

                if (!$productId && $salesItemId) {
                    $sItem = \App\Models\SalesItem::find($salesItemId);
                    if ($sItem) {
                        $productId = $sItem->coil_id ?? $sItem->product_id;
                    }
                }

                $qty = (float)($item['quantity'] ?? 0);
                $unitPrice = (float)($item['unit_price'] ?? 0);

                ReturnItem::create([
                    'return_id'     => $return->id,
                    'sales_item_id' => $salesItemId,
                    'product_id'    => $productId,
                    'quantity'      => $qty,
                    'unit_price'    => $unitPrice,
                    'total_price'   => $qty * $unitPrice,
                    'return_reason' => $item['return_reason'] ?? 'other',
                    'condition'     => $item['condition'] ?? 'good',
                    'notes'         => $item['notes'] ?? null,
                ]);
            }

            // Update total refund amount
            $return->total_refund_amount = $return->calculateTotalRefund();
            $return->save();

            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Success!',
                'text'  => 'Return request created. Status: Pending Approval.',
            ]);

            return redirect()->route('returns.show', $return->id)
                ->with('success', 'Return request created successfully. Status: Pending');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Error creating return: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified return.
     */
    public function show($id)
    {
        $return = ProductReturn::with(['sale', 'customer', 'items.salesItem', 'processedBy'])->findOrFail($id);
        return view('frontend.pages.returns.show', compact('return'));
    }

    /**
     * Approve the return (immediately updates stock and adjusts sales balance).
     */
    public function approve($id)
    {
        $return = ProductReturn::with('items')->findOrFail($id);

        if (!$return->isPending()) {
            return back()->with('error', 'Only pending returns can be approved');
        }

        DB::beginTransaction();

        try {
            $return->approve(auth()->id());
            DB::commit();

            session()->flash('sweet_alert', [
                'type'  => 'success',
                'title' => 'Return Approved!',
                'text'  => 'Return approved. Stock has been updated and sale balance adjusted.',
            ]);

            return back()->with('success', 'Return approved successfully. Stock updated and sale balance adjusted.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error approving return: ' . $e->getMessage());
        }
    }

    /**
     * Complete the return (alias for backward compatibility).
     */
    public function complete($id)
    {
        return $this->approve($id);
    }

    /**
     * Reject the return.
     */
    public function reject(Request $request, $id)
    {
        $return = ProductReturn::findOrFail($id);

        if (!$return->isPending()) {
            return back()->with('error', 'Return is not in pending status');
        }

        $return->reject(auth()->id(), $request->reason);

        return back()->with('success', 'Return rejected');
    }

    /**
     * Get sale items for return (AJAX).
     */
    public function getSaleItems($saleId)
    {
        $sale = Sale::with(['items.coil', 'items.product', 'customer'])->find($saleId);

        if (!$sale) {
            return response()->json(['error' => 'Sale not found'], 404);
        }

        return response()->json([
            'sale' => $sale,
            'items' => $sale->items->map(function ($item) {
                $coilNo = $item->coil->coil_number ?? $item->product->coil_number ?? null;
                $name = $coilNo ? ('Coil #' . $coilNo) : ('Item #' . $item->id);
                if ($item->size) {
                    $name .= " ({$item->size} {$item->size_type})";
                }
                return [
                    'id'            => $item->id,
                    'sales_item_id' => $item->id,
                    'product_id'    => $item->coil_id ?? $item->product_id,
                    'product_name'  => $name,
                    'quantity'      => (float)$item->qty,
                    'unit_price'    => (float)$item->unit_price,
                    'total_price'   => (float)$item->total_price
                ];
            })
        ]);
    }

    /**
     * Remove the specified return.
     */
    public function destroy($id)
    {
        $return = ProductReturn::findOrFail($id);

        if (!$return->isPending() && !$return->isRejected()) {
            return back()->with('error', 'Only pending or rejected returns can be deleted');
        }

        DB::beginTransaction();

        try {
            // Delete return items first
            $return->items()->delete();
            // Delete return
            $return->delete();

            DB::commit();

            return redirect()->route('returns.index')
                ->with('success', 'Return deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Error deleting return: ' . $e->getMessage());
        }
    }
}
