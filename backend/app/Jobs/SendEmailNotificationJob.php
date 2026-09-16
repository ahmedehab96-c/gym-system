<?php

namespace App\Jobs;

use App\Mail\GymNotificationMail;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use App\Services\NotificationDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $userId,
        private readonly string $email,
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
    ) {}

    public function handle(NotificationDeliveryService $deliveries): void
    {
        $delivery = $deliveries->recordPending($this->tenantId, $this->userId, $this->type, 'email', $this->email);

        $tenantName = Tenant::find($this->tenantId)?->name ?? 'Your Gym';

        Mail::to($this->email)->send(new GymNotificationMail($tenantName, $this->title, $this->message));

        $deliveries->markSent($delivery);
    }

    public function failed(Throwable $exception): void
    {
        // Never log the exception's raw message unfiltered elsewhere — here
        // it's intentionally captured for operator visibility, and the
        // delivery row is the tenant-facing "failed notifications" record.
        Log::warning('Email notification delivery failed.', ['tenant_id' => $this->tenantId, 'type' => $this->type]);

        $delivery = NotificationDelivery::query()
            ->where('tenant_id', $this->tenantId)
            ->where('channel', 'email')
            ->where('recipient', $this->email)
            ->where('type', $this->type)
            ->latest('id')
            ->first();

        $delivery?->update(['status' => 'Failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);
    }
}
