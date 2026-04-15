<?php

namespace App\Console\Commands;

use App\Models\PayrollCutoff;
use Illuminate\Console\Command;

class AutoAcknowledgePayslips extends Command
{
    protected $signature = 'payroll:auto-acknowledge
                            {--dry-run : Preview without saving}';

    protected $description = 'Auto-stamp payslips that have not been acknowledged 5 days after finalization';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $cutoffs = PayrollCutoff::where('status', 'finalized')
            ->whereNotNull('finalized_at')
            ->where('finalized_at', '<=', now()->subDays(5))
            ->get();

        if ($cutoffs->isEmpty()) {
            $this->info('No cutoffs eligible for auto-acknowledgment.');
            return self::SUCCESS;
        }

        $total = 0;

        foreach ($cutoffs as $cutoff) {
            $entries = $cutoff->payrollEntries()
                ->with('employee')
                ->whereNull('acknowledged_at')
                ->get();

            if ($entries->isEmpty()) {
                continue;
            }

            $this->info(sprintf(
                '%s cutoff #%d (%s) — %d unacknowledged entry(ies)',
                $dryRun ? '[DRY RUN]' : 'Processing',
                $cutoff->id,
                $cutoff->name,
                $entries->count(),
            ));

            foreach ($entries as $entry) {
                $this->line("  → {$entry->employee->full_name}");

                if (! $dryRun) {
                    $entry->update([
                        'acknowledged_at' => now(),
                        'acknowledged_ip' => null,
                        'acknowledged_by' => 'system',
                    ]);
                }

                $total++;
            }
        }

        $this->info(
            $dryRun
                ? "{$total} payslip(s) would be auto-acknowledged."
                : "{$total} payslip(s) auto-acknowledged."
        );

        return self::SUCCESS;
    }
}
