<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'integer', 'exists:members,id'],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'discount' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['Paid', 'Unpaid', 'Overdue', 'Draft'])],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.amount' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
