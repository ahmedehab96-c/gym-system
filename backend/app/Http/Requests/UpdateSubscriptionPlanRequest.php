<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('subscription_plan');

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($plan)],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['sometimes', 'integer', 'min:0'],
            'yearly_price' => ['sometimes', 'integer', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'limits' => ['sometimes', 'array'],
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
