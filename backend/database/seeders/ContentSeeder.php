<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\GalleryImage;
use App\Models\GymSetting;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        Testimonial::factory()->count(6)->create();
        GalleryImage::factory()->count(12)->create();
        ContactMessage::factory()->count(8)->create();
        GymSetting::factory()->create();
    }
}
