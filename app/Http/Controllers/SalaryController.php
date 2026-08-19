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
        $request->validate([
            'employee_id' => 'required',
            'month' => 'required',
            'basic_salary' => 'required|numeric',
            'advance' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'deduction' => 'nullable|numeric',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_date' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);

        $data = $request->all();
        $data['net_salary'] = (float)$request->basic_salary + (float)($request->allowance ?? 0) - (float)($request->deduction ?? 0) - (float)($request->advance ?? 0);

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
        $request->validate([
            'employee_id' => 'required',
            'month' => 'required',
            'basic_salary' => 'required|numeric',
            'advance' => 'nullable|numeric',
            'allowance' => 'nullable|numeric',
            'deduction' => 'nullable|numeric',
            'payment_status' => 'required|in:paid,unpaid',
            'payment_date' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);

        $data = $request->all();
        $data['net_salary'] = (float)$request->basic_salary + (float)($request->allowance ?? 0) - (float)($request->deduction ?? 0) - (float)($request->advance ?? 0);

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

        try {
            $linkedJournals = \App\Models\JournalEntry::where('reference_type', 'salary')
                ->where('reference_id', $salary->id)
                ->get();
            foreach ($linkedJournals as $j) {
                $j->items()->delete();
                $j->delete();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Salary #{$id} delete journal cleanup notice: " . $e->getMessage());
        }

        $salary->delete();
        return redirect()->route('salary.index')->with('success', 'Salary record deleted successfully.');
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

public function getTaDaDataAjax(Request $request)
{
    $employeeId = $request->employee_id;
    $month = $request->month;
    $year = substr($month, 0, 4);
    $monthNum = substr($month, 5, 2);
    
    $taDaRecords = TaDa::where('employee_id', $employeeId)
                      ->whereYear('date', $year)
                      ->whereMonth('date', $monthNum)
                      ->get();

    $totalAdvance = 0;
    $totalClaim = 0;

    foreach ($taDaRecords as $record) {
        if ($record->payment_type === 'Advance') {
            $totalAdvance += $record->remaining_amount;
        } elseif ($record->payment_type === 'Claim') {
            $totalClaim += $record->amount;
        }
    }

    return response()->json([
        'total_advance' => $totalAdvance,
        'total_claim' => $totalClaim,
        'records_count' => $taDaRecords->count()
    ]);
}
// public function getAdvanceSumByMonth($id, Request $request)
// {
//     // Find the "Advance Salary" category dynamically
//     $advanceCategory = ExpenseCategory::where('name', 'Advance Salary')->first();
    
//     if (!$advanceCategory) {
//         return response()->json(['sum' => 0]);
//     }

//     $query = DailyExpense::where('employee_id', $id)
//                          ->where('expense_category_id', $advanceCategory->id);
    
//     // Filter by month if provided
//     if ($request->has('month') && $request->month) {
//         $year = substr($request->month, 0, 4);
//         $monthNum = substr($request->month, 5, 2);
//         $query->whereYear('date', $year)
//               ->whereMonth('date', $monthNum);
//     }
    
//     $advance = $query->sum('amount');
    
//     return response()->json(['sum' => $advance]);
// }
}