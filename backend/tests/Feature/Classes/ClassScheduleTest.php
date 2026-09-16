<?php

namespace Tests\Feature\Classes;

use App\Models\GymClass;
use App\Models\Trainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class ClassScheduleTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_the_daily_schedule(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $today = Carbon::today()->toDateString();
        GymClass::factory()->create(['date' => $today]);
        GymClass::factory()->create(['date' => Carbon::tomorrow()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/schedule/daily?date={$today}");

        $response->assertOk()
            ->assertJsonPath('data.date', $today)
            ->assertJsonCount(1, 'data.classes');
    }

    public function test_it_returns_the_weekly_schedule_grouped_by_day(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $monday = Carbon::today()->startOfWeek(Carbon::MONDAY);
        GymClass::factory()->create(['date' => $monday->toDateString(), 'day' => 'Monday']);
        GymClass::factory()->create(['date' => $monday->copy()->addDays(2)->toDateString(), 'day' => 'Wednesday']);
        GymClass::factory()->create(['date' => $monday->copy()->addDays(10)->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/schedule/weekly?date='.$monday->toDateString());

        $response->assertOk()
            ->assertJsonPath('data.weekStart', $monday->toDateString())
            ->assertJsonCount(7, 'data.days')
            ->assertJsonCount(1, 'data.days.0.classes')
            ->assertJsonCount(1, 'data.days.2.classes')
            ->assertJsonCount(0, 'data.days.1.classes');
    }

    public function test_it_returns_the_monthly_schedule(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $monthStart = Carbon::create(2026, 11, 1);
        GymClass::factory()->create(['date' => $monthStart->copy()->addDays(4)->toDateString()]);
        GymClass::factory()->create(['date' => $monthStart->copy()->addMonth()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/schedule/monthly?month=2026-11');

        $response->assertOk()
            ->assertJsonPath('data.month', '2026-11')
            ->assertJsonCount(30, 'data.days')
            ->assertJsonCount(1, 'data.days.4.classes');
    }

    public function test_it_filters_the_schedule_by_trainer(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();
        $today = Carbon::today()->toDateString();
        GymClass::factory()->create(['date' => $today, 'trainer_id' => $trainer->id]);
        GymClass::factory()->create(['date' => $today]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/schedule/daily?date={$today}&trainer_id={$trainer->id}");

        $response->assertOk()->assertJsonCount(1, 'data.classes');
    }

    public function test_it_filters_the_schedule_by_class_id(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $today = Carbon::today()->toDateString();
        $class = GymClass::factory()->create(['date' => $today]);
        GymClass::factory()->create(['date' => $today]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/schedule/daily?date={$today}&class_id={$class->id}");

        $response->assertOk()->assertJsonCount(1, 'data.classes')->assertJsonPath('data.classes.0.id', $class->id);
    }

    public function test_guest_cannot_access_schedule(): void
    {
        $response = $this->getJson('/api/v1/schedule/daily');

        $response->assertStatus(401);
    }
}
