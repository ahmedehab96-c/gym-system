<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_label' => ['required', 'string', 'max:50'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
            'color' => ['nullable', 'string', 'max:20'],
            'popular' => ['nullable', 'boolean'],
        ];
    }
}
