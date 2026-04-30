<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_cutoffs', function (Blueprint $table) {
            $table->boolean('has_philhealth')->default(false)->after('end_date');
            $table->foreignId('philhealth_partner_cutoff_id')->nullable()->constrained('payroll_cutoffs')->nullOnDelete()->after('has_philhealth');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_cutoffs', function (Blueprint $table) {
            $table->dropForeign(['philhealth_partner_cutoff_id']);
            $table->dropColumn(['has_philhealth', 'philhealth_partner_cutoff_id']);
        });
    }
};
