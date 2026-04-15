<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill finalized_at using updated_at for cutoffs that were
        // finalized before the finalized_at column was added.
        DB::statement("
            UPDATE payroll_cutoffs
            SET finalized_at = updated_at
            WHERE status = 'finalized'
              AND finalized_at IS NULL
        ");
    }

    public function down(): void
    {
        // Not reversible — leave as-is on rollback.
    }
};
