<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', Rule::in(['Cash', 'Card', 'Bank Transfer', 'Online'])],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['Paid', 'Pending', 'Failed', 'Refunded'])],
        ];
    }
}
