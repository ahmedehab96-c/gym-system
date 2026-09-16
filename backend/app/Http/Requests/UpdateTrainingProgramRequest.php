<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'string', 'max:2048'],
            'duration' => ['nullable', 'string', 'max:50'],
            'difficulty' => ['sometimes', Rule::in(['Beginner', 'Intermediate', 'Advanced', 'All Levels'])],
            'trainer_id' => ['nullable', 'integer', 'exists:trainers,id'],
            'status' => ['sometimes', Rule::in(['Active', 'Draft', 'Archived'])],
        ];
    }
}
