<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            AdminUserSeeder::class,
            AttendanceBadgeSeeder::class,
            PayrollCutoffSeeder::class,
            EmployeeSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
