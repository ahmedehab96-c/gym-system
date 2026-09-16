<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:trainers,user_id'],
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'specialty' => ['required', 'string', 'max:255'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'experience' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:trainers,email'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['Active', 'On Leave', 'Inactive'])],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sessions_completed' => ['nullable', 'integer', 'min:0'],
            'schedule' => ['nullable', 'array'],
            'schedule.*.day' => ['nullable', 'string', 'max:20'],
            'schedule.*.time' => ['nullable', 'string', 'max:50'],
            'schedule.*.activity' => ['nullable', 'string', 'max:100'],
        ];
    }
}
