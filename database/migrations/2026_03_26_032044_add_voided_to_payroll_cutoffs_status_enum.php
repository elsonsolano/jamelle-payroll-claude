<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payroll_cutoffs MODIFY COLUMN status ENUM('draft', 'processing', 'finalized', 'voided') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payroll_cutoffs MODIFY COLUMN status ENUM('draft', 'processing', 'finalized') NOT NULL DEFAULT 'draft'");
    }
};
