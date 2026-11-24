<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|string|max:100|unique:employees,employee_id',
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255|unique:employees,email',
            'phone'       => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:255',
            'join_date'   => 'nullable|date',
            'salary'      => 'required|numeric|min:0',
            'status'      => 'required|in:active,inactive',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee ID is required.',
            'employee_id.unique'   => 'This Employee ID is already registered.',
            'name.required'        => 'Employee name is required.',
            'email.email'          => 'Please enter a valid email address.',
            'email.unique'         => 'This email is already in use.',
            'salary.required'      => 'Salary amount is required.',
            'salary.numeric'       => 'Salary must be a number.',
            'image.image'          => 'Profile image must be a valid image file.',
            'image.max'            => 'Profile image must not exceed 2MB.',
        ];
    }
}
