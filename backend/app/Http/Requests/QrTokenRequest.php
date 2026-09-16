<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by both QR check-in and check-out (Phase 28 §5) — both accept
 * nothing but the raw scanned token; every other validation (member
 * exists, tenant matches, membership is active, no duplicate check-in)
 * happens in the controller against real data, never trusted from the
 * client (§7 "do not trust frontend validation").
 */
class QrTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'min:32', 'max:128'],
        ];
    }
}
