<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        // 1. Inventory & Products (I1)
        $this->addIndexIfMissing('inventories', 'product_id', 'inventories_product_id_index');

        // 2. Returns & Customer Return Items (I2)
        $this->addIndexIfMissing('returns', 'status', 'returns_status_index');
        $this->addIndexIfMissing('returns', ['customer_id', 'status'], 'returns_customer_status_index');
        $this->addIndexIfMissing('returns', 'sale_id', 'returns_sale_id_index');
        $this->addIndexIfMissing('return_items', 'return_id', 'return_items_return_id_index');
        $this->addIndexIfMissing('return_items', 'sales_item_id', 'return_items_sales_item_id_index');

        // 3. Bank & Payment Accounts (I3)
        $this->addIndexIfMissing('bank_details', 'status', 'bank_details_status_index');
        $this->addIndexIfMissing('bank_details', 'is_active', 'bank_details_is_active_index');

        // 4. Lots & Procurement (I4)
        $this->addIndexIfMissing('lots', 'status', 'lots_status_index');
        $this->addIndexIfMissing('lots', ['vendor_id', 'status'], 'lots_vendor_status_index');

        // 5. Salaries, Advances & TA/DA (I5)
        $this->addIndexIfMissing('salaries', 'status', 'salaries_status_index');
        $this->addIndexIfMissing('salaries', ['employee_id', 'month_year'], 'salaries_employee_month_index');
        $this->addIndexIfMissing('salary_advances', 'status', 'salary_advances_status_index');
        $this->addIndexIfMissing('salary_advances', 'employee_id', 'salary_advances_employee_id_index');
        $this->addIndexIfMissing('ta_das', 'status', 'ta_das_status_index');
        $this->addIndexIfMissing('ta_das', 'employee_id', 'ta_das_employee_id_index');

        // 6. Profiles & Contacts (I6)
        $this->addIndexIfMissing('company_details', 'phone', 'company_details_phone_index');
        $this->addIndexIfMissing('employees', 'phone', 'employees_phone_index');
        $this->addIndexIfMissing('employees', 'status', 'employees_status_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('inventories', 'inventories_product_id_index');
        $this->dropIndexIfExists('returns', 'returns_status_index');
        $this->dropIndexIfExists('returns', 'returns_customer_status_index');
        $this->dropIndexIfExists('returns', 'returns_sale_id_index');
        $this->dropIndexIfExists('return_items', 'return_items_return_id_index');
        $this->dropIndexIfExists('return_items', 'return_items_sales_item_id_index');
        $this->dropIndexIfExists('bank_details', 'bank_details_status_index');
        $this->dropIndexIfExists('bank_details', 'bank_details_is_active_index');
        $this->dropIndexIfExists('lots', 'lots_status_index');
        $this->dropIndexIfExists('lots', 'lots_vendor_status_index');
        $this->dropIndexIfExists('salaries', 'salaries_status_index');
        $this->dropIndexIfExists('salaries', 'salaries_employee_month_index');
        $this->dropIndexIfExists('salary_advances', 'salary_advances_status_index');
        $this->dropIndexIfExists('salary_advances', 'salary_advances_employee_id_index');
        $this->dropIndexIfExists('ta_das', 'ta_das_status_index');
        $this->dropIndexIfExists('ta_das', 'ta_das_employee_id_index');
        $this->dropIndexIfExists('company_details', 'company_details_phone_index');
        $this->dropIndexIfExists('employees', 'employees_phone_index');
        $this->dropIndexIfExists('employees', 'employees_status_index');
    }
};
