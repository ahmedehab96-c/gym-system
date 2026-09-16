<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'type' => ['required', 'string', 'max:150'],
            'technician' => ['nullable', 'string', 'max:150'],
            'date' => ['required', 'date'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['Upcoming', 'Overdue', 'Completed', 'In Progress'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
