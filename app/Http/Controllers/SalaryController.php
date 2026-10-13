<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\Employee;
use App\Models\TaDa;
use App\Models\ExpenseCategory;
use App\Models\DailyExpense;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index()
    {
        $salaries = Salary::with('employee')->latest()->get();
        return view('frontend.pages.salary.index', compact('salaries'));
    }

    public function create()
    {
        $employees = Employee::orderBy('name')->get();
        $currentMonth = date('Y-m');
        $taDaData = [];
        return view('frontend.pages.salary.create', compact('employees', 'currentMonth', 'taDaData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'month'          => 'required|string',
            'basic_salary'   => 'required|numeric|min:0',
            'advance'        => 'nullable|numeric|min:0',
            'allowance'      => 'nullable|numeric|min:0',
            'deduction'      => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_date'   => 'nullable|date',
            'note'           => 'nullable|string|max:500',
        ]);

        $netSalary = (float)$validated['basic_salary'] + (float)($validated['allowance'] ?? 0) - (float)($validated['deduction'] ?? 0) - (float)($validated['advance'] ?? 0);
        $netSalary = max(0, $netSalary);

        $data = [
            'employee_id'  => $validated['employee_id'],
            'basic_salary' => (float)$validated['basic_salary'],
            'allowance'    => (float)($validated['allowance'] ?? 0),
            'net_salary'   => $netSalary,
            'payment_date' => $validated['payment_date'] ?? null,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'month')) {
            $data['month'] = $validated['month'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'month_year')) {
            $data['month_year'] = $validated['month'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'advance')) {
            $data['advance'] = (float)($validated['advance'] ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'advance_deduction')) {
            $data['advance_deduction'] = (float)($validated['advance'] ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'deduction')) {
            $data['deduction'] = (float)($validated['deduction'] ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'payment_status')) {
            $data['payment_status'] = $validated['payment_status'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'status')) {
            $data['status'] = $validated['payment_status'] === 'paid' ? 'paid' : 'pending';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'note')) {
            $data['note'] = $validated['note'] ?? null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'notes')) {
            $data['notes'] = $validated['note'] ?? null;
        }

        $salary = Salary::create($data);

        try {
            $this->postSalaryJournal($salary);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Salary #{$salary->id} auto-journal notice: " . $e->getMessage());
        }

        return redirect()->route('salary.index')->with('success', 'Salary record created successfully.');
    }

    public function edit($id)
    {
        $salary = Salary::findOrFail($id);
        $employees = Employee::orderBy('name')->get();
        return view('frontend.pages.salary.edit', compact('salary','employees'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'month'          => 'required|string',
            'basic_salary'   => 'required|numeric|min:0',
            'advance'        => 'nullable|numeric|min:0',
            'allowance'      => 'nullable|numeric|min:0',
            'deduction'      => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_date'   => 'nullable|date',
            'note'           => 'nullable|string|max:500',
        ]);

        $netSalary = (float)$validated['basic_salary'] + (float)($validated['allowance'] ?? 0) - (float)($validated['deduction'] ?? 0) - (float)($validated['advance'] ?? 0);
        $netSalary = max(0, $netSalary);

        $data = [
            'employee_id'  => $validated['employee_id'],
            'basic_salary' => (float)$validated['basic_salary'],
            'allowance'    => (float)($validated['allowance'] ?? 0),
            'net_salary'   => $netSalary,
            'payment_date' => $validated['payment_date'] ?? null,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'month')) {
            $data['month'] = $validated['month'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'month_year')) {
            $data['month_year'] = $validated['month'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'advance')) {
            $data['advance'] = (float)($validated['advance'] ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'advance_deduction')) {
            $data['advance_deduction'] = (float)($validated['advance'] ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'deduction')) {
            $data['deduction'] = (float)($validated['deduction'] ?? 0);
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'payment_status')) {
            $data['payment_status'] = $validated['payment_status'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'status')) {
            $data['status'] = $validated['payment_status'] === 'paid' ? 'paid' : 'pending';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'note')) {
            $data['note'] = $validated['note'] ?? null;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('salaries', 'notes')) {
            $data['notes'] = $validated['note'] ?? null;
        }

        $salary = Salary::findOrFail($id);
        $salary->update($data);

        try {
            $this->postSalaryJournal($salary);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Salary #{$salary->id} update auto-journal notice: " . $e->getMessage());
        }

        return redirect()->route('salary.index')->with('success', 'Salary record updated successfully.');
    }

    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $linkedJournals = \App\Models\JournalEntry::where('reference_type', 'salary')
                ->where('reference_id', $salary->id)
                ->get();
            foreach ($linkedJournals as $j) {
                $j->items()->delete();
                $j->delete();
            }

            $salary->delete();

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('salary.index')->with('success', 'Salary record deleted successfully.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('salary.index')->with('error', 'Failed to delete salary record: ' . $e->getMessage());
        }
    }

    /**
     * Post or update GL journal entry for a paid salary record.
     */
    protected function postSalaryJournal(Salary $salary): void
    {
        if (!function_exists('postJournalEntry')) {
            return;
        }

        // Delete any existing un-reversed journal for this salary to avoid duplicates on update
        $existingJournals = \App\Models\JournalEntry::where('reference_type', 'salary')
            ->where('reference_id', $salary->id)
            ->where('status', '!=', 'reversed')
            ->get();
        foreach ($existingJournals as $ej) {
            $ej->items()->delete();
            $ej->delete();
        }

        // Only post journal if salary payment_status is 'paid'
        if ($salary->payment_status !== 'paid') {
            return;
        }

        $basic = (float) $salary->basic_salary;
        $allowance = (float) ($salary->allowance ?? 0);
        $deduction = (float) ($salary->deduction ?? 0);
        $advance = (float) ($salary->advance ?? 0);
        $netSalary = (float) $salary->net_salary;

        // Gross salary expense to debit
        $grossExpense = max(0, $basic + $allowance - $deduction);
        if ($grossExpense <= 0 && $netSalary <= 0) {
            return;
        }

        $salaryExpAcc = \App\Models\ChartOfAccount::where('account_code', '5210')->first();
        $advanceAcc = \App\Models\ChartOfAccount::where('account_code', '1150')->first();
        $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();

        if (!$salaryExpAcc) {
            $salaryExpAcc = \App\Models\ChartOfAccount::where('account_type', 'expense')->first();
        }
        if (!$cashAcc) {
            $cashAcc = \App\Models\ChartOfAccount::where('account_type', 'asset')->first();
        }

        $employee = $salary->employee;
        $empName = $employee ? $employee->name : "Employee #{$salary->employee_id}";
        $monthStr = $salary->month ? date('M Y', strtotime($salary->month . '-01')) : date('M Y');
        $desc = "Salary Payment for {$empName} - {$monthStr}";

        $journalItems = [];

        // Debit: Salary Expense for the full gross expense incurred
        $journalItems[] = [
            'account_id' => $salaryExpAcc->id,
            'debit' => $grossExpense,
            'credit' => 0.00,
            'description' => "Gross Salary Expense for {$empName} ({$monthStr})",
        ];

        // Credit: Employee Advance asset account for the deducted advance portion
        if ($advance > 0 && $advanceAcc) {
            $journalItems[] = [
                'account_id' => $advanceAcc->id,
                'debit' => 0.00,
                'credit' => $advance,
                'description' => "Advance recovery from {$empName}",
            ];
        }

        // Credit: Cash in Hand for the actual net cash disbursed
        if ($netSalary > 0 && $cashAcc) {
            $journalItems[] = [
                'account_id' => $cashAcc->id,
                'debit' => 0.00,
                'credit' => $netSalary,
                'description' => "Net Salary Cash Payout to {$empName}",
            ];
        }

        // Check if double-entry balancing is preserved
        $totalDebit = array_sum(array_column($journalItems, 'debit'));
        $totalCredit = array_sum(array_column($journalItems, 'credit'));

        if (abs($totalDebit - $totalCredit) < 0.001 && $totalDebit > 0) {
            postJournalEntry([
                'entry_date' => $salary->payment_date ?? date('Y-m-d'),
                'reference_type' => 'salary',
                'reference_id' => $salary->id,
                'description' => $desc,
                'status' => 'approved',
                'created_by' => \Illuminate\Support\Facades\Auth::id() ?? \App\Models\User::value('id'),
                'items' => $journalItems,
            ]);
        }
    }
}