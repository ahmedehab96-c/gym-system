<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberNote>
 */
class MemberNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'author' => $this->faker->name(),
            'text' => $this->faker->sentence(12),
        ];
    }
}
