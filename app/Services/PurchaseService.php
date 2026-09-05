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
            if ($lotType === 'new' || empty($data['lot_id'])) {
                $lotNumber = !empty($data['new_lot_number']) ? trim($data['new_lot_number']) : Lot::generateLotNumber();
                $vendorId = $data['vendor_id'];
                $lotDate = $data['purchase_date'] ?? date('Y-m-d');
                
                $lot = Lot::create([
                    'lot_number'     => $lotNumber,
                    'vendor_id'      => $vendorId,
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
                $vendorId = $lot?->vendor_id ?? $data['vendor_id'];
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

            $createdPurchases = [];
            $allocatedPayment = 0;
            $allocatedDelivery = 0;
            $allocatedLabour = 0;
            $allocatedScale = 0;
            $allocatedOther = 0;
            $allocatedDiscount = 0;
            $itemCount = count($items);

            foreach ($items as $index => $item) {
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

                // Proportional payment allocation across batch items
                if ($itemCount === 1) {
                    $itemPayment = $totalPayment;
                } elseif ($index === $itemCount - 1) {
                    $itemPayment = max(0, round($totalPayment - $allocatedPayment, 2));
                } else {
                    $paymentRatio = $batchGrandTotal > 0 ? ($itemTotal / $batchGrandTotal) : (1 / $itemCount);
                    $itemPayment = min($itemTotal, round($paymentRatio * $totalPayment, 2));
                }

                $allocatedPayment += $itemPayment;
                $itemDue = max(0, round($itemTotal - $itemPayment, 2));

                $thickness = !empty($item['thickness']) ? trim($item['thickness']) : null;
                $size = !empty($item['size']) ? trim($item['size']) : (!empty($item['width']) ? trim($item['width']) : null);
                $sizeType = !empty($item['size_type']) ? trim($item['size_type']) : 'ft';

                // 1. Record Purchase Line
                $purchase = Purchase::create([
                    'lot_id'            => $lotId,
                    'vendor_id'         => $vendorId,
                    'warehouse_id'      => $warehouseId,
                    'thickness'         => $thickness,
                    'size'              => $size,
                    'size_type'         => $sizeType,
                    'quantity'          => $coilQty,
                    'unit_weight'       => $perCoilWeight,
                    'total_weight'      => $totalWeight,
                    'unit_price'        => $rate,
                    'sub_price'         => $itemSub,
                    'delivery_charge'   => $itemDelivery,
                    'labour_cost'       => $itemLabour,
                    'weight_scale_cost' => $itemScale,
                    'other_charges'     => $itemOther,
                    'discount'          => $itemDiscount,
                    'total_price'       => $itemTotal,
                    'payment'           => $itemPayment,
                    'due'               => $itemDue,
                    'payment_method'    => $data['payment_method'] ?? 'cash',
                    'bank_detail_id'    => !empty($data['bank_detail_id']) ? $data['bank_detail_id'] : null,
                    'transaction_ref'   => $data['transaction_ref'] ?? null,
                    'created_by'        => Auth::id(),
                ]);

                // 2. Record Payment entry if initial payment was allocated
                if ($itemPayment > 0) {
                    Payment::create([
                        'vendor_id'       => $vendorId,
                        'purchase_id'     => $purchase->id,
                        'amount'          => $itemPayment,
                        'payment_for'     => 3, // 3: Purchases / Vendor payment
                        'payment_method'  => $data['payment_method'] ?? 'cash',
                        'bank_detail_id'  => !empty($data['bank_detail_id']) ? $data['bank_detail_id'] : null,
                        'transaction_ref' => $data['transaction_ref'] ?? null,
                        'payment_date'    => $data['purchase_date'] ?? date('Y-m-d'),
                        'remarks'         => 'Initial disbursement for Purchase Order #PO-' . $purchase->id,
                        'status'          => '1',
                        'created_by'      => Auth::id(),
                    ]);
                }

                // 3. Register Single Batch Coil in Yard Stock
                $coilNumber = Coil::generateCoilNumber();

                Coil::create([
                    'coil_number'      => $coilNumber,
                    'purchase_id'      => $purchase->id,
                    'lot_id'           => $lotId,
                    'vendor_id'        => $vendorId,
                    'warehouse_id'     => $warehouseId,
                    'thickness'        => $thickness,
                    'width'            => $size,
                    'length'           => $sizeType,
                    'piece_count'      => $coilQty,
                    'gross_weight'     => $totalWeight,
                    'tare_weight'      => 0,
                    'net_weight'       => $totalWeight,
                    'remaining_weight' => $totalWeight,
                    'rate_per_ton'     => $rate,
                    'total_price'      => $itemTotal,
                    'status'           => 'in_stock',
                    'notes'            => $item['notes'] ?? null,
                    'created_by'       => Auth::id(),
                ]);

                $createdPurchases[] = $purchase;
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

                // Determine exact Cash or Bank Chart of Account for disbursement
                $paymentMethod = $data['payment_method'] ?? 'cash';
                $bankDetailId = !empty($data['bank_detail_id']) ? $data['bank_detail_id'] : null;
                $transactionRef = $data['transaction_ref'] ?? null;

                $disbursementAcc = $cashAcc;
                if ($paymentMethod !== 'cash') {
                    if (!empty($bankDetailId)) {
                        $bank = \App\Models\BankDetail::find($bankDetailId);
                        $disbursementAcc = $bank?->resolveChartOfAccount() 
                            ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                            ?? $cashAcc;
                    } else {
                        $disbursementAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                    }
                }

                if ($apAcc && $invAcc) {
                    $items = [];
                    $items[] = [
                        'account_id' => $invAcc->id,
                        'debit' => $batchGrandTotal,
                        'credit' => 0.00,
                        'description' => 'Steel yard stock inventory intake for Purchase Batch (' . count($createdPurchases) . ' items)'
                    ];

                    $methodLabel = match($paymentMethod) {
                        'bank' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash'
                    };
                    $refText = $transactionRef ? " [Ref/Cheque: {$transactionRef}]" : "";

                    if ($totalPayment > 0 && $disbursementAcc) {
                        $items[] = [
                            'account_id' => $disbursementAcc->id,
                            'debit' => 0.00,
                            'credit' => $totalPayment,
                            'description' => "{$methodLabel} disbursement for Purchase Batch{$refText}"
                        ];
                    }

                    $totalBatchDue = max(0, round($batchGrandTotal - $totalPayment, 2));
                    if ($totalBatchDue > 0) {
                        $items[] = [
                            'account_id' => $apAcc->id,
                            'debit' => 0.00,
                            'credit' => $totalBatchDue,
                            'description' => 'Payable balance to Vendor #' . $vendorId
                        ];
                    }

                    postJournalEntry([
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'purchase',
                        'reference_id' => $createdPurchases[0]->id ?? null,
                        'description' => 'Purchase Inward Batch — ' . count($createdPurchases) . ' steel items from Vendor #' . $vendorId,
                        'items' => $items
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Auto-post journal voucher failed for purchase batch: ' . $e->getMessage(), [
                    'exception' => $e,
                    'vendor_id' => $vendorId,
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
