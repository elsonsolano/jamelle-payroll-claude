<?php

namespace Database\Seeders;

use App\Services\AttendanceBadgeService;
use Illuminate\Database\Seeder;

class AttendanceBadgeSeeder extends Seeder
{
    public function run(): void
    {
        app(AttendanceBadgeService::class)->ensureDefaultBadges();
    }
}
