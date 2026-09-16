<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Services\Communication\Contracts\PushProviderContract;
use App\Services\NotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $userId,
        private readonly string $deviceToken,
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
    ) {}

    public function handle(PushProviderContract $provider, NotificationDeliveryService $deliveries): void
    {
        $delivery = $deliveries->recordPending($this->tenantId, $this->userId, $this->type, 'push', $this->deviceToken);

        $provider->send($this->deviceToken, $this->title, $this->message, ['type' => $this->type]);

        $deliveries->markSent($delivery);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Push notification delivery failed.', ['tenant_id' => $this->tenantId, 'type' => $this->type]);

        $delivery = NotificationDelivery::query()
            ->where('tenant_id', $this->tenantId)
            ->where('channel', 'push')
            ->where('recipient', $this->deviceToken)
            ->where('type', $this->type)
            ->latest('id')
            ->first();

        $delivery?->update(['status' => 'Failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);
    }
}
