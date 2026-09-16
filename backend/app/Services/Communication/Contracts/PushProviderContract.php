<?php

namespace App\Services\Communication\Contracts;

use App\Services\Communication\DTO\PushSendResult;

interface PushProviderContract
{
    /**
     * @param  array<string, string>  $data  Extra payload for the client app to act on (e.g. deep-link route)
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): PushSendResult;
}
