<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Whole currency units, same as PaymentTransaction::amount. Omitted -> full refund.
            'amount' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
