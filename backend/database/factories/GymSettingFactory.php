<?php

namespace Database\Factories;

use App\Models\GymSetting;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GymSetting>
 */
class GymSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'name' => 'Premium Gym',
            'phone' => '+20 100 000 0000',
            'email' => 'info@premiumgym.com',
            'address' => '5 Nile Corniche St, Cairo',
            'website' => 'www.premiumgym.com',
            'logo_url' => null,
            'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'open_time' => '06:00',
            'close_time' => '23:00',
            'accent_color' => '#d4a72f',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'language' => 'English',
            'notify_email' => true,
            'notify_push' => true,
            'notify_sms' => false,
        ];
    }
}
