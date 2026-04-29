<?php

namespace Database\Seeders;

use App\Models\AttendanceScore;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoLeaderboardSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['employee_code' => 'LL-2024-0008', 'first_name' => 'Via', 'last_name' => 'Omaque', 'points' => 680],
            ['employee_code' => 'LL-2024-0009', 'first_name' => 'Kissie', 'last_name' => 'Argoncillo', 'points' => 640],
            ['employee_code' => 'LL-2025-0015', 'first_name' => 'Aime', 'last_name' => 'Ajero', 'points' => 605],
            ['employee_code' => 'LL-2024-0007', 'first_name' => 'Jocelyn', 'last_name' => 'Sinco', 'points' => 580],
            ['employee_code' => 'LL-2026-0029', 'first_name' => 'John Michael', 'last_name' => 'Colina', 'points' => 540],
            ['employee_code' => 'LL-2025-0016', 'first_name' => 'Trisha Kaye', 'last_name' => 'Pascua', 'points' => 510],
            ['employee_code' => 'LL-2026-0030', 'first_name' => 'Jhannie', 'last_name' => 'Suazo', 'points' => 475],
            ['employee_code' => 'LL-2025-0018', 'first_name' => 'Janet', 'last_name' => 'Perez', 'points' => 430],
            ['employee_code' => 'LL-2026-0028', 'first_name' => 'Angelica', 'last_name' => 'Culinar', 'points' => 395],
            ['employee_code' => 'LL-2026-0034', 'first_name' => 'Joshua', 'last_name' => 'Casia', 'points' => 360],
            ['employee_code' => 'LL-2025-0021', 'first_name' => 'Joshua', 'last_name' => 'De Asis', 'points' => 320],
            ['employee_code' => 'LL-2026-0036', 'first_name' => 'Ashlee Jane', 'last_name' => 'Bughao', 'points' => 280],
        ];

        foreach ($entries as $entry) {
            $employee = $this->ensureEmployee($entry);

            $this->ensureStaffAccount($employee);
            $this->ensureScore($employee, $entry['points']);
        }

        $this->command?->info('Demo leaderboard staff and scores seeded successfully.');
    }

    private function ensureEmployee(array $entry): Employee
    {
        $branch = Branch::firstOrCreate(
            ['name' => 'Main'],
            [
                'address' => 'Demo',
                'work_start_time' => '09:00:00',
                'work_end_time' => '18:00:00',
            ]
        );

        return Employee::firstOrCreate(
            ['employee_code' => $entry['employee_code']],
            [
                'branch_id' => $branch->id,
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name'],
                'position' => 'Demo Staff',
                'salary_type' => 'daily',
                'rate' => 500,
                'hired_date' => '2026-01-01',
                'active' => true,
            ]
        )->load('branch');
    }

    private function ensureStaffAccount(Employee $employee): void
    {
        User::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'name' => $employee->full_name,
                'email' => Str::lower($employee->employee_code).'@staff.test',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'signature' => 'data:image/png;base64,demo',
                'must_change_password' => false,
            ]
        );
    }

    private function ensureScore(Employee $employee, int $points): void
    {
        $cutoff = PayrollCutoff::where('branch_id', $employee->branch_id)
            ->where('status', 'finalized')
            ->orderByDesc('end_date')
            ->first();

        if (! $cutoff) {
            $cutoff = PayrollCutoff::firstOrCreate(
                [
                    'branch_id' => $employee->branch_id,
                    'start_date' => '2026-04-01',
                    'end_date' => '2026-04-15',
                ],
                [
                    'name' => 'Demo Leaderboard Cutoff',
                    'status' => 'finalized',
                    'finalized_at' => now(),
                ]
            );
        }

        AttendanceScore::updateOrCreate(
            [
                'payroll_cutoff_id' => $cutoff->id,
                'employee_id' => $employee->id,
            ],
            [
                'total_points' => $points,
                'on_time_days' => intdiv($points, 10),
                'same_day_complete_days' => intdiv($points % 10, 5),
                'complete_dtr_days' => intdiv($points, 10),
                'no_absent_days' => intdiv($points, 10),
                'proper_time_out_days' => intdiv($points, 10),
                'late_days' => 0,
                'late_minutes' => 0,
                'approved_ot_days' => 0,
                'finalized_at' => $cutoff->finalized_at ?? now(),
            ]
        );
    }
}
