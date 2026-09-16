<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Deliberately a much smaller field set than the staff-facing
 * UpdateMemberRequest — a member may edit their own contact details, but
 * never plan_id, trainer_id, status, attendance_rate, or balance_due,
 * which only staff can change (see MemberController::update()).
 */
class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'gender' => ['sometimes', Rule::in(['Male', 'Female'])],
            'phone' => ['sometimes', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:32'],
        ];
    }
}
