<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['sometimes', 'integer', 'exists:equipment,id'],
            'type' => ['sometimes', 'string', 'max:150'],
            'technician' => ['nullable', 'string', 'max:150'],
            'date' => ['sometimes', 'date'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['Upcoming', 'Overdue', 'Completed', 'In Progress'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
