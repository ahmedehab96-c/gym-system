<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonalTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:members,id', 'unique:personal_training_sessions,member_id'],
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'goal' => ['required', 'string', 'max:150'],
            'sessions_per_week' => ['nullable', 'integer', 'min:1', 'max:14'],
        ];
    }
}
