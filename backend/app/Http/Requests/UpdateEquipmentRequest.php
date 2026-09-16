<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:2048'],
            'category' => ['sometimes', Rule::in(['Cardio', 'Strength', 'Free Weights', 'Functional'])],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'condition' => ['sometimes', Rule::in(['Excellent', 'Good', 'Needs Maintenance', 'Out of Service'])],
            'location' => ['nullable', 'string', 'max:150'],
            'last_maintenance' => ['nullable', 'date'],
            'next_maintenance' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['In Use', 'Under Maintenance', 'Retired'])],
        ];
    }
}
