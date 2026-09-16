<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Staff are User records with a role. Never exposes password/remember_token
 * — only the fields explicitly listed below leave this API.
 *
 * @mixin User
 */
class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'position' => $this->position,
            'hireDate' => $this->hire_date,
            'lastLoginAt' => $this->last_login_at,
            'createdAt' => $this->created_at,
        ];
    }
}
