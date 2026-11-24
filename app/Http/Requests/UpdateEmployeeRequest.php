<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee') ?? $this->route('id');

        return [
            'employee_id' => 'required|string|max:100|unique:employees,employee_id,' . $employeeId,
            'name'        => 'required|string|max:255',
            'email'       => 'nullable|email|max:255|unique:employees,email,' . $employeeId,
            'phone'       => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:255',
            'join_date'   => 'nullable|date',
            'salary'      => 'nullable|numeric|min:0',
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
            'email.unique'         => 'This email is already used by another employee.',
            'salary.numeric'       => 'Salary must be a number.',
            'image.image'          => 'Profile image must be a valid image file.',
            'image.max'            => 'Profile image must not exceed 2MB.',
        ];
    }
}
