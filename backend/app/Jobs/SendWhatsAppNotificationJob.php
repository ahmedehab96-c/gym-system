<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Services\Communication\Contracts\WhatsAppProviderContract;
use App\Services\NotificationDeliveryService;
use App\Support\WhatsAppTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $userId,
        private readonly string $phone,
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
    ) {}

    public function handle(WhatsAppProviderContract $provider, NotificationDeliveryService $deliveries): void
    {
        $delivery = $deliveries->recordPending($this->tenantId, $this->userId, $this->type, 'whatsapp', $this->phone);

        $provider->sendTemplate($this->phone, WhatsAppTemplate::forType($this->type), [
            'title' => $this->title,
            'body' => $this->message,
        ]);

        $deliveries->markSent($delivery);
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('WhatsApp notification delivery failed.', ['tenant_id' => $this->tenantId, 'type' => $this->type]);

        $delivery = NotificationDelivery::query()
            ->where('tenant_id', $this->tenantId)
            ->where('channel', 'whatsapp')
            ->where('recipient', $this->phone)
            ->where('type', $this->type)
            ->latest('id')
            ->first();

        $delivery?->update(['status' => 'Failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);
    }
}
