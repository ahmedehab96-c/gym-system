<?php

namespace Tests\Feature\Communication;

use App\Jobs\SendEmailNotificationJob;
use App\Models\GymClass;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_reminders_are_sent_for_classes_starting_within_the_next_hour(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'email' => 'staff@example.com']);

        $soon = Carbon::now()->addMinutes(30);
        GymClass::factory()->create([
            'tenant_id' => $tenant->id, 'status' => 'Scheduled',
            'date' => $soon->toDateString(), 'start_time' => $soon->format('H:i'),
        ]);
        $farAway = Carbon::now()->addHours(5);
        GymClass::factory()->create([
            'tenant_id' => $tenant->id, 'status' => 'Scheduled',
            'date' => $farAway->toDateString(), 'start_time' => $farAway->format('H:i'),
        ]);

        $this->artisan('classes:send-reminders')->assertSuccessful();

        Queue::assertPushed(SendEmailNotificationJob::class, 1);
        $this->assertDatabaseCount('class_reminder_logs', 1);
    }

    public function test_running_class_reminders_twice_does_not_duplicate(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'email' => 'staff@example.com']);
        $soon = Carbon::now()->addMinutes(20);
        GymClass::factory()->create([
            'tenant_id' => $tenant->id, 'status' => 'Scheduled',
            'date' => $soon->toDateString(), 'start_time' => $soon->format('H:i'),
        ]);

        $this->artisan('classes:send-reminders');
        $this->artisan('classes:send-reminders');

        Queue::assertPushed(SendEmailNotificationJob::class, 1);
        $this->assertDatabaseCount('class_reminder_logs', 1);
    }

    public function test_invoice_reminders_are_sent_exactly_three_days_before_the_due_date(): void
    {
        Queue::fake();
        $tenant = Tenant::default();
        User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Active', 'email' => 'staff@example.com']);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        Invoice::factory()->create([
            'tenant_id' => $tenant->id, 'member_id' => $member->id, 'status' => 'Unpaid',
            'due_date' => Carbon::today()->addDays(3)->toDateString(),
        ]);
        Invoice::factory()->create([
            'tenant_id' => $tenant->id, 'member_id' => $member->id, 'status' => 'Unpaid',
            'due_date' => Carbon::today()->addDays(10)->toDateString(),
        ]);
        Invoice::factory()->create([
            'tenant_id' => $tenant->id, 'member_id' => $member->id, 'status' => 'Paid',
            'due_date' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('invoices:send-payment-reminders')->assertSuccessful();

        Queue::assertPushed(SendEmailNotificationJob::class, 1);
    }
}
