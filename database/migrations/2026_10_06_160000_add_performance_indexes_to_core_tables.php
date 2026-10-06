<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper to safely add an index if it doesn't already exist.
     */
    private function addIndexIfMissing(string $table, string|array $columns, ?string $indexName = null): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $cols = (array) $columns;
        foreach ($cols as $col) {
            if (!Schema::hasColumn($table, $col)) {
                return;
            }
        }

        $name = $indexName ?? ($table . '_' . implode('_', $cols) . '_index');

        if (Schema::hasIndex($table, $name)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $t) use ($cols, $name) {
                $t->index($cols, $name);
            });
        } catch (\Throwable $e) {
            // Index might already exist or driver does not support duplicate names
        }
    }

    /**
     * Helper to safely drop an index if it exists.
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Inventory & Steel Coils
        $this->addIndexIfMissing('coils', 'status', 'coils_status_index');
        $this->addIndexIfMissing('coils', 'coil_number', 'coils_coil_number_index');
        $this->addIndexIfMissing('coils', ['warehouse_id', 'status'], 'coils_warehouse_status_index');

        // 2. Sales & Invoices
        $this->addIndexIfMissing('sales', 'status', 'sales_status_index');
        $this->addIndexIfMissing('sales', 'order_date', 'sales_order_date_index');
        $this->addIndexIfMissing('sales', ['customer_id', 'status'], 'sales_customer_status_index');

        // 3. Purchases & Ship Procurement
        $this->addIndexIfMissing('purchases', 'status', 'purchases_status_index');
        $this->addIndexIfMissing('purchases', ['vendor_id', 'lot_id'], 'purchases_vendor_lot_index');

        // 4. Payments & Financial Transactions
        $this->addIndexIfMissing('payments', 'status', 'payments_status_index');
        $this->addIndexIfMissing('payments', 'payment_date', 'payments_payment_date_index');
        $this->addIndexIfMissing('payments', ['customer_id', 'payment_date'], 'payments_customer_date_index');
        $this->addIndexIfMissing('payments', ['vendor_id', 'payment_date'], 'payments_vendor_date_index');

        // 5. Customers & Vendors Lookup
        $this->addIndexIfMissing('customers', 'phone', 'customers_phone_index');
        $this->addIndexIfMissing('customers', 'status', 'customers_status_index');
        $this->addIndexIfMissing('vendors', 'phone', 'vendors_phone_index');
        $this->addIndexIfMissing('vendors', 'status', 'vendors_status_index');

        // 6. Expenses & TA/DA Logs
        $this->addIndexIfMissing('daily_expenses', 'date', 'daily_expenses_date_index');
        $this->addIndexIfMissing('ta_das', 'date', 'ta_das_date_index');

        // 7. Double-Entry Accounting
        $this->addIndexIfMissing('journal_entries', ['reference_type', 'reference_id'], 'journal_entries_reference_index');
        $this->addIndexIfMissing('journal_entries', 'status', 'journal_entries_status_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('coils', 'coils_status_index');
        $this->dropIndexIfExists('coils', 'coils_coil_number_index');
        $this->dropIndexIfExists('coils', 'coils_warehouse_status_index');
        $this->dropIndexIfExists('sales', 'sales_status_index');
        $this->dropIndexIfExists('sales', 'sales_order_date_index');
        $this->dropIndexIfExists('sales', 'sales_customer_status_index');
        $this->dropIndexIfExists('purchases', 'purchases_status_index');
        $this->dropIndexIfExists('purchases', 'purchases_vendor_lot_index');
        $this->dropIndexIfExists('payments', 'payments_status_index');
        $this->dropIndexIfExists('payments', 'payments_payment_date_index');
        $this->dropIndexIfExists('payments', 'payments_customer_date_index');
        $this->dropIndexIfExists('payments', 'payments_vendor_date_index');
        $this->dropIndexIfExists('customers', 'customers_phone_index');
        $this->dropIndexIfExists('customers', 'customers_status_index');
        $this->dropIndexIfExists('vendors', 'vendors_phone_index');
        $this->dropIndexIfExists('vendors', 'vendors_status_index');
        $this->dropIndexIfExists('daily_expenses', 'daily_expenses_date_index');
        $this->dropIndexIfExists('ta_das', 'ta_das_date_index');
        $this->dropIndexIfExists('journal_entries', 'journal_entries_reference_index');
        $this->dropIndexIfExists('journal_entries', 'journal_entries_status_index');
    }
};
