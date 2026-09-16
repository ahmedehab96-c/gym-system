<?php

namespace App\Http\Requests;

use App\Services\AI\ReportAssistantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SummarizeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(ReportAssistantService::REPORT_TYPES)],
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
