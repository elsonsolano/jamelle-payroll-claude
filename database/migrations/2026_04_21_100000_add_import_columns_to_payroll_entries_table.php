<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->decimal('daily_rate', 10, 2)->default(0)->after('working_days');
            $table->decimal('retirement_pay', 10, 2)->default(0)->after('allowance_pay');
            $table->decimal('thirteenth_month_allocation', 10, 2)->default(0)->after('retirement_pay');
            $table->boolean('is_imported')->default(false)->after('acknowledged_by');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn(['daily_rate', 'retirement_pay', 'thirteenth_month_allocation', 'is_imported']);
        });
    }
};
