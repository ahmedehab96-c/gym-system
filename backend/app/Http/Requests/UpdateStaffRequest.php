<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff)],
            'phone' => ['nullable', 'string', 'max:32'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'role' => ['sometimes', Rule::in(['Super Admin', 'Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant'])],
            'status' => ['sometimes', Rule::in(['Active', 'Inactive'])],
            'position' => ['nullable', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
