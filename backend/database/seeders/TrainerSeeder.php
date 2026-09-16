<?php

namespace Database\Seeders;

use App\Models\Trainer;
use Illuminate\Database\Seeder;

class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = [
            ['name' => 'Karim Adel', 'photo' => 12, 'specialty' => 'Strength & Conditioning', 'specialties' => ['Strength', 'Powerlifting', 'Bodybuilding'], 'experience' => '8 years', 'phone' => '+20 100 111 2233', 'email' => 'karim.adel@premiumgym.com', 'bio' => 'Karim specializes in strength programming and helps members build sustainable powerlifting foundations.', 'status' => 'Active', 'rating' => 4.9, 'sessions_completed' => 1240, 'schedule' => [['day' => 'Mon', 'time' => '07:00 - 09:00', 'activity' => 'Strength Coaching'], ['day' => 'Wed', 'time' => '17:00 - 19:00', 'activity' => 'Powerlifting Class'], ['day' => 'Fri', 'time' => '07:00 - 09:00', 'activity' => 'Strength Coaching']]],
            ['name' => 'Nourhan Sami', 'photo' => 32, 'specialty' => 'Yoga & Mobility', 'specialties' => ['Yoga', 'Mobility', 'Recovery'], 'experience' => '6 years', 'phone' => '+20 101 222 3344', 'email' => 'nourhan.sami@premiumgym.com', 'bio' => 'Nourhan focuses on flexibility, breathwork and recovery protocols for high-performing athletes.', 'status' => 'Active', 'rating' => 4.8, 'sessions_completed' => 980, 'schedule' => [['day' => 'Tue', 'time' => '08:00 - 09:00', 'activity' => 'Yoga Flow'], ['day' => 'Thu', 'time' => '08:00 - 09:00', 'activity' => 'Mobility Class'], ['day' => 'Sat', 'time' => '10:00 - 11:00', 'activity' => 'Recovery Session']]],
            ['name' => 'Hesham Fathy', 'photo' => 51, 'specialty' => 'HIIT & Fat Loss', 'specialties' => ['HIIT', 'Fat Loss', 'Functional'], 'experience' => '5 years', 'phone' => '+20 102 333 4455', 'email' => 'hesham.fathy@premiumgym.com', 'bio' => 'Hesham runs high-intensity programs engineered for rapid, sustainable fat loss.', 'status' => 'Active', 'rating' => 4.7, 'sessions_completed' => 1510, 'schedule' => [['day' => 'Mon', 'time' => '18:00 - 19:00', 'activity' => 'HIIT Circuit'], ['day' => 'Wed', 'time' => '18:00 - 19:00', 'activity' => 'HIIT Circuit'], ['day' => 'Fri', 'time' => '18:00 - 19:00', 'activity' => 'Fat Loss Bootcamp']]],
            ['name' => 'Rana Tarek', 'photo' => 45, 'specialty' => 'CrossFit', 'specialties' => ['CrossFit', 'Olympic Lifting'], 'experience' => '7 years', 'phone' => '+20 103 444 5566', 'email' => 'rana.tarek@premiumgym.com', 'bio' => 'Rana is a certified CrossFit L2 coach with a focus on Olympic lifting technique.', 'status' => 'On Leave', 'rating' => 4.9, 'sessions_completed' => 870, 'schedule' => [['day' => 'Tue', 'time' => '06:00 - 07:00', 'activity' => 'CrossFit WOD'], ['day' => 'Thu', 'time' => '06:00 - 07:00', 'activity' => 'CrossFit WOD']]],
            ['name' => 'Omar Nabil', 'photo' => 14, 'specialty' => 'Boxing & Conditioning', 'specialties' => ['Boxing', 'Conditioning'], 'experience' => '9 years', 'phone' => '+20 104 555 6677', 'email' => 'omar.nabil@premiumgym.com', 'bio' => 'Former amateur boxer, Omar trains members in technique, footwork and conditioning.', 'status' => 'Active', 'rating' => 4.6, 'sessions_completed' => 1120, 'schedule' => [['day' => 'Mon', 'time' => '19:00 - 20:00', 'activity' => 'Boxing Class'], ['day' => 'Sat', 'time' => '11:00 - 12:00', 'activity' => 'Boxing Class']]],
            ['name' => 'Dina Aziz', 'photo' => 47, 'specialty' => 'Pilates & Core', 'specialties' => ['Pilates', 'Core Training'], 'experience' => '4 years', 'phone' => '+20 105 666 7788', 'email' => 'dina.aziz@premiumgym.com', 'bio' => 'Dina designs low-impact, high-results core and posture programs.', 'status' => 'Active', 'rating' => 4.8, 'sessions_completed' => 640, 'schedule' => [['day' => 'Wed', 'time' => '09:00 - 10:00', 'activity' => 'Pilates'], ['day' => 'Sun', 'time' => '09:00 - 10:00', 'activity' => 'Core Class']]],
        ];

        foreach ($trainers as $t) {
            Trainer::factory()->create(array_merge($t, [
                'photo' => "https://i.pravatar.cc/200?img={$t['photo']}",
            ]));
        }
    }
}
