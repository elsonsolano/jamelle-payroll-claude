<?php

namespace App\Console\Commands;

use App\Models\Dtr;
use App\Models\Employee;
use App\Services\DtrComputationService;
use Illuminate\Console\Command;

class RecomputeDtrHours extends Command
{
    protected $signature = 'dtr:recompute
                            {--employee= : Recompute only for a specific employee ID}
                            {--branch=   : Recompute only for a specific branch ID}
                            {--from=     : Start date (YYYY-MM-DD)}
                            {--to=       : End date (YYYY-MM-DD)}
                            {--dry-run   : Show what would change without saving}';

    protected $description = 'Recompute total_hours, late_mins, undertime_mins, and is_rest_day for all DTR records';

    public function handle(DtrComputationService $computer): int
    {
        $query = Dtr::with('employee')->whereNotNull('time_in');

        if ($this->option('employee')) {
            $query->where('employee_id', $this->option('employee'));
        }
        if ($this->option('branch')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $this->option('branch')));
        }
        if ($this->option('from')) {
            $query->where('date', '>=', $this->option('from'));
        }
        if ($this->option('to')) {
            $query->where('date', '<=', $this->option('to'));
        }

        $dtrs    = $query->orderBy('date')->get();
        $dryRun  = $this->option('dry-run');
        $total   = $dtrs->count();
        $changed = 0;

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Processing {$total} DTR record(s)…");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($dtrs as $dtr) {
            $employee = $dtr->employee;

            $computed = $computer->compute(
                $employee,
                $dtr->date->toDateString(),
                $dtr->time_in,
                $dtr->am_out,
                $dtr->pm_in,
                $dtr->time_out,
                $dtr->overtime_hours > 0 ? (float) $dtr->overtime_hours : null,
            );

            $dirty = (float) $dtr->total_hours    !== (float) $computed['total_hours']
                  || (int)   $dtr->late_mins       !== (int)   $computed['late_mins']
                  || (int)   $dtr->undertime_mins  !== (int)   $computed['undertime_mins']
                  || (bool)  $dtr->is_rest_day     !== (bool)  $computed['is_rest_day'];

            if ($dirty) {
                $changed++;
                if (! $dryRun) {
                    $dtr->total_hours    = $computed['total_hours'];
                    $dtr->late_mins      = $computed['late_mins'];
                    $dtr->undertime_mins = $computed['undertime_mins'];
                    $dtr->is_rest_day    = $computed['is_rest_day'];
                    $dtr->save();
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("{$changed} of {$total} record(s) " . ($dryRun ? 'would be updated.' : 'updated.'));

        return self::SUCCESS;
    }
}
