<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Purchase;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Models\Coil;
use App\Models\BankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePurchaseRequest;
use App\Services\PurchaseService;
use Carbon\Carbon;
use App\Services\PdfService;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService) {}

    /**
     * Display a listing of the resource.
     * Consolidates purchases by Lot into 1 row with expandable item details.
     */
    public function index(Request $request)
    {
        $query = Lot::with(['vendor', 'purchases' => function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            })->with(['warehouse', 'coils'])->latest();
        }])->whereHas('purchases', function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        });

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })->orWhereHas('purchases', function ($q) use ($search) {
                      $q->where('thickness', 'like', "%{$search}%")
                        ->orWhere('size', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('coils', function ($q) use ($search) {
                            $q->where('coil_number', 'like', "%{$search}%");
                        });
                  });
            });
        }

        // Filter by lot
        if ($request->filled('lot_id')) {
            $query->where('id', $request->lot_id);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->whereHas('purchases', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Filter by date range
        if ($request->filled('from') && $request->filled('to')) {
            $from = date('Y-m-d 00:00:00', strtotime($request->from));
            $to = date('Y-m-d 23:59:59', strtotime($request->to));
            $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('lot_date', [$from, $to])
                  ->orWhereBetween('created_at', [$from, $to]);
            });
        }

        $lots = $query->latest()->paginate(15)->withQueryString();
        $purchases = $lots; // Alias for backward compatibility

        // Overall summary statistics (Yard / Main Warehouse purchases only)
        $yardPurchaseQuery = Purchase::whereDoesntHave('warehouse', function ($wq) {
            $wq->where('type', 'shop');
        });
        $totalLotsCount  = Lot::whereHas('purchases', function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        })->count();
        $totalOrderValue = (float) (clone $yardPurchaseQuery)->sum('total_price');
        $totalPaid       = (float) (clone $yardPurchaseQuery)->sum('payment');
        $totalDue        = (float) (clone $yardPurchaseQuery)->sum('due');
        $totalWeight     = (float) (clone $yardPurchaseQuery)->sum('total_weight');

        $vendors      = Vendor::active()->orderBy('name')->get();
        $allLots      = Lot::where('status', 'active')
            ->whereDoesntHave('purchases', function ($q) {
                $q->whereHas('warehouse', function ($wq) {
                    $wq->where('type', 'shop');
                });
            })
            ->latest()->get();
        $warehouses   = Warehouse::warehouses()->where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.index', compact(
            'lots',
            'purchases',
            'vendors',
            'allLots',
            'warehouses',
            'bankAccounts',
            'totalLotsCount',
            'totalOrderValue',
            'totalPaid',
            'totalDue',
            'totalWeight'
        ));
    }

    public function create()
    {
        $products   = collect();
        $vendors    = Vendor::active()->orderBy('name')->get();
        $lots       = Lot::with(['vendor', 'purchases.warehouse'])
            ->where('status', 'active')
            ->whereDoesntHave('purchases', function ($q) {
                $q->whereHas('warehouse', function ($wq) {
                    $wq->where('type', 'shop');
                });
            })
            ->latest()->get();
        $warehouses = Warehouse::warehouses()->where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();
        $suggestedLotNumber = Lot::generateLotNumber();

        return view('frontend.pages.purchase.create', compact('products', 'vendors', 'lots', 'warehouses', 'bankAccounts', 'suggestedLotNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        try {
            $data = $request->validated();
            $warehouse = !empty($data['warehouse_id']) ? Warehouse::find($data['warehouse_id']) : null;
            $isShop = $warehouse && $warehouse->type === 'shop';

            if (!empty($data['items']) && is_array($data['items'])) {
                $purchases = $this->purchaseService->createPurchasesBatch($data);
                $count = count($purchases);
                if ($isShop) {
                    return redirect()->route('shops.purchases.index')
                        ->with('success', "{$count} shop purchase items recorded and stock updated successfully.");
                }
                return redirect()->route('purchase.index')
                    ->with('success', "{$count} purchase items recorded and stock updated successfully.");
            } else {
                $this->purchaseService->createPurchase($data);
                if ($isShop) {
                    return redirect()->route('shops.purchases.index')
                        ->with('success', 'Shop purchase created and stock updated successfully.');
                }
                return redirect()->route('purchase.index')
                    ->with('success', 'Purchase created and stock updated successfully.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to save purchase: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['vendor', 'lot.purchases.coils', 'lot.purchases.warehouse', 'warehouse', 'bankDetail', 'coils', 'payments', 'creator', 'updater']);

        // Consignment purchases: all purchases under the same Lot, or just this purchase if standalone
        if ($purchase->lot && $purchase->lot->purchases->isNotEmpty()) {
            $consignmentPurchases = $purchase->lot->purchases;
        } else {
            $consignmentPurchases = collect([$purchase]);
        }

        // Flatten all coils in this consignment
        $allCoils = $consignmentPurchases->flatMap->coils;

        // Consignment aggregate calculations
        $consignmentSubTotal    = (float) $consignmentPurchases->sum(fn($p) => (float)($p->sub_price ?: $p->total_price));
        $consignmentDelivery    = (float) $consignmentPurchases->sum('delivery_charge');
        $consignmentLabour      = (float) $consignmentPurchases->sum('labour_cost');
        $consignmentScale       = (float) $consignmentPurchases->sum('weight_scale_cost');
        $consignmentOther       = (float) $consignmentPurchases->sum('other_charges');
        $consignmentDiscount    = (float) $consignmentPurchases->sum('discount');
        $consignmentExtra       = $consignmentDelivery + $consignmentLabour + $consignmentScale + $consignmentOther;
        $consignmentGrandTotal  = max(0, round($consignmentSubTotal + $consignmentExtra - $consignmentDiscount, 2));
        $consignmentPayment     = (float) $consignmentPurchases->sum('payment');
        $consignmentDue         = max(0, round($consignmentGrandTotal - $consignmentPayment, 2));
        $consignmentTotalWeight = (float) $consignmentPurchases->sum('total_weight');
        $consignmentTotalQty    = (int) $consignmentPurchases->sum('quantity');

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.show', compact(
            'purchase',
            'consignmentPurchases',
            'allCoils',
            'consignmentSubTotal',
            'consignmentDelivery',
            'consignmentLabour',
            'consignmentScale',
            'consignmentOther',
            'consignmentDiscount',
            'consignmentExtra',
            'consignmentGrandTotal',
            'consignmentPayment',
            'consignmentDue',
            'consignmentTotalWeight',
            'consignmentTotalQty',
            'bankAccounts'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load(['vendor', 'lot', 'warehouse', 'coils', 'bankDetail', 'creator']);
        $vendors = Vendor::active()->orderBy('name')->get();
        $lots = Lot::where('status', 'active')->latest()->get();
        $warehouses = $purchase->warehouse?->isShop() 
            ? Warehouse::where('status', 'active')->orderBy('name')->get() 
            : Warehouse::warehouses()->where('status', 'active')->orderBy('name')->get();
        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.edit', compact('purchase', 'vendors', 'lots', 'warehouses', 'bankAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $isBreakdown = $request->boolean('breakdown_mode') || $request->input('breakdown_mode') === '1' || $request->has('coils');

        $request->validate([
            'lot_id'          => 'required|exists:lots,id',
            'warehouse_id'    => 'required|exists:warehouses,id',
            'vendor_id'       => 'required|exists:vendors,id',
            'product_name'    => 'nullable|string|max:255',
            'coil_number'     => 'nullable|string|max:100',
            'thickness'       => 'nullable|string|max:100',
            'size'            => 'nullable|string|max:100',
            'size_type'       => 'nullable|string|max:50',
            'unit_weight'     => $isBreakdown ? 'nullable|numeric|min:0' : 'required|numeric|min:0.001',
            'total_weight'    => 'nullable|numeric|min:0',
            'quantity'        => 'nullable|numeric|min:1',
            'unit_price'      => 'required|numeric|min:0',
            'sub_price'       => 'nullable|numeric',
            'delivery_charge' => 'nullable|numeric|min:0',
            'transport_payer' => 'nullable|string|in:me,vendor',
            'labour_cost'     => 'nullable|numeric|min:0',
            'weight_scale_cost' => 'nullable|numeric|min:0',
            'other_charges'   => 'nullable|numeric|min:0',
            'discount'        => 'nullable|numeric|min:0',
            'total_price'     => 'nullable|numeric|min:0',
            'payment'         => 'required|numeric|min:0',
            'due'             => 'nullable|numeric|min:0',
            'payment_method'  => 'nullable|string|in:cash,bank,cheque,mobile_banking',
            'bank_detail_id'  => 'nullable|exists:bank_details,id',
            'transaction_ref' => 'nullable|string|max:255',
            'notes'           => 'nullable|string|max:500',
            'coil_notes'      => 'nullable|string|max:500',
            'coils'           => $isBreakdown ? 'required|array|min:1' : 'nullable|array',
            'coils.*.weight'  => $isBreakdown ? 'required|numeric|min:0.001' : 'nullable|numeric',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $purchase = Purchase::with('coils')->findOrFail($purchase->id);
            $oldLotId = $purchase->lot_id;
            $existingCoils = $purchase->coils->keyBy('id');

            if ($isBreakdown) {
                $submittedCoils = $request->input('coils', []);
                $totalBreakdownWeight = 0;
                foreach ($submittedCoils as $sc) {
                    $totalBreakdownWeight += (float) ($sc['weight'] ?? 0);
                }

                if ($totalBreakdownWeight <= 0) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return redirect()->back()->withInput()->with('error', 'Total breakdown weight must be greater than 0 kg.');
                }

                // Sold weight safety check across existing coils
                foreach ($existingCoils as $oldCoil) {
                    $soldWeight = max(0, (float)$oldCoil->net_weight - (float)$oldCoil->remaining_weight);
                    if ($soldWeight > 0) {
                        $matched = collect($submittedCoils)->first(fn($sc) => isset($sc['id']) && $sc['id'] == $oldCoil->id);
                        if (!$matched) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            return redirect()->back()->withInput()->with('error', "Cannot remove coil #{$oldCoil->coil_number} because " . number_format($soldWeight, 2) . " kg has already been sold.");
                        }
                        if ((float)$matched['weight'] < $soldWeight) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            return redirect()->back()->withInput()->with('error', "Cannot reduce weight of coil #{$oldCoil->coil_number} below " . number_format($soldWeight, 2) . " kg because that amount has already been sold.");
                        }
                    }
                }

                $coilQty = count($submittedCoils);
                $totalWeight = $totalBreakdownWeight;
                $perCoilWeight = $coilQty > 0 ? round($totalWeight / $coilQty, 3) : $totalWeight;
            } else {
                $coil = $existingCoils->first();
                $coilQty = max(1, (int) $request->quantity);
                $perCoilWeight = (float) $request->unit_weight;
                $calculatedTotalWeight = $coilQty * $perCoilWeight;
                $totalWeight = (float) ($request->total_weight ?: $calculatedTotalWeight);
                if ($totalWeight <= 0 && $calculatedTotalWeight > 0) {
                    $totalWeight = $calculatedTotalWeight;
                }

                // Sold weight protection check
                if ($coil) {
                    $soldWeight = max(0, (float)$coil->net_weight - (float)$coil->remaining_weight);
                    if ($soldWeight > 0 && $totalWeight < $soldWeight) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        return redirect()->back()->withInput()->with('error', "Cannot reduce total intake weight below " . number_format($soldWeight, 2) . " kg because this amount has already been sold and dispatched.");
                    }
                }
            }

            $rate = (float) $request->unit_price;
            $subPrice = (float) ($request->sub_price ?: ($totalWeight * $rate));

            $deliveryCharge  = (float) ($request->delivery_charge ?? 0);
            $transportPayer  = $request->input('transport_payer', 'me');
            $vendorDelivery  = ($transportPayer === 'vendor') ? $deliveryCharge : 0;
            $labourCost      = (float) ($request->labour_cost ?? 0);
            $weightScaleCost = (float) ($request->weight_scale_cost ?? 0);
            $otherCharges    = (float) ($request->other_charges ?? 0);
            $discount        = (float) ($request->discount ?? 0);
            $netExtraCharges = ($vendorDelivery + $labourCost + $weightScaleCost + $otherCharges) - $discount;

            $totalPrice = max(0, round($subPrice + $netExtraCharges, 2));
            $payment = (float) $request->payment;
            $due = max(0, round($totalPrice - $payment, 2));

            $purchase->lot_id            = $request->lot_id;
            $purchase->warehouse_id      = $request->warehouse_id;
            $purchase->vendor_id         = $request->vendor_id;
            $purchase->product_name      = $request->product_name;
            $purchase->thickness         = $request->thickness;
            $purchase->size              = $request->size;
            $purchase->size_type         = $request->size_type ?: 'ft';
            $purchase->unit_weight       = $perCoilWeight;
            $purchase->total_weight      = $totalWeight;
            $purchase->quantity          = $coilQty;
            $purchase->unit_price        = $rate;
            $purchase->sub_price         = $subPrice;
            $purchase->delivery_charge   = $deliveryCharge;
            $purchase->transport_payer   = $transportPayer;
            $purchase->labour_cost       = $labourCost;
            $purchase->weight_scale_cost = $weightScaleCost;
            $purchase->other_charges     = $otherCharges;
            $purchase->discount          = $discount;
            $purchase->total_price       = $totalPrice;
            $purchase->payment           = $payment;
            $purchase->due               = $due;
            $purchase->payment_method    = $request->payment_method ?? 'cash';
            $purchase->bank_detail_id    = ($request->payment_method !== 'cash') ? $request->bank_detail_id : null;
            $purchase->transaction_ref   = ($request->payment_method !== 'cash') ? $request->transaction_ref : null;
            $purchase->notes             = $request->notes;
            $purchase->updated_by        = Auth::id();
            $purchase->save();

            // Synchronize linked Coil record(s) in yard stock
            if ($isBreakdown) {
                $retainedCoilIds = [];
                foreach ($submittedCoils as $sc) {
                    $cWeight = (float) ($sc['weight'] ?? 0);
                    if ($cWeight <= 0) continue;

                    $cNumber = !empty($sc['coil_number']) ? trim($sc['coil_number']) : null;
                    $cProduct = !empty($sc['product_name']) ? trim($sc['product_name']) : $request->product_name;
                    $cThickness = !empty($sc['thickness']) ? trim($sc['thickness']) : $request->thickness;
                    $cSize = !empty($sc['size']) ? trim($sc['size']) : $request->size;
                    $cSizeType = !empty($sc['size_type']) ? trim($sc['size_type']) : ($request->size_type ?: 'ft');
                    $cNotes = $sc['notes'] ?? null;

                    $cExistingId = $sc['id'] ?? null;
                    if ($cExistingId && isset($existingCoils[$cExistingId])) {
                        $cModel = $existingCoils[$cExistingId];
                        $cSold = max(0, (float)$cModel->net_weight - (float)$cModel->remaining_weight);
                        $cNewRemaining = max(0, $cWeight - $cSold);

                        $cModel->update([
                            'lot_id'           => $request->lot_id,
                            'vendor_id'        => $request->vendor_id,
                            'warehouse_id'     => $request->warehouse_id,
                            'product_name'     => $cProduct,
                            'coil_number'      => $cNumber ?: $cModel->coil_number,
                            'thickness'        => $cThickness,
                            'width'            => $cSize,
                            'length'           => $cSizeType,
                            'piece_count'      => 1,
                            'gross_weight'     => $cWeight,
                            'net_weight'       => $cWeight,
                            'remaining_weight' => $cNewRemaining,
                            'rate_per_ton'     => $rate,
                            'total_price'      => round($cWeight * $rate, 2),
                            'status'           => ($cNewRemaining <= 0) ? 'exhausted' : 'in_stock',
                            'notes'            => $cNotes,
                            'updated_by'       => Auth::id(),
                        ]);
                        $retainedCoilIds[] = $cModel->id;
                    } else {
                        // Reuse an unclaimed existing coil if unsold
                        $unclaimed = $existingCoils->whereNotIn('id', $retainedCoilIds)->first();
                        if ($unclaimed && max(0, (float)$unclaimed->net_weight - (float)$unclaimed->remaining_weight) <= 0.0001) {
                            $unclaimed->update([
                                'lot_id'           => $request->lot_id,
                                'vendor_id'        => $request->vendor_id,
                                'warehouse_id'     => $request->warehouse_id,
                                'product_name'     => $cProduct,
                                'coil_number'      => $cNumber ?: $unclaimed->coil_number,
                                'thickness'        => $cThickness,
                                'width'            => $cSize,
                                'length'           => $cSizeType,
                                'piece_count'      => 1,
                                'gross_weight'     => $cWeight,
                                'net_weight'       => $cWeight,
                                'remaining_weight' => $cWeight,
                                'rate_per_ton'     => $rate,
                                'total_price'      => round($cWeight * $rate, 2),
                                'status'           => 'in_stock',
                                'notes'            => $cNotes,
                                'updated_by'       => Auth::id(),
                            ]);
                            $retainedCoilIds[] = $unclaimed->id;
                        } else {
                            $newCoilNumber = $cNumber ?: Coil::generateCoilNumber();
                            if (Coil::where('coil_number', $newCoilNumber)->exists()) {
                                $newCoilNumber = Coil::generateCoilNumber();
                            }
                            $createdCoil = Coil::create([
                                'coil_number'      => $newCoilNumber,
                                'product_name'     => $cProduct,
                                'purchase_id'      => $purchase->id,
                                'lot_id'           => $request->lot_id,
                                'vendor_id'        => $request->vendor_id,
                                'warehouse_id'     => $request->warehouse_id,
                                'thickness'        => $cThickness,
                                'width'            => $cSize,
                                'length'           => $cSizeType,
                                'piece_count'      => 1,
                                'gross_weight'     => $cWeight,
                                'tare_weight'      => 0,
                                'net_weight'       => $cWeight,
                                'remaining_weight' => $cWeight,
                                'rate_per_ton'     => $rate,
                                'total_price'      => round($cWeight * $rate, 2),
                                'status'           => 'in_stock',
                                'notes'            => $cNotes,
                                'created_by'       => Auth::id(),
                            ]);
                            $retainedCoilIds[] = $createdCoil->id;
                        }
                    }
                }

                // Delete any un-retained coils (only if 0 sold weight)
                foreach ($existingCoils as $oldCoil) {
                    if (!in_array($oldCoil->id, $retainedCoilIds)) {
                        $cSold = max(0, (float)$oldCoil->net_weight - (float)$oldCoil->remaining_weight);
                        if ($cSold <= 0.0001) {
                            $oldCoil->delete();
                        }
                    }
                }
            } else {
                $coil = $existingCoils->first();
                if ($coil) {
                    $prevNetWeight = (float) $coil->net_weight;
                    $soldWeight = max(0, $prevNetWeight - (float) $coil->remaining_weight);
                    $newRemainingWeight = max(0, $totalWeight - $soldWeight);

                    $coil->update([
                        'lot_id'           => $request->lot_id,
                        'vendor_id'        => $request->vendor_id,
                        'warehouse_id'     => $request->warehouse_id,
                        'product_name'     => $request->product_name,
                        'coil_number'      => !empty($request->coil_number) ? trim($request->coil_number) : $coil->coil_number,
                        'thickness'        => $request->thickness,
                        'width'            => $request->size,
                        'length'           => $request->size_type ?: 'ft',
                        'piece_count'      => $coilQty,
                        'gross_weight'     => $totalWeight,
                        'net_weight'       => $totalWeight,
                        'remaining_weight' => $newRemainingWeight,
                        'rate_per_ton'     => $rate,
                        'total_price'      => $totalPrice,
                        'status'           => ($newRemainingWeight <= 0) ? 'exhausted' : 'in_stock',
                        'notes'            => $request->coil_notes ?? $coil->notes,
                        'updated_by'       => Auth::id(),
                    ]);
                }
            }

            // Update affected lots totals
            foreach (array_unique(array_filter([$oldLotId, $request->lot_id])) as $lId) {
                $lot = Lot::find($lId);
                if ($lot) {
                    $lot->total_quantity = $lot->purchases()->sum('total_weight');
                    $lot->total_amount   = $lot->purchases()->sum('total_price');
                    $lot->save();
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            $msg = $isBreakdown
                ? "Purchase order #PO-{$purchase->id} updated with {$coilQty} coil items (" . number_format($totalWeight, 2) . " kg) successfully."
                : "Purchase order #PO-{$purchase->id} and physical coil specifications updated successfully.";

            return redirect()->route('purchase.show', $purchase->id)->with('success', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Failed to update purchase #PO-{$purchase->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update purchase: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // Safety check: Prevent deletion if any coil under this purchase has sales records or sold weight
        $hasSalesItems = \App\Models\SalesItem::whereHas('coil', function ($q) use ($purchase) {
            $q->where('purchase_id', $purchase->id);
        })->exists();

        $hasSoldWeight = \App\Models\Coil::where('purchase_id', $purchase->id)
            ->whereColumn('remaining_weight', '<', 'net_weight')
            ->exists();

        if ($hasSalesItems || $hasSoldWeight) {
            return redirect()->back()->with('error', 'Cannot delete purchase: Steel coils from this procurement intake have already been sold.');
        }

        $purchase->delete();
        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }

    public function getLatestPrice($id)
    {
        return response()->json(['price' => 0]);
    }

    public function reportIndex(?Request $request = null)
    {
        $request = $request ?? request();
        $query = Lot::with(['vendor', 'purchases' => function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            })->with(['warehouse', 'coils'])->latest();
        }])->whereHas('purchases', function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        });

        $hasFilters = $request->filled('vendor_id') || $request->filled('lot_id') || $request->filled('from') || $request->filled('to');

        if (!$hasFilters) {
            $query->where(function ($q) {
                $start = Carbon::now()->startOfMonth();
                $end   = Carbon::now()->endOfMonth();
                $q->whereBetween('lot_date', [$start, $end])
                  ->orWhereBetween('created_at', [$start, $end]);
            });
        } else {
            $query = $this->applyPurchaseReportFilters($query, $request);
        }

        $purchases = $query->latest()->get();
        $vendors = Vendor::latest()->get();
        $allLots = Lot::whereDoesntHave('purchases', function ($q) {
            $q->whereHas('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        })->latest()->get();
        $lots = $allLots;

        return view('frontend.pages.report.purchase.index', compact('purchases', 'vendors', 'lots', 'allLots', 'request'));
    }

    public function report(Request $request)
    {
        $query = Lot::with(['vendor', 'purchases' => function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            })->with(['warehouse', 'coils'])->latest();
        }])->whereHas('purchases', function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        });

        $query = $this->applyPurchaseReportFilters($query, $request);

        $purchases = $query->latest()->get();
        $products = collect();
        $vendors = Vendor::latest()->get();
        $allLots = Lot::whereDoesntHave('purchases', function ($q) {
            $q->whereHas('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        })->latest()->get();
        $lots = $allLots;

        return view('frontend.pages.report.purchase.index', compact('purchases', 'products', 'vendors', 'lots', 'allLots', 'request'));
    }

    public function reportPdf(Request $request)
    {
        $query = Lot::with(['vendor', 'purchases' => function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            })->with(['warehouse', 'coils'])->latest();
        }])->whereHas('purchases', function ($q) {
            $q->whereDoesntHave('warehouse', function ($wq) {
                $wq->where('type', 'shop');
            });
        });

        $query = $this->applyPurchaseReportFilters($query, $request);

        $purchases = $query->latest()->get();
        $vendors = Vendor::latest()->get();

        $filters = [
            'from' => $request->filled('from') ? $request->from : Carbon::now()->startOfMonth()->format('Y-m-d'),
            'to' => $request->filled('to') ? $request->to : Carbon::now()->endOfMonth()->format('Y-m-d'),
            'vendor' => $request->filled('vendor_id') ? Vendor::find($request->vendor_id)?->name : 'All Vendors',
            'lot' => $request->filled('lot_id') ? Lot::find($request->lot_id)?->lot_number : 'All Lots',
        ];

        $filename = 'purchase-report-' . now()->format('Y-m-d') . '.pdf';
        return PdfService::viewToResponse('frontend.pages.report.purchase.pdf', compact('purchases', 'filters'), $filename);
    }

    private function applyPurchaseReportFilters($query, Request $request)
    {
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('lot_id')) {
            $query->where('id', $request->lot_id);
        }

        if ($request->filled('from')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('lot_date', '>=', $request->from)
                  ->orWhereDate('created_at', '>=', $request->from);
            });
        }

        if ($request->filled('to')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('lot_date', '<=', $request->to)
                  ->orWhereDate('created_at', '<=', $request->to);
            });
        }

        return $query;
    }

    /**
     * Display listing of unpaid purchase orders with vendor dues
     */
    public function duePayments()
    {
        Vendor::reconcileUnallocatedPayments();

        $purchases = Purchase::with(['vendor', 'lot', 'warehouse'])
            ->where('due', '>', 0)
            ->latest()
            ->get();

        $vendorsWithDue = Vendor::withSum(['purchases' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due')
            ->withCount(['purchases' => function($q) {
                $q->whereNull('deleted_at')
                  ->where('due', '>', 0);
            }])
            ->get()
            ->map(function($v) {
                $v->opening_due = max(0, (float)($v->opening_balance ?? 0));
                $v->purchase_due = (float)($v->purchases_sum_due ?? 0);
                $v->total_due = $v->opening_due + $v->purchase_due;
                return $v;
            })
            ->filter(function($v) {
                return $v->total_due > 0;
            })
            ->sortByDesc('total_due')
            ->values();

        $totalOpeningDues = (float) Vendor::where('opening_balance', '>', 0)->sum('opening_balance');
        $totalPurchaseDues = (float) Purchase::where('due', '>', 0)->sum('due');
        $grandTotalDues = $totalOpeningDues + $totalPurchaseDues;

        $bankAccounts = BankDetail::where('is_active', true)->orderBy('bank_name')->get();

        return view('frontend.pages.purchase.due-payments', compact(
            'purchases',
            'vendorsWithDue',
            'totalOpeningDues',
            'totalPurchaseDues',
            'grandTotalDues',
            'bankAccounts'
        ));
    }

    /**
     * Export Vendor Due Payments Report as PDF
     */
    public function duePaymentsPdf()
    {
        Vendor::reconcileUnallocatedPayments();

        $purchases = Purchase::with(['vendor', 'lot', 'warehouse'])
            ->where('due', '>', 0)
            ->latest()
            ->get();

        $vendorsWithDue = Vendor::withSum(['purchases' => function($q) {
                $q->whereNull('deleted_at');
            }], 'due')
            ->withCount(['purchases' => function($q) {
                $q->whereNull('deleted_at')
                  ->where('due', '>', 0);
            }])
            ->get()
            ->map(function($v) {
                $v->opening_due = max(0, (float)($v->opening_balance ?? 0));
                $v->purchase_due = (float)($v->purchases_sum_due ?? 0);
                $v->total_due = $v->opening_due + $v->purchase_due;
                return $v;
            })
            ->filter(function($v) {
                return $v->total_due > 0;
            })
            ->sortByDesc('total_due')
            ->values();

        $totalOpeningDues = (float) Vendor::where('opening_balance', '>', 0)->sum('opening_balance');
        $totalPurchaseDues = (float) Purchase::where('due', '>', 0)->sum('due');
        $grandTotalDues = $totalOpeningDues + $totalPurchaseDues;

        $filename = 'Vendor_Due_Payments_Report_' . now()->format('Y_m_d_His') . '.pdf';

        return PdfService::viewToResponse(
            'pdf.vendor_due_payments',
            compact(
                'purchases',
                'vendorsWithDue',
                'totalOpeningDues',
                'totalPurchaseDues',
                'grandTotalDues'
            ),
            $filename
        );
    }
}
