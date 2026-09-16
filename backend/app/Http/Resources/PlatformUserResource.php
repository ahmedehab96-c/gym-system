<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A platform-level Super Admin account (User::isPlatformAdmin()), as
 * seen from the platform's own Platform Users screen. Never exposes
 * password/remember_token, same guarantee as StaffResource.
 *
 * @mixin User
 */
class PlatformUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'role' => 'Platform Admin',
            'lastLoginAt' => $this->last_login_at,
            'createdAt' => $this->created_at,
        ];
    }
}
