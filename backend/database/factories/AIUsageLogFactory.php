<?php

namespace Database\Factories;

use App\Models\AIUsageLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIUsageLog>
 */
class AIUsageLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::default()->id,
            'user_id' => null,
            'feature' => 'assistant',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'prompt_tokens' => $this->faker->numberBetween(50, 300),
            'completion_tokens' => $this->faker->numberBetween(20, 150),
            'total_tokens' => $this->faker->numberBetween(70, 450),
            'estimated_cost' => $this->faker->randomFloat(6, 0, 0.01),
            'status' => 'success',
        ];
    }
}
