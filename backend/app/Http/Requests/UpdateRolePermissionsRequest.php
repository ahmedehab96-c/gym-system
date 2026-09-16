<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*.module' => ['required', 'string', 'max:100'],
            'permissions.*.canView' => ['required', 'boolean'],
            'permissions.*.canCreate' => ['required', 'boolean'],
            'permissions.*.canEdit' => ['required', 'boolean'],
            'permissions.*.canDelete' => ['required', 'boolean'],
        ];
    }
}
