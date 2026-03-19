<?php

namespace App\Console\Commands;

use App\Jobs\FetchAttendanceJob;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchAttendanceCommand extends Command
{
    protected $signature = 'attendance:fetch
                            {--date-from= : Start date (Y-m-d). Defaults to yesterday.}
                            {--date-to=   : End date (Y-m-d). Defaults to yesterday.}';

    protected $description = 'Dispatch attendance fetch jobs for all active employees';

    public function handle(): int
    {
        $dateFrom = $this->option('date-from') ?? Carbon::yesterday()->toDateString();
        $dateTo   = $this->option('date-to')   ?? Carbon::yesterday()->toDateString();

        $employees = Employee::where('active', true)
            ->whereNotNull('timemark_id')
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No active employees with a timemark ID found.');
            return self::SUCCESS;
        }

        foreach ($employees as $employee) {
            FetchAttendanceJob::dispatch($employee, $dateFrom, $dateTo);
        }

        $this->info("Dispatched fetch jobs for {$employees->count()} employee(s) [{$dateFrom} → {$dateTo}].");

        return self::SUCCESS;
    }
}
