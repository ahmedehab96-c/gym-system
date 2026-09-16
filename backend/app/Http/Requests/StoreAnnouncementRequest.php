<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'audience' => ['required', Rule::in(['All Members', 'Trainers', 'Staff', 'Specific Plan'])],
            'plan_id' => ['required_if:audience,Specific Plan', 'nullable', 'integer', 'exists:membership_plans,id'],
            'status' => ['nullable', Rule::in(['Published', 'Scheduled', 'Draft'])],
            'publish_date' => ['required', 'date'],
        ];
    }
}
