<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $year = now()->year;

        // Last Monday of August
        $lastMondayAugust = Carbon::create($year, 8, 31)->startOfDay();
        while ($lastMondayAugust->dayOfWeek !== Carbon::MONDAY) {
            $lastMondayAugust->subDay();
        }

        $holidays = [
            ['date' => "{$year}-01-01", 'name' => "New Year's Day"],
            ['date' => "{$year}-04-09", 'name' => 'Araw ng Kagitingan'],
            ['date' => "{$year}-05-01", 'name' => 'Labor Day'],
            ['date' => "{$year}-06-12", 'name' => 'Independence Day'],
            ['date' => $lastMondayAugust->format('Y-m-d'), 'name' => 'National Heroes Day'],
            ['date' => "{$year}-11-30", 'name' => 'Bonifacio Day'],
            ['date' => "{$year}-12-25", 'name' => 'Christmas Day'],
            ['date' => "{$year}-12-30", 'name' => 'Rizal Day'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                ['name' => $holiday['name'], 'type' => 'regular']
            );
        }
    }
}
