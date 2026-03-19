<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_standing_deductions', function (Blueprint $table) {
            $table->enum('cutoff_period', ['both', 'first', 'second'])->default('both')->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('employee_standing_deductions', function (Blueprint $table) {
            $table->dropColumn('cutoff_period');
        });
    }
};
