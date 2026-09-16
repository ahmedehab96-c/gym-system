<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'duration_label' => ['sometimes', 'string', 'max:50'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'status' => ['sometimes', Rule::in(['Active', 'Inactive'])],
            'color' => ['nullable', 'string', 'max:20'],
            'popular' => ['sometimes', 'boolean'],
        ];
    }
}
