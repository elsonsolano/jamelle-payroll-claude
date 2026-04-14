<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Services\PayrollComputationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecomputePayrollCutoff extends Command
{
    protected $signature = 'payroll:recompute
                            {cutoff : Payroll cutoff ID}
                            {--mode=default : Payroll math mode (default or sheet)}
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Recompute payroll entries for a single cutoff using the selected payroll math mode';

    public function handle(PayrollComputationService $payrollService): int
    {
        $cutoff = PayrollCutoff::with('branch')->find($this->argument('cutoff'));

        if (! $cutoff) {
            $this->error('Payroll cutoff not found.');

            return self::FAILURE;
        }

        $mode = (string) $this->option('mode');

        if (! in_array($mode, ['default', 'sheet'], true)) {
            $this->error("Unsupported mode [{$mode}]. Use default or sheet.");

            return self::FAILURE;
        }

        $employees = Employee::where('branch_id', $cutoff->branch_id)
            ->where('active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No active employees found for this cutoff branch.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%s cutoff #%d (%s, %s to %s) using [%s] mode for %d employee(s).',
            $dryRun ? 'Previewing' : 'Recomputing',
            $cutoff->id,
            $cutoff->branch?->name ?? 'Unknown branch',
            $cutoff->start_date->toDateString(),
            $cutoff->end_date->toDateString(),
            $mode,
            $employees->count()
        ));

        $rows = [];

        DB::beginTransaction();

        try {
            foreach ($employees as $employee) {
                $before = $cutoff->payrollEntries()
                    ->where('employee_id', $employee->id)
                    ->first();

                $entry = $payrollService->computeEntry($cutoff, $employee, ['mode' => $mode]);

                $rows[] = [
                    'employee' => $employee->full_name,
                    'before' => $before ? number_format((float) $before->net_pay, 2) : 'NEW',
                    'after' => number_format((float) $entry->net_pay, 2),
                    'diff' => $before
                        ? number_format(round((float) $entry->net_pay - (float) $before->net_pay, 2), 2)
                        : 'NEW',
                    'days' => number_format((float) $entry->working_days, 2),
                ];
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['Employee', 'Before Net', 'After Net', 'Diff', 'Working Days'], $rows);

        $changedCount = collect($rows)->filter(fn (array $row) => $row['diff'] !== '0.00')->count();

        $this->info(
            $dryRun
                ? "{$changedCount} employee(s) would change."
                : "{$changedCount} employee(s) changed and were saved."
        );

        return self::SUCCESS;
    }
}
