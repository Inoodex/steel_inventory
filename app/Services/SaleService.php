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
                'product_id'       => $data['product'][0] ?? null,
                'qty'              => !empty($data['qty']) ? array_sum($data['qty']) : 0,
                'total'            => $financials['total'],
                'payble'           => $financials['payble'],
                'bill'             => $financials['total'],
                'discount'         => $financials['discount'],
                'advanced_payment' => $financials['advanced_payment'],
                'due_payment'      => $financials['due_payment'],
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

            // 6. Auto-post double-entry journal voucher for Sale
            try {
                $arAcc = \App\Models\ChartOfAccount::where('account_code', '1130')->first();
                $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
                $revAcc = \App\Models\ChartOfAccount::where('account_code', '4110')->first();

                if ($arAcc && $revAcc) {
                    $items = [];
                    $grandTotal = (float) $sale->payble;
                    $paid = (float) $sale->advanced_payment;
                    $due = (float) $sale->due_payment;

                    if ($paid > 0 && $cashAcc) {
                        $items[] = ['account_id' => $cashAcc->id, 'debit' => $paid, 'credit' => 0.00, 'description' => 'Cash/Bank collected for Sale ' . $sale->order_no];
                    }
                    if ($due > 0) {
                        $items[] = ['account_id' => $arAcc->id, 'debit' => $due, 'credit' => 0.00, 'description' => 'Receivable due for Sale ' . $sale->order_no];
                    }
                    $items[] = ['account_id' => $revAcc->id, 'debit' => 0.00, 'credit' => $grandTotal, 'description' => 'Sales revenue recognized'];

                    postJournalEntry([
                        'entry_date' => date('Y-m-d'),
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'description' => 'Invoice ' . $sale->order_no . ' — Customer #' . $sale->customer_id,
                        'items' => $items
                    ]);
                }
            } catch (\Throwable $e) {}

            // 7. Broadcast real-time Pusher event
            event(new \App\Events\SaleCreatedEvent($sale));

            return $sale;
        });
    }

    /**
     * Resolve customer by ID or create new.
     */
    private function resolveCustomer(array $data): Customer
    {
        if (!empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        return Customer::create([
            'name'           => $data['customer_name'] ?? 'Walk-in Customer',
            'phone'          => $data['customer_phone'] ?? null,
            'address'        => $data['customer_address'] ?? null,
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

        $otherCharges = (float)($data['delivery_charge'] ?? 0)
                      + (float)($data['labour_cost'] ?? 0)
                      + (float)($data['weight_scale_cost'] ?? 0)
                      + (float)($data['other_charges'] ?? 0)
                      + (float)($data['vat'] ?? 0)
                      + (float)($data['tax'] ?? 0);

        $discount = (float)($data['discount'] ?? 0);
        $total    = max(0, $subtotal + $otherCharges);
        $payble   = max(0, $total - $discount);

        $advancedPayment = (float)($data['advanced_payment'] ?? 0);
        $duePayment = $data['duePayment'] ?? ($payble - $advancedPayment);

        $status = match(true) {
            $duePayment <= 0          => 'paid',
            $advancedPayment > 0      => 'partial',
            default                   => 'credit',
        };

        return compact('total', 'discount', 'payble', 'advancedPayment', 'duePayment', 'status')
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
