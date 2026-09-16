<?php

namespace Database\Factories;

use App\Models\AppNotification;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
class AppNotificationFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement([
            'Membership Expiring', 'Payment Received', 'New Member', 'Class Reminder', 'Maintenance Due', 'System Notification',
        ]);

        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'type' => $type,
            'title' => $type,
            'message' => $this->faker->sentence(10),
            'read' => $this->faker->boolean(40),
            'user_id' => null,
        ];
    }
}
