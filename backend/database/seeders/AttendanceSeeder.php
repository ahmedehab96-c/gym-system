<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::inRandomOrder()->limit(28)->get();

        foreach ($members as $i => $member) {
            $checkedOut = $i % 4 !== 0;

            AttendanceRecord::factory()->create([
                'member_id' => $member->id,
                'date' => Carbon::today()->format('Y-m-d'),
                'check_in' => sprintf('%d:%s', 6 + ($i % 14), $i % 2 === 0 ? '00' : '30'),
                'check_out' => $checkedOut ? sprintf('%d:%s', 8 + ($i % 12), $i % 3 === 0 ? '15' : '45') : null,
                'duration' => $checkedOut ? sprintf('%dh %dm', 1 + ($i % 2), 15 + ($i % 3) * 10) : null,
            ]);

            for ($d = 0; $d < 5; $d++) {
                AttendanceRecord::factory()->create([
                    'member_id' => $member->id,
                    'date' => Carbon::today()->subDays($d + 1)->format('Y-m-d'),
                    'check_in' => sprintf('%d:00', 6 + (($i + $d) % 14)),
                    'check_out' => sprintf('%d:30', 8 + (($i + $d) % 12)),
                    'duration' => sprintf('%dh %dm', 1 + ($d % 2), 20 + ($d % 3) * 10),
                ]);
            }
        }
    }
}
