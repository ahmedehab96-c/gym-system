<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $member = $this->route('member');

        return [
            'member_id' => ['sometimes', 'string', 'max:32', Rule::unique('members', 'member_id')->ignore($member)],
            'name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'gender' => ['sometimes', Rule::in(['Male', 'Female'])],
            'phone' => ['sometimes', 'string', 'max:32'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('members', 'email')->ignore($member)],
            'address' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'join_date' => ['sometimes', 'date'],
            'plan_id' => ['nullable', 'integer', 'exists:membership_plans,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'start_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::in(['Active', 'Inactive', 'Suspended', 'Expired'])],
            'attendance_rate' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'balance_due' => ['sometimes', 'integer', 'min:0'],
            'emergency_contact' => ['nullable', 'string', 'max:32'],
        ];
    }
}
