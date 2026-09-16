<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'audience' => ['sometimes', Rule::in(['All Members', 'Trainers', 'Staff', 'Specific Plan'])],
            'plan_id' => ['required_if:audience,Specific Plan', 'nullable', 'integer', 'exists:membership_plans,id'],
            'status' => ['sometimes', Rule::in(['Published', 'Scheduled', 'Draft'])],
            'publish_date' => ['sometimes', 'date'],
        ];
    }
}
