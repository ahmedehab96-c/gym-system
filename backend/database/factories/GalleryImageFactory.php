<?php

namespace Database\Factories;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    public function definition(): array
    {
        $category = $this->faker->randomElement(['Interior', 'Equipment', 'Training', 'Athletes', 'Trainers', 'Classes']);

        return [
            'src' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&q=80',
            'alt' => $category.' at Premium Gym',
            'category' => $category,
        ];
    }
}
