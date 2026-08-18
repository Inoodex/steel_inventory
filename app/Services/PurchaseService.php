<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Coil;
use App\Models\Lot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

            // Calculate total batch bill
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

            $createdPurchases = [];
            $allocatedPayment = 0;
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

                // Proportional payment allocation across batch items
                if ($itemCount === 1) {
                    $itemPayment = $totalPayment;
                } elseif ($index === $itemCount - 1) {
                    $itemPayment = max(0, round($totalPayment - $allocatedPayment, 2));
                } else {
                    $ratio = $batchSubTotal > 0 ? ($itemSub / $batchSubTotal) : (1 / $itemCount);
                    $itemPayment = min($itemSub, round($ratio * $totalPayment, 2));
                }

                $allocatedPayment += $itemPayment;
                $itemDue = max(0, round($itemSub - $itemPayment, 2));

                $thickness = !empty($item['thickness']) ? trim($item['thickness']) : null;
                $size = !empty($item['size']) ? trim($item['size']) : (!empty($item['width']) ? trim($item['width']) : null);
                $sizeType = !empty($item['size_type']) ? trim($item['size_type']) : 'ft';

                // 1. Record Purchase Line
                $purchase = Purchase::create([
                    'lot_id'          => $lotId,
                    'vendor_id'       => $vendorId,
                    'warehouse_id'    => $warehouseId,
                    'thickness'       => $thickness,
                    'size'            => $size,
                    'size_type'       => $sizeType,
                    'quantity'        => $coilQty,
                    'unit_weight'     => $perCoilWeight,
                    'total_weight'    => $totalWeight,
                    'unit_price'      => $rate,
                    'sub_price'       => $itemSub,
                    'total_price'     => $itemSub,
                    'payment'         => $itemPayment,
                    'due'             => $itemDue,
                    'payment_method'  => $data['payment_method'] ?? 'cash',
                    'bank_detail_id'  => !empty($data['bank_detail_id']) ? $data['bank_detail_id'] : null,
                    'transaction_ref' => $data['transaction_ref'] ?? null,
                    'created_by'      => Auth::id(),
                ]);

                // 2. Register Single Batch Coil in Yard Stock
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
                    'total_price'      => $itemSub,
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
                        'debit' => $batchSubTotal,
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

                    $totalBatchDue = max(0, round($batchSubTotal - $totalPayment, 2));
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
            } catch (\Throwable $e) {}

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
