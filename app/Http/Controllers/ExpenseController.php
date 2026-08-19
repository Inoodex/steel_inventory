<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Models\Sale;
use App\Models\User;
use App\Models\Payment;
use Twilio\Rest\Client;
use App\Models\Employee;
use App\Models\DailyExpense;
use App\Models\Salary;
use Illuminate\Http\Request;
use App\Mail\CreateSalesMail;
use Illuminate\Support\Facades\Mail;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
   

    public function index(Request $request)
    {
        // Start the query on daily_expenses, joining to categories only:
        $query = DailyExpense::leftJoin('expense_categories', 'expense_categories.id', '=', 'daily_expenses.expense_category_id');

        // Date filtering
        $defaultFilter = true;
        if ($request->from && $request->to) {
            $from = date('Y-m-d 00:00:00', strtotime($request->from));
            $to   = date('Y-m-d 23:59:59', strtotime($request->to));
            $query->whereBetween('daily_expenses.created_at', [$from, $to]);
            $defaultFilter = false;
        }

        // Spend method filter
        if ($request->spend_method) {
            $query->where('daily_expenses.spend_method', $request->spend_method);
            $defaultFilter = false;
        }

        // Expense category filter
        if ($request->expense_category_id) {
            $query->where('daily_expenses.expense_category_id', $request->expense_category_id);
            $defaultFilter = false;
        }

        // Remarks search
        if ($request->key) {
            $query->where('daily_expenses.remarks', 'like', '%' . $request->key . '%');
            $defaultFilter = false;
        }

        // Default to current month
        if ($defaultFilter) {
            $startOfMonth = now()->startOfMonth()->startOfDay();
            $endOfMonth   = now()->endOfMonth()->endOfDay();
            $query->whereBetween('daily_expenses.created_at', [$startOfMonth, $endOfMonth]);
        }

        // Select what we need
        $dailyExpense = $query
            ->with('employee')
            ->select(
                'daily_expenses.*',
                'expense_categories.name as category_name'
            )
            ->orderBy('daily_expenses.id', 'desc')
            ->get();

        // Pull only the active categories for the filter dropdown
        $categories = ExpenseCategory::where('status', 1)->orderBy('name')->get();

        // PDF export shortcut
        if ($request->search_for === 'pdf') {
            $html = view('pdf.daily_expense', compact('dailyExpense', 'request', 'categories'))->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'Helvetica',
            ]);
            $mpdf->WriteHTML($html);
            return response($mpdf->Output('daily_expense.pdf', 'I'), 200, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Render index view
        return view('frontend.pages.expense.index', compact('dailyExpense','request','categories'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
            $users = User::leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('users.*', 'roles.name as roleName')
                ->orderBy('users.id', 'desc')
                ->get();

            $employees = Employee::where('status', 'active')->get();

            $categories = ExpenseCategory::all();

            return view('frontend.pages.expense.create', compact('users', 'categories', 'employees'));
    }


    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $attributes = $request->all();

    //     $rules = [
    //         'date' => 'required|date',
    //         'expense_category_id' => 'required|exists:expense_categories,id',
    //         'amount' => 'required|numeric|min:0.01',
    //         'spend_method' => 'required|in:cash,card,bank_transfer',
    //         'remarks' => 'nullable|string|max:1000',
    //     ];

    //     $validation = Validator::make($attributes, $rules);

    //     if ($validation->fails()) {
    //         return redirect()->back()
    //             ->with(['error' => getNotify(4)])
    //             ->withErrors($validation)
    //             ->withInput();
    //     }

    //     $expense = new DailyExpense();
    //     $expense->date = $request->date;
    //     $expense->expense_category_id = $request->expense_category_id;
    //     $expense->amount = $request->amount;
    //     $expense->spend_method = $request->spend_method;
    //     $expense->remarks = $request->remarks;
    //     $expense->save();

    //     // return redirect()->back()->with(['success' => getNotify(1)]);
    //     return redirect()->route('dailyExpenses.index')->with('success', 'Created successfully.');

    // }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'spend_method' => 'required|in:cash,card,bank_transfer',
            'remarks' => 'nullable|string',
            'expense_category_id' => 'required|exists:expense_categories,id',
        ]);

        $expense = DailyExpense::create([
            'user_id' => Auth::id(),
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'expense_category_id' => $request->expense_category_id,
            'amount' => $request->amount,
            'spend_method' => $request->spend_method,
            'remarks' => $request->remarks,
        ]);

        try {
            $this->postExpenseJournal($expense);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Expense #{$expense->id} auto-journal notice: " . $e->getMessage());
        }

        return redirect()->route('dailyExpenses.index')->with('success', 'Created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $expense = DailyExpense::where('id', $id)->first();        
        $users = User::leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.name as roleName')
            ->orderBy('users.id', 'desc')
            ->get();
        $employees = Employee::where('status', 'active')->get();
        $categories = ExpenseCategory::where('status', 1)->orderBy('name')->get();
        return view('frontend.pages.expense.edit', compact('users', 'expense', 'categories', 'employees'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $attributes = $request->all();
        $rules = [
            'date'                => 'required|date',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'spend_method'        => 'required|in:cash,card,bank_transfer',
            'remarks'             => 'nullable|string|max:1000',
        ];

        $validation = Validator::make($attributes, $rules);
        if ($validation->fails()) {
            return redirect()->back()
                ->with(['error' => getNotify(4)])
                ->withErrors($validation)
                ->withInput();
        }

        // Find the existing expense entry
        $expense = DailyExpense::findOrFail($id);

        // Update the expense details
        $expense->date                = $request->date;
        $expense->expense_category_id = $request->expense_category_id;
        $expense->amount              = $request->amount;
        $expense->spend_method        = $request->spend_method;
        $expense->remarks             = $request->remarks;
        $expense->save();

        try {
            $this->postExpenseJournal($expense);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Expense #{$expense->id} update auto-journal notice: " . $e->getMessage());
        }

        return redirect()->route('dailyExpenses.index')->with('success', 'Updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $expense = DailyExpense::findOrFail($id);

        // Clean up linked journal entries
        try {
            $linkedJournals = \App\Models\JournalEntry::where('reference_type', 'expense')
                ->where('reference_id', $expense->id)
                ->get();
            foreach ($linkedJournals as $j) {
                $j->items()->delete();
                $j->delete();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Expense #{$id} delete journal cleanup notice: " . $e->getMessage());
        }

        $expense->delete();
        return redirect()->back()->with(['success' => getNotify(3)]);
    }

    /**
     * Post or update GL journal entry for an expense.
     */
    protected function postExpenseJournal(DailyExpense $expense): void
    {
        if (!function_exists('postJournalEntry')) {
            return;
        }

        // Delete any existing un-reversed journal for this expense to avoid duplicates on update
        $existingJournals = \App\Models\JournalEntry::where('reference_type', 'expense')
            ->where('reference_id', $expense->id)
            ->where('status', '!=', 'reversed')
            ->get();
        foreach ($existingJournals as $ej) {
            $ej->items()->delete();
            $ej->delete();
        }

        $category = ExpenseCategory::find($expense->expense_category_id);
        $categoryName = $category ? strtolower(trim($category->name)) : '';

        // Determine Debit Account (Expense Account)
        if (str_contains($categoryName, 'advance') || str_contains($categoryName, 'loan')) {
            $expenseAcc = \App\Models\ChartOfAccount::where('account_code', '1150')->first();
        } elseif (str_contains($categoryName, 'travel') || str_contains($categoryName, 'ta') || str_contains($categoryName, 'da')) {
            $expenseAcc = \App\Models\ChartOfAccount::where('account_code', '5220')->first();
        } elseif (str_contains($categoryName, 'salary') || str_contains($categoryName, 'wages')) {
            $expenseAcc = \App\Models\ChartOfAccount::where('account_code', '5210')->first();
        } else {
            $expenseAcc = \App\Models\ChartOfAccount::where('account_code', '5230')->first();
        }

        if (!$expenseAcc) {
            $expenseAcc = \App\Models\ChartOfAccount::where('account_type', 'expense')->whereNotNull('parent_id')->first();
        }

        // Determine Credit Account (Payment Source Asset)
        if ($expense->spend_method === 'cash') {
            $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
        } else {
            $cashAcc = \App\Models\ChartOfAccount::where('account_code', 'like', '1120-%')
                ->where('is_active', true)
                ->first() ?? \App\Models\ChartOfAccount::where('account_code', '1120')->first();
        }

        if (!$cashAcc) {
            $cashAcc = \App\Models\ChartOfAccount::where('account_code', '1110')->first();
        }

        if ($expenseAcc && $cashAcc && (float)$expense->amount > 0) {
            $catTitle = $category ? $category->name : 'Expense';
            $employeeName = $expense->employee ? $expense->employee->name : '';
            $desc = "Daily Expense #{$expense->id}: {$catTitle}" . ($employeeName ? " ({$employeeName})" : '') . ($expense->remarks ? " - {$expense->remarks}" : '');

            postJournalEntry([
                'entry_date' => $expense->date ?? date('Y-m-d'),
                'reference_type' => 'expense',
                'reference_id' => $expense->id,
                'description' => $desc,
                'status' => 'approved',
                'created_by' => Auth::id() ?? \App\Models\User::value('id'),
                'items' => [
                    [
                        'account_id' => $expenseAcc->id,
                        'debit' => (float) $expense->amount,
                        'credit' => 0.00,
                        'description' => $desc,
                    ],
                    [
                        'account_id' => $cashAcc->id,
                        'debit' => 0.00,
                        'credit' => (float) $expense->amount,
                        'description' => "Paid via " . ucfirst(str_replace('_', ' ', $expense->spend_method)),
                    ],
                ],
            ]);
        }
    }

    public function getAdvanceSum($employeeId)
{
    $advanceCategory = ExpenseCategory::where('name', 'Advance Salary')->first();

    if (!$advanceCategory) {
        return response()->json(['sum' => 0]);
    }

    $sum = DailyExpense::where('employee_id', $employeeId)
        ->where('expense_category_id', $advanceCategory->id)
        ->sum('amount');

    return response()->json(['sum' => $sum]);
}

}