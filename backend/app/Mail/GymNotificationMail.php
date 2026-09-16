<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * One generic, reusable Mailable for every business event (Phase 24
 * §2/§7) — the subject/body text is already generated once, in
 * App\Services\NotificationService, so this class only ever renders it;
 * it never composes per-event content itself (avoids duplicating
 * message copy between here and NotificationService's 16 event
 * methods). Dispatched from inside App\Jobs\SendEmailNotificationJob,
 * which is itself queued, so this Mailable does not need to implement
 * ShouldQueue too.
 */
class GymNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $subjectLine,
        public readonly string $bodyText,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.notification')
            ->with([
                'tenantName' => $this->tenantName,
                'subjectLine' => $this->subjectLine,
                'bodyText' => $this->bodyText,
            ]);
    }
}
