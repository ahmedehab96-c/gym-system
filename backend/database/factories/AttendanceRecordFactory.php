<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        $checkedOut = $this->faker->boolean(75);
        $checkInHour = $this->faker->numberBetween(6, 20);
        $checkOutHour = min(22, $checkInHour + $this->faker->numberBetween(1, 2));

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'member_id' => Member::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'check_in' => sprintf('%d:%02d', $checkInHour, $this->faker->randomElement([0, 15, 30, 45])),
            'check_out' => $checkedOut ? sprintf('%d:%02d', $checkOutHour, $this->faker->randomElement([0, 15, 30, 45])) : null,
            'duration' => $checkedOut ? $this->faker->numberBetween(1, 2).'h '.$this->faker->randomElement([15, 30, 45]).'m' : null,
            'method' => $this->faker->randomElement(['QR Code', 'Manual', 'Card']),
        ];
    }
}
