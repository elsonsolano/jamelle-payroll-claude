<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\PayrollCutoff;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PayrollCutoffSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Run BranchSeeder first.');
            return;
        }

        $cutoffs = $this->generateCutoffs(6);

        foreach ($branches as $branch) {
            foreach ($cutoffs as $cutoff) {
                PayrollCutoff::firstOrCreate(
                    [
                        'branch_id'  => $branch->id,
                        'start_date' => $cutoff['start_date'],
                        'end_date'   => $cutoff['end_date'],
                    ],
                    [
                        'name'   => $cutoff['name'],
                        'status' => $cutoff['status'],
                    ]
                );
            }
        }

        $this->command->info("Seeded {$cutoffs->count()} cutoffs for {$branches->count()} branch(es).");
    }

    /**
     * Generate semi-monthly cutoffs following the company pattern:
     *   - Payday 15th  → start: last day of previous month, end: 13th of current month
     *   - Payday 30/31 → start: 14th of current month,     end: 29th of current month
     *
     * Status is 'draft' for the current active period, 'finalized' for all past periods.
     */
    private function generateCutoffs(int $count): \Illuminate\Support\Collection
    {
        $today    = Carbon::today();
        $cutoffs  = collect();
        $month    = $today->copy()->startOfMonth();
        $generated = 0;

        while ($generated < $count) {
            $year = $month->year;
            $m    = $month->month;

            // Second cutoff (payday ~30th/31st): 14th → 29th
            $secondStart = Carbon::createFromDate($year, $m, 14);
            $secondEnd   = Carbon::createFromDate($year, $m, min(29, $month->copy()->endOfMonth()->day));

            // First cutoff (payday 15th): last day of previous month → 13th
            $firstStart = $month->copy()->subMonthNoOverflow()->endOfMonth();
            $firstEnd   = Carbon::createFromDate($year, $m, 13);

            if ($secondStart->lte($today) && $generated < $count) {
                $cutoffs->push([
                    'start_date' => $secondStart->toDateString(),
                    'end_date'   => $secondEnd->toDateString(),
                    'name'       => $secondStart->format('M j') . ' – ' . $secondEnd->format('M j, Y'),
                    'status'     => $secondEnd->lt($today) ? 'finalized' : 'draft',
                ]);
                $generated++;
            }

            if ($firstStart->lte($today) && $generated < $count) {
                $cutoffs->push([
                    'start_date' => $firstStart->toDateString(),
                    'end_date'   => $firstEnd->toDateString(),
                    'name'       => $firstStart->format('M j') . ' – ' . $firstEnd->format('M j, Y'),
                    'status'     => $firstEnd->lt($today) ? 'finalized' : 'draft',
                ]);
                $generated++;
            }

            $month->subMonthNoOverflow();
        }

        return $cutoffs->sortBy('start_date')->values();
    }
}
