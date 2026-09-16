<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonalTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sessionId = $this->route('personal_training');

        return [
            'member_id' => [
                'sometimes', 'required', 'integer', 'exists:members,id',
                Rule::unique('personal_training_sessions', 'member_id')->ignore($sessionId),
            ],
            'trainer_id' => ['sometimes', 'required', 'integer', 'exists:trainers,id'],
            'goal' => ['sometimes', 'required', 'string', 'max:150'],
            'sessions_per_week' => ['nullable', 'integer', 'min:1', 'max:14'],
        ];
    }
}
