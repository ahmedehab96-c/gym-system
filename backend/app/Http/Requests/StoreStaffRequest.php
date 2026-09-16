<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['Super Admin', 'Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant'])],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
            'position' => ['nullable', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
