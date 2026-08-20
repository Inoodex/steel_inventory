<?php

namespace App\Http\Controllers;

use App\Models\BankDetail;
use Illuminate\Http\Request;

class BankDetailController extends Controller
{
    public function index()
    {
        $banks = BankDetail::latest()->get();
        return view('frontend.pages.bank-details.index', compact('banks'));
    }

    public function create()
    {
        return view('frontend.pages.bank-details.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_type' => 'required|string|max:50',
            'routing_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['opening_balance'] = $request->filled('opening_balance') ? (float) $request->opening_balance : 0.00;
        $data['current_balance'] = $data['opening_balance'];
        $data['is_default'] = $request->has('is_default');
        $data['is_active'] = $request->has('is_active');

        // If setting as default, remove default from others
        if ($data['is_default']) {
            BankDetail::where('is_default', true)->update(['is_default' => false]);
        }

        $bank = BankDetail::create($data);
        $bank->resolveChartOfAccount();

        return redirect()->route('bank-details.index')
            ->with('success', 'Bank/MFS account details created successfully.');
    }

    public function edit(BankDetail $bankDetail)
    {
        return view('frontend.pages.bank-details.edit', compact('bankDetail'));
    }

    public function update(Request $request, BankDetail $bankDetail)
    {
        $data = $request->validate([
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_type' => 'required|string|max:50',
            'routing_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $newOpening = $request->filled('opening_balance') ? (float) $request->opening_balance : 0.00;
        $oldOpening = (float) ($bankDetail->opening_balance ?? 0.00);

        // If current balance equals old opening balance, update current balance as well
        if ((float)$bankDetail->current_balance === $oldOpening) {
            $data['current_balance'] = $newOpening;
        }

        $data['opening_balance'] = $newOpening;
        $data['is_default'] = $request->has('is_default');
        $data['is_active'] = $request->has('is_active');

        // If setting as default, remove default from others
        if ($data['is_default']) {
            BankDetail::where('is_default', true)->where('id', '!=', $bankDetail->id)->update(['is_default' => false]);
        }

        $bankDetail->update($data);
        $bankDetail->resolveChartOfAccount();

        return redirect()->route('bank-details.index')
            ->with('success', 'Bank details updated successfully.');
    }

    public function destroy(BankDetail $bankDetail)
    {
        // If deleting default, set another as default
        if ($bankDetail->is_default) {
            $newDefault = BankDetail::where('id', '!=', $bankDetail->id)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        $bankDetail->delete();

        return redirect()->route('bank-details.index')
            ->with('success', 'Bank details deleted successfully.');
    }

    public function setDefault(BankDetail $bankDetail)
    {
        BankDetail::where('is_default', true)->update(['is_default' => false]);
        $bankDetail->update(['is_default' => true]);

        return redirect()->route('bank-details.index')
            ->with('success', 'Default bank details updated successfully.');
    }
}