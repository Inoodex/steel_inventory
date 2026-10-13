<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Phase3DatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase3_indexes_exist_on_all_target_tables(): void
    {
        // 1. inventories
        $this->assertTrue(Schema::hasTable('inventories'));
        $this->assertTrue(Schema::hasIndex('inventories', 'inventories_product_id_index'));

        // 2. returns & return_items
        $this->assertTrue(Schema::hasTable('returns'));
        $this->assertTrue(Schema::hasIndex('returns', 'returns_status_index'));
        $this->assertTrue(Schema::hasIndex('returns', 'returns_customer_status_index'));
        $this->assertTrue(Schema::hasIndex('returns', 'returns_sale_id_index'));
        $this->assertTrue(Schema::hasTable('return_items'));
        $this->assertTrue(Schema::hasIndex('return_items', 'return_items_return_id_index'));
        $this->assertTrue(Schema::hasIndex('return_items', 'return_items_sales_item_id_index'));

        // 3. bank_details
        $this->assertTrue(Schema::hasTable('bank_details'));
        $this->assertTrue(Schema::hasIndex('bank_details', 'bank_details_status_index'));
        $this->assertTrue(Schema::hasIndex('bank_details', 'bank_details_is_active_index'));

        // 4. lots
        $this->assertTrue(Schema::hasTable('lots'));
        $this->assertTrue(Schema::hasIndex('lots', 'lots_status_index'));
        $this->assertTrue(Schema::hasIndex('lots', 'lots_vendor_status_index'));

        // 5. salaries, salary_advances, ta_das
        $this->assertTrue(Schema::hasTable('salaries'));
        $this->assertTrue(Schema::hasIndex('salaries', 'salaries_status_index'));
        $this->assertTrue(Schema::hasIndex('salaries', 'salaries_employee_month_index'));
        $this->assertTrue(Schema::hasTable('salary_advances'));
        $this->assertTrue(Schema::hasIndex('salary_advances', 'salary_advances_status_index'));
        $this->assertTrue(Schema::hasIndex('salary_advances', 'salary_advances_employee_id_index'));
        $this->assertTrue(Schema::hasTable('ta_das'));
        $this->assertTrue(Schema::hasIndex('ta_das', 'ta_das_status_index'));
        $this->assertTrue(Schema::hasIndex('ta_das', 'ta_das_employee_id_index'));

        // 6. company_details, employees
        $this->assertTrue(Schema::hasTable('company_details'));
        $this->assertTrue(Schema::hasIndex('company_details', 'company_details_phone_index'));
        $this->assertTrue(Schema::hasTable('employees'));
        $this->assertTrue(Schema::hasIndex('employees', 'employees_phone_index'));
        $this->assertTrue(Schema::hasIndex('employees', 'employees_status_index'));
    }
}
