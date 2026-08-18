<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\Coil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private InventoryService $inventoryService) {}

    /**
     * Create a full sale — customer, sale record, items, inventory deduction.
     * Wrapped in a DB transaction by the caller or internally.
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            // 1. Resolve customer (new or existing)
            $customer = $this->resolveCustomer($data);

            // 2. Calculate financials
            $financials = $this->calculateFinancials($data);

            // 3. Generate invoice number
            $invoiceNumber = 'INV-' . strtoupper(uniqid());

            // 4. Create Sale record
            $sale = Sale::create([
                'order_no'         => $invoiceNumber,
                'customer_id'      => $customer->id,
                'qty'              => !empty($data['qty']) ? array_sum($data['qty']) : 0,
                'subtotal'         => $financials['subtotal'],
                'total'            => $financials['total'],
                'payble'           => $financials['payble'],
                'bill'             => $financials['total'],
                'discount'         => $financials['discount'],
                'advanced_payment' => $financials['advanced_payment'],
                'due_payment'      => $financials['due_payment'],
                'payment_method'   => $data['payment_method'] ?? 'cash',
                'bank_detail_id'   => !empty($data['bank_detail_id']) ? $data['bank_detail_id'] : null,
                'transaction_ref'  => $data['transaction_ref'] ?? null,
                'sales_by'         => Auth::id(),
                'status'           => $financials['status'],
                'warehouse_id'     => $data['warehouse_id'] ?? null,
                'delivery_status'  => $data['delivery_status'] ?? 'pending',
                'vat'              => $data['vat'] ?? 0,
                'tax'              => $data['tax'] ?? 0,
                'delivery_charge'  => $data['delivery_charge'] ?? 0,
                'labour_cost'      => $data['labour_cost'] ?? 0,
                'weight_scale_cost'=> $data['weight_scale_cost'] ?? 0,
                'other_charges'    => $data['other_charges'] ?? 0,
                'note'             => $data['note'] ?? null,
            ]);

            // 5. Create line items and deduct inventory
            $this->createSaleItems($sale, $data);

            // 6. Record Initial Payment entry if paid > 0
            $paid = (float) $sale->advanced_payment;
            if ($paid > 0) {
                Payment::create([
                    'customer_id'    => $customer->id,
                    'sale_id'        => $sale->id,
                    'payment_for'    => 2,
                    'payment_method' => $sale->payment_method ?? 'cash',
                    'bank_detail_id' => $sale->bank_detail_id,
                    'transaction_ref'=> $sale->transaction_ref,
                    'amount'         => $paid,
                    'remarks'        => 'Initial receipt for ' . $sale->order_no,
                    'status'         => 1,
                    'created_by'     => Auth::id(),
                ]);
            }

            // 7. Auto-post double-entry journal voucher for Sale
            try {
                $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $revAcc = \App\Models\ChartOfAccount::where('account_code', '4110')->first();

                // Determine exact Cash or Bank Chart of Account based on Payment Method
                $collectionAcc = $cashAcc;
                if ($sale->payment_method !== 'cash') {
                    if (!empty($sale->bank_detail_id)) {
                        $bank = \App\Models\BankDetail::find($sale->bank_detail_id);
                        $collectionAcc = $bank?->resolveChartOfAccount() 
                            ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first() 
                            ?? $cashAcc;
                    } else {
                        $collectionAcc = \App\Models\ChartOfAccount::where('account_code', '1120')->first() ?? $cashAcc;
                    }
                }

                // Pass-Through Charges Payable account (2140)
                $chargesAcc = \App\Models\ChartOfAccount::firstOrCreate(
                    ['account_code' => '2140'],
                    [
                        'account_name' => 'Collected Extra Charges Payable (Pass-Through)',
                        'account_type' => 'liability',
                        'level' => 3,
                        'is_system' => true,
                        'status' => 'active',
                    ]
                );

                if ($arAcc && $revAcc) {
                    $items = [];
                    $due = (float) $sale->due_payment;

                    $subtotal = (float) ($sale->subtotal > 0 ? $sale->subtotal : $sale->items()->sum('total_price'));
                    $discount = (float) $sale->discount;

                    $vatPercent = (float) $sale->vat;
                    $taxPercent = (float) $sale->tax;
                    $vatAmount = round(($subtotal * $vatPercent) / 100, 2);
                    $taxAmount = round(($subtotal * $taxPercent) / 100, 2);

                    $extraCharges = (float)$sale->delivery_charge
                                  + (float)$sale->labour_cost
                                  + (float)$sale->weight_scale_cost
                                  + (float)$sale->other_charges
                                  + $vatAmount
                                  + $taxAmount;

                    $totalDebits = round($paid + $due, 2);
                    $netSalesRevenue = max(0, round($totalDebits - $extraCharges, 2));

                    $methodLabel = match($sale->payment_method) {
                        'bank' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        default => 'Cash'
                    };
                    $refText = $sale->transaction_ref ? " [Ref/Cheque: {$sale->transaction_ref}]" : "";

                    // 1. Debits: Cash / Bank / Receivable
                    if ($paid > 0 && $collectionAcc) {
                        $items[] = [
                            'account_id' => $collectionAcc->id,
                            'debit' => $paid,
                            'credit' => 0.00,
                            'description' => "{$methodLabel} collected for Sale {$sale->order_no}{$refText}"
                        ];
                    }
                    if ($due > 0) {
                        $items[] = ['account_id' => $arAcc->id, 'debit' => $due, 'credit' => 0.00, 'description' => 'Receivable due for Sale ' . $sale->order_no];
                    }

                    // 2. Credits: Net Product Revenue (4110)
                    if ($netSalesRevenue > 0) {
                        $items[] = ['account_id' => $revAcc->id, 'debit' => 0.00, 'credit' => $netSalesRevenue, 'description' => 'Net product sales revenue recognized'];
                    }

                    // 3. Credits: Pass-Through Extra Charges Payable (2140)
                    if ($extraCharges > 0 && $chargesAcc) {
                        $items[] = ['account_id' => $chargesAcc->id, 'debit' => 0.00, 'credit' => $extraCharges, 'description' => 'Collected extra charges (delivery/labour/scale/vat/tax) payable to handlers'];
                    }

                    postJournalEntry([
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'description' => 'Invoice ' . $sale->order_no . ' — Customer #' . $sale->customer_id,
                        'items' => $items
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Sale journal posting failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                throw $e;
            }

            // 8. Broadcast real-time Pusher event
            event(new \App\Events\SaleCreatedEvent($sale));

            return $sale;
        });
    }

    /**
     * Resolve customer by ID or create new.
     */
    private function resolveCustomer(array $data): Customer
    {
        if (!empty($data['existing_client_id'])) {
            return Customer::findOrFail($data['existing_client_id']);
        }

        return Customer::create([
            'name'           => $data['name'] ?? 'Walk-in Customer',
            'phone'          => $data['phone'] ?? null,
            'address'        => $data['address'] ?? null,
            'opening_balance'=> 0,
            'status'         => 'active',
        ]);
    }

    /**
     * Calculate financial totals.
     */
    private function calculateFinancials(array $data): array
    {
        $subtotal = 0;
        if (!empty($data['qty']) && !empty($data['unit_price'])) {
            foreach ($data['qty'] as $index => $qty) {
                $subtotal += ($qty * ($data['unit_price'][$index] ?? 0));
            }
        }

        $vatPercent = (float)($data['vat'] ?? 0);
        $taxPercent = (float)($data['tax'] ?? 0);
        $vatAmount  = round(($subtotal * $vatPercent) / 100, 2);
        $taxAmount  = round(($subtotal * $taxPercent) / 100, 2);

        $otherCharges = (float)($data['delivery_charge'] ?? 0)
                      + (float)($data['labour_cost'] ?? 0)
                      + (float)($data['weight_scale_cost'] ?? 0)
                      + (float)($data['other_charges'] ?? 0)
                      + $vatAmount
                      + $taxAmount;

        $discount = (float)($data['discount'] ?? 0);
        $total    = max(0, round($subtotal + $otherCharges, 2));
        $payble   = max(0, round($total - $discount, 2));

        $advancedPayment = (float)($data['advanced_payment'] ?? 0);
        $duePayment = isset($data['duePayment']) ? (float)$data['duePayment'] : max(0, round($payble - $advancedPayment, 2));

        $status = match(true) {
            $duePayment <= 0          => 'paid',
            $advancedPayment > 0      => 'partial',
            default                   => 'credit',
        };

        return compact('subtotal', 'total', 'discount', 'payble', 'advancedPayment', 'duePayment', 'status')
            + ['advanced_payment' => $advancedPayment, 'due_payment' => $duePayment];
    }

    /**
     * Create SalesItem records and deduct inventory/coils.
     */
    private function createSaleItems(Sale $sale, array $data): void
    {
        if (empty($data['qty'])) {
            return;
        }

        foreach ($data['qty'] as $index => $qty) {
            $unitPrice  = (float) ($data['unit_price'][$index] ?? 0);
            $total      = $unitPrice * $qty;
            $coilId     = !empty($data['coil_id'][$index]) ? $data['coil_id'][$index] : null;
            $productId  = !empty($data['product'][$index]) ? $data['product'][$index] : null;
            $lotId      = !empty($data['lot_id'][$index]) ? $data['lot_id'][$index] : null;
            $thickness  = $data['thickness'][$index] ?? null;
            $size       = $data['size'][$index] ?? null;
            $sizeType   = $data['size_type'][$index] ?? 'ft';

            $purchasePrice = 0;
            if ($coilId) {
                $coil = Coil::find($coilId);
                if ($coil) {
                    $purchasePrice = $coil->rate_per_ton;
                    $thickness = $thickness ?: $coil->thickness;
                    $size = $size ?: $coil->width;
                    $sizeType = $sizeType ?: $coil->length;
                    $lotId = $lotId ?: $coil->lot_id;

                    // Deduct coil weight
                    $newRemaining = max(0, (float)$coil->remaining_weight - (float)$qty);
                    $coil->remaining_weight = $newRemaining;
                    if ($newRemaining <= 0) {
                        $coil->status = 'exhausted';
                    }
                    $coil->save();
                }
            }

            $itemProfit = ($unitPrice - $purchasePrice) * $qty;

            SalesItem::create([
                'order_id'       => $sale->id,
                'coil_id'        => $coilId,
                'product_id'     => $productId,
                'lot_id'         => $lotId,
                'thickness'      => $thickness,
                'size'           => $size,
                'size_type'      => $sizeType,
                'unit_price'     => $unitPrice,
                'qty'            => $qty,
                'total_price'    => $total,
                'warranty'       => 0,
                'purchase_price' => $purchasePrice,
                'profit'         => $itemProfit,
            ]);
        }
    }

    /**
     * Record a payment against a sale.
     */
    public function recordPayment(Sale $sale, float $amount, string $method = 'cash', ?int $userId = null): Payment
    {
        $payment = Payment::create([
            'sale_id'        => $sale->id,
            'customer_id'    => $sale->customer_id,
            'payment_for'    => 1,
            'payment_method' => $method,
            'amount'         => $amount,
            'status'         => 1,
            'created_by'     => $userId ?? Auth::id(),
            'updated_by'     => $userId ?? Auth::id(),
        ]);

        // Update due payment on sale
        $newDue = max(0, $sale->due_payment - $amount);
        $sale->update([
            'due_payment'      => $newDue,
            'advanced_payment' => $sale->advanced_payment + $amount,
            'status'           => $newDue <= 0 ? 'paid' : 'partial',
        ]);

        return $payment;
    }
}
