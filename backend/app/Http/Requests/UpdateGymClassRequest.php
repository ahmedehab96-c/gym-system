<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGymClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'trainer_id' => ['sometimes', 'integer', 'exists:trainers,id'],
            'date' => ['nullable', 'date'],
            'day' => ['sometimes', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['Scheduled', 'Full', 'Cancelled', 'Completed'])],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $class = $this->route('class');
            $start = $this->input('start_time', $class?->start_time);
            $end = $this->input('end_time', $class?->end_time);

            if ($start && $end && $start >= $end) {
                $validator->errors()->add('end_time', 'The end time must be after the start time.');
            }
        });
    }
}
