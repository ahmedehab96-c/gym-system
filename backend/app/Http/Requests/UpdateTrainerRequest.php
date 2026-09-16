<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $trainer = $this->route('trainer');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('trainers', 'user_id')->ignore($trainer)],
            'name' => ['sometimes', 'string', 'max:255'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'specialty' => ['sometimes', 'string', 'max:255'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'experience' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('trainers', 'email')->ignore($trainer)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in(['Active', 'On Leave', 'Inactive'])],
            'rating' => ['sometimes', 'numeric', 'min:0', 'max:5'],
            'sessions_completed' => ['sometimes', 'integer', 'min:0'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.day' => ['nullable', 'string', 'max:20'],
            'schedule.*.time' => ['nullable', 'string', 'max:50'],
            'schedule.*.activity' => ['nullable', 'string', 'max:100'],
        ];
    }
}
