<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['Paid', 'Unpaid', 'Overdue', 'Draft'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
