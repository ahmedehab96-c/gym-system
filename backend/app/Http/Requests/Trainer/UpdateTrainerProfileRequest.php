<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberately a smaller field set than the staff-facing
 * UpdateTrainerRequest — a trainer may edit their own public-facing
 * profile fields, but never status, rating, or sessions_completed,
 * which stay admin-controlled (see Api\TrainerController::update()).
 */
class UpdateTrainerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'specialty' => ['sometimes', 'string', 'max:255'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'experience' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
