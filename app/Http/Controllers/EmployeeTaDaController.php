<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaDa;
use Illuminate\Support\Facades\Auth;

class EmployeeTaDaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found for this account.');
        }

        $tadas = TaDa::where('employee_id', $employee->id)->latest()->get();

        return view('frontend.pages.employees.ta_da.index', compact('tadas'));
    }

    public function edit($id)
    {
        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found for this account.');
        }

        $tadas = TaDa::where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        return view('frontend.pages.employees.ta_da.edit', compact('tadas'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found for this account.');
        }

        // Scope to authenticated employee unless user has Super Admin role
        $tadaQuery = TaDa::where('id', $id);
        if (!$user->hasRole('Super Admin')) {
            $tadaQuery->where('employee_id', $employee->id);
        }
        $tadas = $tadaQuery->firstOrFail();

        $request->validate([
            'used_amount' => 'required|numeric|min:0|max:' . $tadas->amount,
        ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('ta_das', 'used_amount')) {
            $tadas->used_amount = (float) $request->used_amount;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('ta_das', 'remaining_amount')) {
            $tadas->remaining_amount = max(0, (float) $tadas->amount - (float) $request->used_amount);
        }
        $tadas->save();

        return redirect()->route('employee.tada.index')->with('success', 'Amount submitted successfully.');
    }

    public function create()
    {
        return view('frontend.pages.employees.ta_da.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
            'type'         => 'required|in:TA,DA',
            'payment_type' => 'required|in:Advance,Claim',
            'purpose'      => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Employee profile not found for this account.');
        }

        $data = [
            'employee_id' => $employee->id,
            'date'        => $request->date,
            'amount'      => $request->amount,
            'purpose'     => $request->purpose,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('ta_das', 'user_id')) {
            $data['user_id'] = $user->id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('ta_das', 'type')) {
            $data['type'] = $request->type;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('ta_das', 'payment_type')) {
            $data['payment_type'] = $request->payment_type;
        }

        TaDa::create($data);

        return redirect()->route('employee.tada.index')->with('success', 'TA/DA request submitted.');
    }
}