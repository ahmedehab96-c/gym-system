<?php

namespace App\Http\Requests;

use App\Support\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(NotificationType::ALL)],
            'channel' => ['required', Rule::in(['email', 'whatsapp', 'push'])],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
