<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Coil;
use App\Models\Lot;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    /**
     * Create multiple steel purchase items & physical coils in one batch transaction.
     * Operates purely on direct steel specifications (Lot, Coil #, Thickness, Size, Weight, Rate)
     * without needing or creating redundant product records.
     */
    public function createPurchasesBatch(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            $warehouseId = $data['warehouse_id'] ?? null;
            $totalPayment = (float) ($data['payment'] ?? 0);

            // 1. Resolve or create Lot on the fly
            $lotType = $data['lot_type'] ?? 'existing';
            $firstVendorId = $data['vendor_id'] ?? (!empty($items[0]['vendor_id']) ? $items[0]['vendor_id'] : null);

            if ($lotType === 'new' || empty($data['lot_id'])) {
                $lotNumber = !empty($data['new_lot_number']) ? trim($data['new_lot_number']) : Lot::generateLotNumber();
                $lotDate = $data['purchase_date'] ?? date('Y-m-d');
                
                $lot = Lot::create([
                    'lot_number'     => $lotNumber,
                    'vendor_id'      => $firstVendorId,
                    'lot_date'       => $lotDate,
                    'total_quantity' => 0,
                    'total_amount'   => 0,
                    'notes'          => $data['lot_notes'] ?? null,
                    'status'         => 'active',
                    'created_by'     => Auth::id(),
                ]);
                $lotId = $lot->id;
            } else {
                $lotId = $data['lot_id'];
                $lot = Lot::find($lotId);
            }

            // Calculate total batch bill & extra charges
            $deliveryCharge  = (float) ($data['delivery_charge'] ?? 0);
            $labourCost      = (float) ($data['labour_cost'] ?? 0);
            $weightScaleCost = (float) ($data['weight_scale_cost'] ?? 0);
            $otherCharges    = (float) ($data['other_charges'] ?? 0);
            $discount        = (float) ($data['discount'] ?? 0);
            $netExtraCharges = ($deliveryCharge + $labourCost + $weightScaleCost + $otherCharges) - $discount;

            $batchSubTotal = 0;
            foreach ($items as $item) {
                $coilQty = max(1, (int) ($item['quantity'] ?? 1));
                $perCoilWeight = (float) ($item['unit_weight'] ?? (!empty($item['net_weight']) ? $item['net_weight'] : 0));
                $totalWeight = !empty($item['total_weight']) && (float) $item['total_weight'] > 0 
                    ? (float) $item['total_weight'] 
                    : ($coilQty * $perCoilWeight);

                if ($perCoilWeight <= 0 && $totalWeight > 0) {
                    $perCoilWeight = $totalWeight / $coilQty;
                }

                $rate = !empty($item['unit_price']) ? (float) $item['unit_price'] : (!empty($item['rate_per_ton']) ? (float) $item['rate_per_ton'] : 0);

                if (!empty($item['sub_price']) && (float) $item['sub_price'] > 0) {
                    $itemSub = (float) $item['sub_price'];
                } else {
                    $itemSub = $totalWeight * $rate;
                }
                $batchSubTotal += $itemSub;
            }

            $batchGrandTotal = max(0, round($batchSubTotal + $netExtraCharges, 2));

            // Pre-calculate each item's sub_price, allocated extra charges, and item total
            $itemDetails = [];
            $allocatedDelivery = 0;
            $allocatedLabour = 0;
            $allocatedScale = 0;
            $allocatedOther = 0;
            $allocatedDiscount = 0;
            $itemCount = count($items);

            foreach ($items as $index => $item) {
                $itemVendorId = $item['vendor_id'] ?? ($data['vendor_id'] ?? $firstVendorId);
                $coilQty = max(1, (int) ($item['quantity'] ?? 1));
                $perCoilWeight = (float) ($item['unit_weight'] ?? (!empty($item['net_weight']) ? $item['net_weight'] : 0));
                $totalWeight = !empty($item['total_weight']) && (float) $item['total_weight'] > 0 
                    ? (float) $item['total_weight'] 
                    : ($coilQty * $perCoilWeight);

                if ($perCoilWeight <= 0 && $totalWeight > 0) {
                    $perCoilWeight = $totalWeight / $coilQty;
                }

                $rate = !empty($item['unit_price']) ? (float) $item['unit_price'] : (!empty($item['rate_per_ton']) ? (float) $item['rate_per_ton'] : 0);

                if (!empty($item['sub_price']) && (float) $item['sub_price'] > 0) {
                    $itemSub = (float) $item['sub_price'];
                } else {
                    $itemSub = $totalWeight * $rate;
                }

                // Proportional allocation of charges across items in the batch
                $ratio = ($batchSubTotal > 0) ? ($itemSub / $batchSubTotal) : (1 / $itemCount);

                if ($index === $itemCount - 1) {
                    $itemDelivery = max(0, round($deliveryCharge - $allocatedDelivery, 2));
                    $itemLabour   = max(0, round($labourCost - $allocatedLabour, 2));
                    $itemScale    = max(0, round($weightScaleCost - $allocatedScale, 2));
                    $itemOther    = max(0, round($otherCharges - $allocatedOther, 2));
                    $itemDiscount = max(0, round($discount - $allocatedDiscount, 2));
                } else {
                    $itemDelivery = round($deliveryCharge * $ratio, 2);
                    $itemLabour   = round($labourCost * $ratio, 2);
                    $itemScale    = round($weightScaleCost * $ratio, 2);
                    $itemOther    = round($otherCharges * $ratio, 2);
                    $itemDiscount = round($discount * $ratio, 2);

                    $allocatedDelivery += $itemDelivery;
                    $allocatedLabour   += $itemLabour;
                    $allocatedScale    += $itemScale;
                    $allocatedOther    += $itemOther;
                    $allocatedDiscount += $itemDiscount;
                }

                $itemNetExtra = ($itemDelivery + $itemLabour + $itemScale + $itemOther) - $itemDiscount;
                $itemTotal    = max(0, round($itemSub + $itemNetExtra, 2));

                $itemDetails[$index] = [
                    'item'            => $item,
                    'vendor_id'       => $itemVendorId,
                    'coil_qty'        => $coilQty,
                    'per_coil_weight' => $perCoilWeight,
                    'total_weight'    => $totalWeight,
                    'rate'            => $rate,
                    'item_sub'        => $itemSub,
                    'item_delivery'   => $itemDelivery,
                    'item_labour'     => $itemLabour,
                    'item_scale'      => $itemScale,
                    'item_other'      => $itemOther,
                    'item_discount'   => $itemDiscount,
                    'item_net_extra'  => $itemNetExtra,
                    'item_total'      => $itemTotal,
                ];
            }

            // Determine if individual vendor payments were provided
            $hasVendorPayments = !empty($data['vendor_payments']) && is_array($data['vendor_payments']);
            $vendorItemPayments = [];

            if ($hasVendorPayments) {
                // Group item indices by vendor
                $vendorItemsMap = [];
                $vendorTotalBills = [];
                foreach ($itemDetails as $index => $detail) {
                    $vId = $detail['vendor_id'];
                    $vendorItemsMap[$vId][] = $index;
                    $vendorTotalBills[$vId] = ($vendorTotalBills[$vId] ?? 0) + $detail['item_total'];
                }

                // Allocate payment individually per vendor
                foreach ($vendorItemsMap as $vId => $indices) {
                    $vSpecifiedPaid = (float) ($data['vendor_payments'][$vId]['amount'] ?? 0);
                    $vBill = $vendorTotalBills[$vId] ?? 0;
                    $vAllocated = 0;
                    $vCount = count($indices);

                    foreach ($indices as $iPos => $idx) {
                        $iTotal = $itemDetails[$idx]['item_total'];
                        if ($vCount === 1) {
                            $iPay = min($iTotal, $vSpecifiedPaid);
                        } elseif ($iPos === $vCount - 1) {
                            $iPay = max(0, round($vSpecifiedPaid - $vAllocated, 2));
                        } else {
                            $pRatio = ($vBill > 0) ? ($iTotal / $vBill) : (1 / $vCount);
                            $iPay = min($iTotal, round($pRatio * $vSpecifiedPaid, 2));
                        }
                        $vAllocated += $iPay;
                        $vendorItemPayments[$idx] = $iPay;
                    }
                }
            } else {
                // Global proportional payment allocation
                $allocatedPayment = 0;
                foreach ($itemDetails as $index => $detail) {
                    $iTotal = $detail['item_total'];
                    if ($itemCount === 1) {
                        $iPay = $totalPayment;
                    } elseif ($index === $itemCount - 1) {
                        $iPay = max(0, round($totalPayment - $allocatedPayment, 2));
                    } else {
                        $pRatio = $batchGrandTotal > 0 ? ($iTotal / $batchGrandTotal) : (1 / $itemCount);
                        $iPay = min($iTotal, round($pRatio * $totalPayment, 2));
                    }
                    $allocatedPayment += $iPay;
                    $vendorItemPayments[$index] = $iPay;
                }
            }

            $createdPurchases = [];
            foreach ($itemDetails as $index => $detail) {
                $item = $detail['item'];
                $itemVendorId = $detail['vendor_id'];
                $itemTotal = $detail['item_total'];
                $itemPayment = $vendorItemPayments[$index] ?? 0;
                $itemDue = max(0, round($itemTotal - $itemPayment, 2));

                $thickness = !empty($item['thickness']) ? trim($item['thickness']) : null;
                $size = !empty($item['size']) ? trim($item['size']) : (!empty($item['width']) ? trim($item['width']) : null);
                $sizeType = !empty($item['size_type']) ? trim($item['size_type']) : 'ft';

                $paymentMethod = $data['vendor_payments'][$itemVendorId]['payment_method'] ?? ($data['payment_method'] ?? 'cash');
                $isBank = ($paymentMethod !== 'cash');
                $bankDetailId = ($isBank && !empty($data['vendor_payments'][$itemVendorId]['bank_detail_id'])) 
                    ? $data['vendor_payments'][$itemVendorId]['bank_detail_id'] 
                    : (($isBank && !empty($data['bank_detail_id'])) ? $data['bank_detail_id'] : null);
                $transactionRef = ($isBank && !empty($data['vendor_payments'][$itemVendorId]['transaction_ref']))
                    ? $data['vendor_payments'][$itemVendorId]['transaction_ref']
                    : (($isBank && !empty($data['transaction_ref'])) ? $data['transaction_ref'] : null);

                // 1. Record Purchase Line
                $purchase = Purchase::create([
                    'lot_id'            => $lotId,
                    'vendor_id'         => $itemVendorId,
                    'warehouse_id'      => $warehouseId,
                    'thickness'         => $thickness,
                    'size'              => $size,
                    'size_type'         => $sizeType,
                    'quantity'          => $detail['coil_qty'],
                    'unit_weight'       => $detail['per_coil_weight'],
                    'total_weight'      => $detail['total_weight'],
                    'unit_price'        => $detail['rate'],
                    'sub_price'         => $detail['item_sub'],
                    'delivery_charge'   => $detail['item_delivery'],
                    'labour_cost'       => $detail['item_labour'],
                    'weight_scale_cost' => $detail['item_scale'],
                    'other_charges'     => $detail['item_other'],
                    'discount'          => $detail['item_discount'],
                    'total_price'       => $itemTotal,
                    'payment'           => $itemPayment,
                    'due'               => $itemDue,
                    'payment_method'    => $paymentMethod,
                    'bank_detail_id'    => $bankDetailId,
                    'transaction_ref'   => $transactionRef,
                    'created_by'        => Auth::id(),
                ]);

                // 2. Register Single Batch Coil in Yard Stock
                $coilNumber = Coil::generateCoilNumber();

                Coil::create([
                    'coil_number'      => $coilNumber,
                    'purchase_id'      => $purchase->id,
                    'lot_id'           => $lotId,
                    'vendor_id'        => $itemVendorId,
                    'warehouse_id'     => $warehouseId,
                    'thickness'        => $thickness,
                    'width'            => $size,
                    'length'           => $sizeType,
                    'piece_count'      => $detail['coil_qty'],
                    'gross_weight'     => $detail['total_weight'],
                    'tare_weight'      => 0,
                    'net_weight'       => $detail['total_weight'],
                    'remaining_weight' => $detail['total_weight'],
                    'rate_per_ton'     => $detail['rate'],
                    'total_price'      => $itemTotal,
                    'status'           => 'in_stock',
                    'notes'            => $item['notes'] ?? null,
                    'created_by'       => Auth::id(),
                ]);

                $createdPurchases[] = $purchase;
            }

            // 3. Record Payment entries grouped by Vendor for accurate vendor ledger & due management
            $actualTotalPaid = 0;
            if (!empty($createdPurchases)) {
                $purchasesByVendor = collect($createdPurchases)->groupBy('vendor_id');
                foreach ($purchasesByVendor as $vId => $vPurchases) {
                    $vTotalPaid = (float) $vPurchases->sum('payment');
                    $actualTotalPaid += $vTotalPaid;
                    if ($vTotalPaid > 0) {
                        $primaryPurchase = $vPurchases->first();
                        $vPayMethod = $data['vendor_payments'][$vId]['payment_method'] ?? ($data['payment_method'] ?? 'cash');
                        $vBankId = ($vPayMethod !== 'cash') ? ($data['vendor_payments'][$vId]['bank_detail_id'] ?? ($data['bank_detail_id'] ?? null)) : null;
                        $vTrxRef = ($vPayMethod !== 'cash') ? ($data['vendor_payments'][$vId]['transaction_ref'] ?? ($data['transaction_ref'] ?? null)) : null;

                        Payment::create([
                            'vendor_id'       => $vId,
                            'purchase_id'     => $primaryPurchase->id,
                            'amount'          => $vTotalPaid,
                            'payment_for'     => 3, // 3: Purchases / Vendor payment
                            'payment_method'  => $vPayMethod,
                            'bank_detail_id'  => $vBankId,
                            'transaction_ref' => $vTrxRef,
                            'payment_date'    => $data['purchase_date'] ?? date('Y-m-d'),
                            'remarks'         => 'Initial disbursement for ' . (!empty($lotId) ? 'Consignment Lot #' . ($lot->lot_number ?? $lotId) : 'Purchase Order #PO-' . $primaryPurchase->id),
                            'status'          => '1',
                            'created_by'      => Auth::id(),
                        ]);
                    }
                }
            }

            // Recalculate Lot totals if linked
            if (!empty($lotId)) {
                $lot = Lot::find($lotId);
                if ($lot) {
                    $lot->total_quantity = $lot->purchases()->sum('total_weight');
                    $lot->total_amount   = $lot->purchases()->sum('total_price');
                    $lot->save();
                }
            }

            // Auto-post double-entry accounting journal voucher for batch purchase
            try {
                $apAcc = \App\Models\ChartOfAccount::where('account_code', '2110')->first();
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $invAcc = \App\Models\ChartOfAccount::where('account_code', '1140')->first();

                if ($apAcc && $invAcc) {
                    $journalItems = [];
                    $journalItems[] = [
                        'account_id'  => $invAcc->id,
                        'debit'       => $batchGrandTotal,
                        'credit'      => 0.00,
                        'description' => 'Steel yard stock inventory intake for Purchase Batch (' . count($createdPurchases) . ' items)'
                    ];

                    // Track disbursements per account (Cash vs individual Bank accounts)
                    $disbursements = [];
                    if (!empty($createdPurchases)) {
                        $purchasesByVendor = collect($createdPurchases)->groupBy('vendor_id');
                        foreach ($purchasesByVendor as $vId => $vPurchases) {
                            $vPaid = (float) $vPurchases->sum('payment');
                            if ($vPaid > 0) {
                                $vMethod = $data['vendor_payments'][$vId]['payment_method'] ?? ($data['payment_method'] ?? 'cash');
                                $vBankId = ($vMethod !== 'cash') ? ($data['vendor_payments'][$vId]['bank_detail_id'] ?? ($data['bank_detail_id'] ?? null)) : null;
                                
                                $accKey = 'cash';
                                $accModel = $cashAcc;
                                if ($vMethod !== 'cash' && !empty($vBankId)) {
                                    $bank = \App\Models\BankDetail::find($vBankId);
                                    $accModel = $bank?->resolveChartOfAccount() 
                                        ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                                        ?? $cashAcc;
                                    $accKey = 'bank_' . $accModel->id;
                                } elseif ($vMethod !== 'cash') {
                                    $accModel = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                                    $accKey = 'bank_' . $accModel->id;
                                }

                                if (!isset($disbursements[$accKey])) {
                                    $disbursements[$accKey] = [
                                        'account_id'  => $accModel->id,
                                        'amount'      => 0,
                                        'method'      => $vMethod,
                                    ];
                                }
                                $disbursements[$accKey]['amount'] += $vPaid;
                            }
                        }
                    }

                    foreach ($disbursements as $disb) {
                        if ($disb['amount'] > 0) {
                            $methodLabel = match($disb['method']) {
                                'bank'           => 'Bank Transfer',
                                'cheque'         => 'Cheque',
                                'mobile_banking' => 'Mobile Banking',
                                default          => 'Cash'
                            };
                            $journalItems[] = [
                                'account_id'  => $disb['account_id'],
                                'debit'       => 0.00,
                                'credit'      => round($disb['amount'], 2),
                                'description' => "{$methodLabel} disbursement for Purchase Batch"
                            ];
                        }
                    }

                    $totalBatchDue = max(0, round($batchGrandTotal - $actualTotalPaid, 2));
                    if ($totalBatchDue > 0) {
                        $journalItems[] = [
                            'account_id'  => $apAcc->id,
                            'debit'       => 0.00,
                            'credit'      => $totalBatchDue,
                            'description' => 'Payable balance to Accounts Payable (Vendor Batch)'
                        ];
                    }

                    postJournalEntry([
                        'entry_date'     => date('Y-m-d'),
                        'reference_type' => 'purchase',
                        'reference_id'   => $createdPurchases[0]->id ?? null,
                        'description'    => 'Purchase Inward Batch — ' . count($createdPurchases) . ' steel items (' . count($purchasesByVendor) . ' vendors)',
                        'items'          => $journalItems
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Auto-post journal voucher failed for purchase batch: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
            return $createdPurchases;
        });
    }

    /**
     * Single createPurchase method for compatibility
     */
    public function createPurchase(array $data): Purchase
    {
        $data['items'] = [$data];
        $purchases = $this->createPurchasesBatch($data);
        return $purchases[0];
    }
}
