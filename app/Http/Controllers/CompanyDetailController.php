<?php

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use Illuminate\Http\Request;

class CompanyDetailController extends Controller
{
    public function index()
    {
        $companies = CompanyDetail::latest()->get();
        return view('frontend.pages.company-details.index', compact('companies'));
    }

    public function create()
    {
        return redirect()->route('company-details.index');
    }

    public function show(CompanyDetail $companyDetail)
    {
        return redirect()->route('company-details.index');
    }

    public function edit(CompanyDetail $companyDetail)
    {
        return redirect()->route('company-details.index');
    }

    public function store(Request $request)
    {
        // Support either company_name or name
        if ($request->filled('name') && !$request->filled('company_name')) {
            $request->merge(['company_name' => $request->name]);
        }

        $request->validate([
            'company_name'         => 'required|string|max:255',
            'tagline'              => 'nullable|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:255',
            'alternate_phone'      => 'nullable|string|max:255',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:255',
            'state'                => 'nullable|string|max:255',
            'postal_code'          => 'nullable|string|max:255',
            'country'              => 'nullable|string|max:255',
            'tax_number'           => 'nullable|string|max:255',
            'bin_number'           => 'nullable|string|max:255',
            'tin_number'           => 'nullable|string|max:255',
            'trade_license'        => 'nullable|string|max:255',
            'website'              => 'nullable|string|max:255',
            'currency_symbol'      => 'nullable|string|max:10',
            'currency_code'        => 'nullable|string|max:10',
            'terms_and_conditions' => 'nullable|string',
            'invoice_notes'        => 'nullable|string',
            'is_default'           => 'sometimes|boolean',
            'logo'                 => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_path'            => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'signature_image'      => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'signature_path'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $data = $request->only([
            'company_name',
            'tagline',
            'email',
            'phone',
            'alternate_phone',
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'tax_number',
            'bin_number',
            'tin_number',
            'trade_license',
            'website',
            'currency_symbol',
            'currency_code',
            'terms_and_conditions',
            'invoice_notes',
        ]);

        $data['is_default'] = $request->boolean('is_default');

        // Signature upload
        $sigFile = $request->file('signature_path') ?? $request->file('signature_image');
        if ($sigFile) {
            $imageName = time() . '_sig_' . uniqid() . '.' . $sigFile->getClientOriginalExtension();
            $sigFile->move(public_path('uploads/signatures'), $imageName);
            $data['signature_path'] = 'uploads/signatures/' . $imageName;
        }

        // Logo upload
        $logoFile = $request->file('logo') ?? $request->file('logo_path');
        if ($logoFile) {
            $logoName = time() . '_logo_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->move(public_path('uploads/logos'), $logoName);
            $data['logo_path'] = 'uploads/logos/' . $logoName;
        }

        // If setting as default, remove default from others
        if (!empty($data['is_default'])) {
            CompanyDetail::where('is_default', true)->update(['is_default' => false]);
        }

        CompanyDetail::create($data);

        return redirect()->route('company-details.index')
            ->with('success', 'Company details created successfully.');
    }

    public function update(Request $request, CompanyDetail $companyDetail)
    {
        // Support either company_name or name
        if ($request->filled('name') && !$request->filled('company_name')) {
            $request->merge(['company_name' => $request->name]);
        }

        $request->validate([
            'company_name'         => 'required|string|max:255',
            'tagline'              => 'nullable|string|max:255',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:255',
            'alternate_phone'      => 'nullable|string|max:255',
            'address'              => 'nullable|string',
            'city'                 => 'nullable|string|max:255',
            'state'                => 'nullable|string|max:255',
            'postal_code'          => 'nullable|string|max:255',
            'country'              => 'nullable|string|max:255',
            'tax_number'           => 'nullable|string|max:255',
            'bin_number'           => 'nullable|string|max:255',
            'tin_number'           => 'nullable|string|max:255',
            'trade_license'        => 'nullable|string|max:255',
            'website'              => 'nullable|string|max:255',
            'currency_symbol'      => 'nullable|string|max:10',
            'currency_code'        => 'nullable|string|max:10',
            'terms_and_conditions' => 'nullable|string',
            'invoice_notes'        => 'nullable|string',
            'is_default'           => 'sometimes|boolean',
            'logo'                 => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'logo_path'            => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'signature_image'      => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'signature_path'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $data = $request->only([
            'company_name',
            'tagline',
            'email',
            'phone',
            'alternate_phone',
            'address',
            'city',
            'state',
            'postal_code',
            'country',
            'tax_number',
            'bin_number',
            'tin_number',
            'trade_license',
            'website',
            'currency_symbol',
            'currency_code',
            'terms_and_conditions',
            'invoice_notes',
        ]);

        $data['is_default'] = $request->boolean('is_default');

        // Signature upload
        $sigFile = $request->file('signature_path') ?? $request->file('signature_image');
        if ($sigFile) {
            if ($companyDetail->signature_path && file_exists(public_path($companyDetail->signature_path))) {
                @unlink(public_path($companyDetail->signature_path));
            }
            $imageName = time() . '_sig_' . uniqid() . '.' . $sigFile->getClientOriginalExtension();
            $sigFile->move(public_path('uploads/signatures'), $imageName);
            $data['signature_path'] = 'uploads/signatures/' . $imageName;
        }

        // Logo upload
        $logoFile = $request->file('logo') ?? $request->file('logo_path');
        if ($logoFile) {
            if ($companyDetail->logo_path && file_exists(public_path($companyDetail->logo_path))) {
                @unlink(public_path($companyDetail->logo_path));
            }
            $logoName = time() . '_logo_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->move(public_path('uploads/logos'), $logoName);
            $data['logo_path'] = 'uploads/logos/' . $logoName;
        }

        // If setting as default, remove default from others
        if (!empty($data['is_default'])) {
            CompanyDetail::where('is_default', true)->where('id', '!=', $companyDetail->id)->update(['is_default' => false]);
        }

        $companyDetail->update($data);

        return redirect()->route('company-details.index')
            ->with('success', 'Company details updated successfully.');
    }

    public function destroy(CompanyDetail $companyDetail)
    {
        // If deleting default, set another as default
        if ($companyDetail->is_default) {
            $newDefault = CompanyDetail::where('id', '!=', $companyDetail->id)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        if ($companyDetail->signature_path && file_exists(public_path($companyDetail->signature_path))) {
            @unlink(public_path($companyDetail->signature_path));
        }
        if ($companyDetail->logo_path && file_exists(public_path($companyDetail->logo_path))) {
            @unlink(public_path($companyDetail->logo_path));
        }

        $companyDetail->delete();

        return redirect()->route('company-details.index')
            ->with('success', 'Company details deleted successfully.');
    }

    public function setDefault(CompanyDetail $companyDetail)
    {
        CompanyDetail::where('is_default', true)->update(['is_default' => false]);
        $companyDetail->update(['is_default' => true]);

        return redirect()->route('company-details.index')
            ->with('success', 'Default company details updated successfully.');
    }
}