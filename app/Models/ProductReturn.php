<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'return_date',
        'total_refund_amount',
        'status',
        'reason',
        'notes',
        'processed_by',
        'processed_at'
    ];

    protected $casts = [
        'return_date' => 'date',
        'processed_at' => 'datetime',
        'total_refund_amount' => 'decimal:2'
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    // Status helpers
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    // Calculate total refund amount from items
    public function calculateTotalRefund()
    {
        return $this->items->sum('total_price');
    }

    // Approve return: updates stock, adjusts sale invoice amounts, creates refund payment, and marks approved
    public function approve($userId)
    {
        $this->load(['sale', 'items.salesItem']);

        $this->update([
            'status'              => 'approved',
            'processed_by'        => $userId,
            'processed_at'        => now(),
            'total_refund_amount' => $this->calculateTotalRefund()
        ]);

        // 1. Update stock for each item
        foreach ($this->items as $item) {
            $this->addToStock($item);
        }

        // 2. Update sale invoice amounts
        $this->updateSaleAmounts();

        // 3. Create refund payment record
        $this->createRefundPayment($userId);

        // 4. Update sales_items returned_qty
        $this->updateSalesItemsReturnedQty();

        // 5. Post double-entry journal voucher to accounting ledger
        $this->postReturnJournalEntry($userId);
    }

    // Alias for complete if called anywhere
    public function complete($userId)
    {
        $this->approve($userId);
    }

    // Post double-entry bookkeeping journal voucher for approved return
    public function postReturnJournalEntry($userId)
    {
        $refundAmount = (float) $this->total_refund_amount;
        if ($refundAmount <= 0) {
            return;
        }

        // Avoid duplicate journal entry for the same return
        $existing = \App\Models\JournalEntry::where('reference_type', 'return')
            ->where('reference_id', $this->id)
            ->first();
        if ($existing) {
            return;
        }

        try {
            // Debit 5120 (Sales Returns) | Credit 1110 (Cash in Hand)
            $returnsAcc = \App\Models\ChartOfAccount::where('account_code', '5120')->first()
                       ?? \App\Models\ChartOfAccount::where('account_name', 'like', '%Sales Return%')->first();

            $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first()
                    ?? \App\Models\ChartOfAccount::where('account_name', 'like', '%Cash%')->first();

            if ($returnsAcc && $cashAcc && function_exists('postJournalEntry')) {
                $orderNo = $this->sale ? $this->sale->order_no : ('Sale #' . $this->sale_id);
                $customerName = $this->customer ? $this->customer->name : ('Customer #' . $this->customer_id);

                postJournalEntry([
                    'entry_date'     => $this->return_date ? (\Carbon\Carbon::parse($this->return_date)->format('Y-m-d')) : date('Y-m-d'),
                    'reference_type' => 'return',
                    'reference_id'   => $this->id,
                    'description'    => "Sales Return #{$this->id} — Refund for Invoice #{$orderNo} ({$customerName})",
                    'status'         => 'approved',
                    'created_by'     => $userId,
                    'items'          => [
                        [
                            'account_id'  => $returnsAcc->id,
                            'debit'       => $refundAmount,
                            'credit'      => 0.00,
                            'description' => "Sales Return allowance for Order #{$orderNo}"
                        ],
                        [
                            'account_id'  => $cashAcc->id,
                            'debit'       => 0.00,
                            'credit'      => $refundAmount,
                            'description' => "Cash refund paid to {$customerName} for Return #{$this->id}"
                        ]
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Return #{$this->id} journal voucher posting skipped: " . $e->getMessage());
        }
    }

    // Update sale payable and total amounts
    private function updateSaleAmounts()
    {
        $sale = $this->sale;
        $refundAmount = $this->total_refund_amount;

        $newPayble = $sale->payble - $refundAmount;
        $newTotal = $sale->total - $refundAmount;
        $newDuePayment = max(0, $sale->payble - $sale->advanced_payment - $refundAmount);

        $sale->update([
            'payble' => max(0, $newPayble),
            'total' => max(0, $newTotal),
            'due_payment' => $newDuePayment,
        ]);
    }

    // Create refund payment record
    private function createRefundPayment($userId)
    {
        // Only use fields defined in Payment::$fillable
        Payment::create([
            'sale_id'        => $this->sale_id,
            'customer_id'    => $this->customer_id,
            'payment_for'    => 3, // 3 = refund
            'payment_method' => 'cash',
            'amount'         => -abs($this->total_refund_amount), // Negative for refund
            'remarks'        => "Refund for Return #{$this->id}",
        ]);
    }

    // Update returned_qty for each sales_item
    private function updateSalesItemsReturnedQty()
    {
        foreach ($this->items as $returnItem) {
            if ($returnItem->sales_item_id) {
                $salesItem = SalesItem::find($returnItem->sales_item_id);
                if ($salesItem) {
                    $salesItem->increment('returned_qty', $returnItem->quantity);
                }
            }
        }
    }

    // Add returned item back to stock
    // For steel coil system: increases Coil remaining_weight, restores in_stock status if was exhausted, and syncs Inventory.
    private function addToStock($item)
    {
        $coilId = $item->product_id;
        if (!$coilId && $item->sales_item_id) {
            $sItem = \App\Models\SalesItem::find($item->sales_item_id);
            $coilId = $sItem->coil_id ?? $sItem->product_id ?? null;
        }

        if ($coilId) {
            $coil = \App\Models\Coil::find($coilId);
            if ($coil) {
                $newRemaining = (float)$coil->remaining_weight + (float)$item->quantity;
                $coil->remaining_weight = $newRemaining;
                if ($coil->status === 'exhausted' && $newRemaining > 0) {
                    $coil->status = 'in_stock';
                }
                $coil->save();
            }

            // Sync legacy Inventory table if present
            $inventory = \App\Models\Inventory::where('product_id', $coilId)->first();
            if ($inventory) {
                $inventory->increment('current_stock', $item->quantity);
            } else {
                \App\Models\Inventory::create([
                    'product_id'    => $coilId,
                    'opening_stock' => 0,
                    'current_stock' => $item->quantity,
                ]);
            }
        }
    }

    // Reject return
    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'processed_by' => $userId,
            'processed_at' => now(),
            'notes' => $reason ?? $this->notes
        ]);
    }
}
