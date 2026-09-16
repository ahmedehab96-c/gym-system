<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in(['Active', 'Suspended', 'Trial', 'Inactive'])],

            // Optional: bootstraps the gym's first admin account. Omitted
            // entirely, the tenant is created with no user yet.
            'owner_name' => ['nullable', 'string', 'max:255', 'required_with:owner_email'],
            'owner_email' => ['nullable', 'email', 'max:255', 'required_with:owner_name', 'unique:users,email'],
            'owner_password' => ['nullable', 'string', 'min:8', 'required_with:owner_email'],
        ];
    }
}
