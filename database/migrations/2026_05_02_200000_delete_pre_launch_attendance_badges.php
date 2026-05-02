<?php

use App\Services\GamificationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employee_attendance_badges')
            ->join('payroll_cutoffs', 'payroll_cutoffs.id', '=', 'employee_attendance_badges.payroll_cutoff_id')
            ->whereDate('payroll_cutoffs.end_date', '<', GamificationService::GAMIFICATION_LAUNCH_DATE)
            ->delete();
    }

    public function down(): void
    {
        // Badge awards are derived from attendance data and can be regenerated.
    }
};
