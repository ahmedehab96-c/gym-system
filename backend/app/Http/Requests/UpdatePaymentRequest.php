<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'integer', 'exists:members,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['sometimes', 'integer', 'min:1'],
            'method' => ['sometimes', Rule::in(['Cash', 'Card', 'Bank Transfer', 'Online'])],
            'date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in(['Paid', 'Pending', 'Failed', 'Refunded'])],
        ];
    }
}
