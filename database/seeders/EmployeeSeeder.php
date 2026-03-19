<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Map branch names from the Excel file to DB branch names
        $branchMap = [
            'Ayala Abreeza' => 'Abreeza',
        ];

        $branches = Branch::all()->keyBy('name');

        foreach ($this->employees() as $data) {
            $branchName = $branchMap[$data['branch']] ?? $data['branch'];
            $branch = $branches->get($branchName);

            if (! $branch) {
                $this->command->warn("Branch not found: [{$data['branch']}] for {$data['first_name']} {$data['last_name']} — skipped.");
                continue;
            }

            Employee::firstOrCreate(
                ['employee_code' => $data['employee_code']],
                [
                    'branch_id'   => $branch->id,
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'position'    => $data['position'],
                    'salary_type' => $data['salary_type'],
                    'rate'        => $data['rate'],
                    'hired_date'  => $data['hired_date'],
                    'active'      => true,
                ]
            );
        }

        $this->command->info('Employees seeded successfully.');
    }

    private function employees(): array
    {
        // salary_type:
        //   'daily'   — employees with a known daily rate
        //   'monthly' — management staff with no rate in the file (rate set to 0, configure manually)
        return [
            // ── HEAD OFFICE ──────────────────────────────────────────────────────────
            [
                'employee_code' => 'LL-2023-0001',
                'first_name'    => 'Elson James',
                'last_name'     => 'Solano',
                'position'      => 'Operations Head',
                'salary_type'   => 'monthly',
                'rate'          => 0,
                'hired_date'    => '2023-10-15',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2023-0002',
                'first_name'    => 'Jezel Rose',
                'last_name'     => 'Solano',
                'position'      => 'Operations Manager',
                'salary_type'   => 'monthly',
                'rate'          => 0,
                'hired_date'    => '2023-10-15',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2023-0003',
                'first_name'    => 'Ervin',
                'last_name'     => 'Soberano',
                'position'      => 'Commissary Manager',
                'salary_type'   => 'daily',
                'rate'          => 680.00, // basic 595 + allowance 85
                'hired_date'    => '2023-01-01',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2023-0004',
                'first_name'    => 'Menirose',
                'last_name'     => 'Cartil',
                'position'      => 'HR Assistant',
                'salary_type'   => 'monthly',
                'rate'          => 0,
                'hired_date'    => '2024-08-16',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2025-0005',
                'first_name'    => 'Ellaine Jame',
                'last_name'     => 'Berza',
                'position'      => 'HR Manager',
                'salary_type'   => 'monthly',
                'rate'          => 0,
                'hired_date'    => '2025-07-01',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2023-0006',
                'first_name'    => 'Mona Liza',
                'last_name'     => 'Leones',
                'position'      => 'Area Officer',
                'salary_type'   => 'daily',
                'rate'          => 618.00,
                'hired_date'    => '2023-09-20',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2024-0011',
                'first_name'    => 'Rachelle Lyn',
                'last_name'     => 'Alisna',
                'position'      => 'Area Manager',
                'salary_type'   => 'monthly',
                'rate'          => 0,
                'hired_date'    => '2024-07-17',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2024-0013',
                'first_name'    => 'Val',
                'last_name'     => 'Villafuerte',
                'position'      => 'Liaison Driver',
                'salary_type'   => 'daily',
                'rate'          => 548.00,
                'hired_date'    => '2024-08-19',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2026-0032',
                'first_name'    => 'Flordelyn',
                'last_name'     => 'Lipardo',
                'position'      => 'Accounting Staff',
                'salary_type'   => 'monthly',
                'rate'          => 0,
                'hired_date'    => '2026-02-16',
                'branch'        => 'Head Office',
            ],
            [
                'employee_code' => 'LL-2026-0038',
                'first_name'    => 'John Carlo',
                'last_name'     => 'Cawayan',
                'position'      => 'Liaison Driver',
                'salary_type'   => 'daily',
                'rate'          => 548.00,
                'hired_date'    => '2026-03-03',
                'branch'        => 'Head Office',
            ],

            // ── SM LANANG ─────────────────────────────────────────────────────────────
            [
                'employee_code' => 'LL-2024-0007',
                'first_name'    => 'Jocelyn',
                'last_name'     => 'Sinco',
                'position'      => 'Officer-in-Charge',
                'salary_type'   => 'daily',
                'rate'          => 538.00,
                'hired_date'    => '2024-03-25',
                'branch'        => 'SM Lanang',
            ],
            [
                'employee_code' => 'LL-2024-0010',
                'first_name'    => 'Jomar',
                'last_name'     => 'Razonable',
                'position'      => 'Officer-in-Charge',
                'salary_type'   => 'daily',
                'rate'          => 538.00,
                'hired_date'    => '2024-07-16',
                'branch'        => 'SM Lanang',
            ],
            [
                'employee_code' => 'LL-2025-0016',
                'first_name'    => 'Trisha Kaye',
                'last_name'     => 'Pascua',
                'position'      => 'Officer-in-Charge',
                'salary_type'   => 'daily',
                'rate'          => 538.00,
                'hired_date'    => '2025-05-05',
                'branch'        => 'SM Lanang',
            ],
            [
                'employee_code' => 'LL-2025-0017',
                'first_name'    => 'Norenz John',
                'last_name'     => 'Taylaran',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-05-20',
                'branch'        => 'SM Lanang',
            ],
            [
                'employee_code' => 'LL-2025-0024',
                'first_name'    => 'Vanessa',
                'last_name'     => 'Omaque',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-12-17',
                'branch'        => 'SM Lanang',
            ],
            [
                'employee_code' => 'LL-2026-0025',
                'first_name'    => 'Mariah Carmella',
                'last_name'     => 'Mosqueda',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-01-09',
                'branch'        => 'SM Lanang',
            ],
            [
                'employee_code' => 'LL-2026-0028',
                'first_name'    => 'Angelica',
                'last_name'     => 'Culinar',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-01-13',
                'branch'        => 'SM Lanang',
            ],

            // ── AYALA ABREEZA ─────────────────────────────────────────────────────────
            [
                'employee_code' => 'LL-2024-0008',
                'first_name'    => 'Via',
                'last_name'     => 'Omaque',
                'position'      => 'Officer-in-Charge',
                'salary_type'   => 'daily',
                'rate'          => 538.00,
                'hired_date'    => '2024-07-01',
                'branch'        => 'Ayala Abreeza',
            ],
            [
                'employee_code' => 'LL-2025-0015',
                'first_name'    => 'Aime',
                'last_name'     => 'Ajero',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-04-05',
                'branch'        => 'Ayala Abreeza',
            ],
            [
                'employee_code' => 'LL-2025-0021',
                'first_name'    => 'Joshua',
                'last_name'     => 'De Asis',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-10-22',
                'branch'        => 'Ayala Abreeza',
            ],
            [
                'employee_code' => 'LL-2025-0022',
                'first_name'    => 'Anajean',
                'last_name'     => 'Jumarito',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-11-24',
                'branch'        => 'Ayala Abreeza',
            ],
            [
                'employee_code' => 'LL-2025-0023',
                'first_name'    => 'Elizabeth',
                'last_name'     => 'Narguada',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-11-24',
                'branch'        => 'Ayala Abreeza',
            ],
            [
                'employee_code' => 'LL-2026-0029',
                'first_name'    => 'John Michael',
                'last_name'     => 'Colina',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-01-23',
                'branch'        => 'Ayala Abreeza',
            ],

            // ── SM ECOLAND ────────────────────────────────────────────────────────────
            [
                'employee_code' => 'LL-2024-0009',
                'first_name'    => 'Kissie',
                'last_name'     => 'Argoncillo',
                'position'      => 'Officer-in-Charge',
                'salary_type'   => 'daily',
                'rate'          => 538.00,
                'hired_date'    => '2024-07-01',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2024-0012',
                'first_name'    => 'Edward',
                'last_name'     => 'Canada',
                'position'      => 'Supervisor',
                'salary_type'   => 'daily',
                'rate'          => 568.00,
                'hired_date'    => '2024-07-29',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2026-0026',
                'first_name'    => 'Archie',
                'last_name'     => 'Pimentel',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-01-13',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2026-0027',
                'first_name'    => 'Peter Saimon',
                'last_name'     => 'Ignacio',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-01-13',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2026-0030',
                'first_name'    => 'Jhannie',
                'last_name'     => 'Suazo',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-02-04',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2026-0033',
                'first_name'    => 'Benjie',
                'last_name'     => 'Domipal',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-02-25',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2026-0036',
                'first_name'    => 'Ashlee Jane',
                'last_name'     => 'Bughao',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-03-04',
                'branch'        => 'SM Ecoland',
            ],
            [
                'employee_code' => 'LL-2026-0037',
                'first_name'    => 'Nelie',
                'last_name'     => 'Cristobal',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-03-04',
                'branch'        => 'SM Ecoland',
            ],

            // ── NCCC ──────────────────────────────────────────────────────────────────
            [
                'employee_code' => 'LL-2024-0014',
                'first_name'    => 'Rhadaen',
                'last_name'     => 'Tibulan',
                'position'      => 'Officer-in-Charge',
                'salary_type'   => 'daily',
                'rate'          => 538.00,
                'hired_date'    => '2024-12-04',
                'branch'        => 'NCCC',
            ],
            [
                'employee_code' => 'LL-2025-0018',
                'first_name'    => 'Janet',
                'last_name'     => 'Perez',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-06-19',
                'branch'        => 'NCCC',
            ],
            [
                'employee_code' => 'LL-2025-0019',
                'first_name'    => 'Christian Dave',
                'last_name'     => 'Orcajo',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2025-06-19',
                'branch'        => 'NCCC',
            ],
            [
                'employee_code' => 'LL-2026-0034',
                'first_name'    => 'Joshua',
                'last_name'     => 'Casia',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-03-04',
                'branch'        => 'NCCC',
            ],
            [
                'employee_code' => 'LL-2026-0035',
                'first_name'    => 'Raven Keith',
                'last_name'     => 'Ingutan',
                'position'      => 'Store Service Crew',
                'salary_type'   => 'daily',
                'rate'          => 518.00,
                'hired_date'    => '2026-03-04',
                'branch'        => 'NCCC',
            ],
        ];
    }
}
