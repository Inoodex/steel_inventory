<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyExpense;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\Salary;
use App\Models\Sale;
use App\Models\TaDa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase1SecurityAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user1;
    protected User $user2;
    protected Employee $employee1;
    protected Employee $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@test.com',
            'phone'    => '01711000000',
            'password' => bcrypt('password'),
            'status'   => '1',
        ]);
        $this->admin->assignRole($adminRole);

        $this->user1 = User::create([
            'name'     => 'Worker One',
            'email'    => 'worker1@test.com',
            'phone'    => '01711000001',
            'password' => bcrypt('password'),
            'status'   => '1',
        ]);
        $this->user1->assignRole($employeeRole);

        $this->user2 = User::create([
            'name'     => 'Worker Two',
            'email'    => 'worker2@test.com',
            'phone'    => '01711000002',
            'password' => bcrypt('password'),
            'status'   => '1',
        ]);
        $this->user2->assignRole($employeeRole);

        $this->employee1 = Employee::create([
            'user_id' => $this->user1->id,
            'name'    => 'Worker One',
            'phone'   => '01711000001',
            'status'  => 'active',
        ]);

        $this->employee2 = Employee::create([
            'user_id' => $this->user2->id,
            'name'    => 'Worker Two',
            'phone'   => '01711000002',
            'status'  => 'active',
        ]);
    }

    public function test_employee_cannot_update_another_employees_tada_record_idor(): void
    {
        $tada2 = TaDa::create([
            'employee_id' => $this->employee2->id,
            'date'        => date('Y-m-d'),
            'amount'      => 1000.00,
            'purpose'     => 'Field visit',
            'status'      => 'pending',
        ]);

        // Attempt by Worker 1 to update Worker 2's TA/DA record
        $response = $this->actingAs($this->user1)->put(route('employee.tada.update', $tada2->id), [
            'used_amount' => 500.00,
        ]);

        // Must fail with 404 Not Found due to scoped query
        $response->assertStatus(404);
    }

    public function test_employee_cannot_submit_negative_tada_claim(): void
    {
        $response = $this->actingAs($this->user1)->post(route('employee.tada.store'), [
            'date'         => date('Y-m-d'),
            'amount'       => -250.00,
            'type'         => 'TA',
            'payment_type' => 'Claim',
            'purpose'      => 'Malicious negative claim',
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseMissing('ta_das', [
            'employee_id' => $this->employee1->id,
            'amount'      => -250.00,
        ]);
    }

    public function test_payment_deletion_reverts_sale_due_and_deletes_linked_journal_voucher(): void
    {
        $customer = Customer::create([
            'name'    => 'Test Corp',
            'phone'   => '01911999888',
            'address' => 'Dhaka',
            'status'  => 'active',
        ]);

        $sale = Sale::create([
            'customer_id'      => $customer->id,
            'order_no'         => 'SO-DEL-001',
            'total'            => 10000.00,
            'bill'             => 10000.00,
            'payble'           => 10000.00,
            'advanced_payment' => 5000.00,
            'due_payment'      => 5000.00,
            'status'           => 'partial',
            'payment_status'   => 0,
        ]);

        $payment = Payment::create([
            'payment_for'    => 2,
            'customer_id'    => $customer->id,
            'sale_id'        => $sale->id,
            'amount'         => 3000.00,
            'payment_method' => 'bank',
            'status'         => 1,
        ]);

        // Add a linked journal voucher
        $journal = JournalEntry::create([
            'journal_no'     => 'JV-DEL-001',
            'entry_date'     => date('Y-m-d'),
            'reference_type' => 'customer_payment',
            'reference_id'   => $payment->id,
            'description'    => 'Test customer payment voucher',
            'total_debit'    => 3000.00,
            'total_credit'   => 3000.00,
            'status'         => 'posted',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('delete.payment', $payment->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('journal_entries', ['id' => $journal->id]);

        $sale->refresh();
        $this->assertEquals(2000.00, (float)$sale->advanced_payment);
        $this->assertEquals(8000.00, (float)$sale->due_payment);
    }

    public function test_salary_store_validates_employee_id_existence(): void
    {
        $response = $this->actingAs($this->admin)->post(route('salary.store'), [
            'employee_id'    => 999999, // non-existent
            'month'          => '2026-10',
            'basic_salary'   => 25000.00,
            'payment_status' => 'paid',
        ]);

        $response->assertSessionHasErrors(['employee_id']);
        $this->assertDatabaseMissing('salaries', [
            'basic_salary' => 25000.00,
        ]);
    }

    public function test_salary_destroy_removes_linked_journals_transactionally(): void
    {
        $data = [
            'employee_id'  => $this->employee1->id,
            'basic_salary' => 30000.00,
            'net_salary'   => 30000.00,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'month')) {
            $data['month'] = '2026-10';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'month_year')) {
            $data['month_year'] = '2026-10';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'status')) {
            $data['status'] = 'paid';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'payment_status')) {
            $data['payment_status'] = 'paid';
        }

        $salary = Salary::create($data);

        $journal = JournalEntry::create([
            'journal_no'     => 'JV-SAL-001',
            'entry_date'     => date('Y-m-d'),
            'reference_type' => 'salary',
            'reference_id'   => $salary->id,
            'description'    => 'Salary disbursement journal',
            'total_debit'    => 30000.00,
            'total_credit'   => 30000.00,
            'status'         => 'posted',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('salary.destroy', $salary->id));

        $response->assertRedirect(route('salary.index'));
        $this->assertDatabaseMissing('salaries', ['id' => $salary->id]);
        $this->assertDatabaseMissing('journal_entries', ['id' => $journal->id]);
    }

    public function test_expense_destroy_cleans_up_journal_entries_in_transaction(): void
    {
        $category = ExpenseCategory::create(['name' => 'Printing', 'status' => 'active']);

        $expense = DailyExpense::create([
            'date'                => date('Y-m-d'),
            'expense_category_id' => $category->id,
            'amount'              => 800.00,
            'spend_method'        => 'cash',
        ]);

        $journal = JournalEntry::create([
            'journal_no'     => 'JV-EXP-001',
            'entry_date'     => date('Y-m-d'),
            'reference_type' => 'expense',
            'reference_id'   => $expense->id,
            'description'    => 'Daily expense journal',
            'total_debit'    => 800.00,
            'total_credit'   => 800.00,
            'status'         => 'posted',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('dailyExpenses.destroy', $expense->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('daily_expenses', ['id' => $expense->id]);
        $this->assertDatabaseMissing('journal_entries', ['id' => $journal->id]);
    }
}
