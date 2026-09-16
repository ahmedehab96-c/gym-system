<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'string', 'max:32', 'unique:members,member_id'],
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'address' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'join_date' => ['nullable', 'date'],
            'plan_id' => ['nullable', 'integer', 'exists:membership_plans,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'start_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive', 'Suspended', 'Expired'])],
            'attendance_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'balance_due' => ['nullable', 'integer', 'min:0'],
            'emergency_contact' => ['nullable', 'string', 'max:32'],
        ];
    }
}
