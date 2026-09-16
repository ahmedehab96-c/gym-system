<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
            'start_date' => ['nullable', 'date'],
            'price' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
