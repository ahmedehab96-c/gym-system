<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', Rule::in(['Rent', 'Equipment', 'Maintenance', 'Salaries', 'Utilities', 'Marketing', 'Other'])],
            'amount' => ['sometimes', 'integer', 'min:1'],
            'date' => ['sometimes', 'date'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
