<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Head Office',
                'address' => 'Davao City',
                'work_start_time' => '08:00:00',
                'work_end_time' => '17:00:00',
            ],
            [
                'name' => 'Abreeza',
                'address' => 'Davao City',
                'work_start_time' => '10:00:00',
                'work_end_time' => '19:00:00',
            ],
            [
                'name' => 'SM Lanang',
                'address' => 'Davao City',
                'work_start_time' => '10:00:00',
                'work_end_time' => '19:00:00',
            ],
            [
                'name' => 'SM Ecoland',
                'address' => 'Davao City',
                'work_start_time' => '10:00:00',
                'work_end_time' => '19:00:00',
            ],
            [
                'name' => 'NCCC',
                'address' => 'Davao City',
                'work_start_time' => '10:00:00',
                'work_end_time' => '19:00:00',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
