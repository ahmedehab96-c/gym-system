<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'yearly_price' => ['required', 'integer', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'limits' => ['required', 'array'],
            'limits.max_members' => ['nullable', 'integer', 'min:0'],
            'limits.max_staff' => ['nullable', 'integer', 'min:0'],
            'limits.max_trainers' => ['nullable', 'integer', 'min:0'],
            'limits.max_classes' => ['nullable', 'integer', 'min:0'],
            'limits.storage_mb' => ['nullable', 'integer', 'min:0'],
            'limits.ai_requests' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
