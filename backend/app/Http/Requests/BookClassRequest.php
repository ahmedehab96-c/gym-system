<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id'],
        ];
    }
}
