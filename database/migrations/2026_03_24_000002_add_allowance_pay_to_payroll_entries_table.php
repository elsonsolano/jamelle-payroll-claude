<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->decimal('allowance_pay', 10, 2)->default(0)->after('holiday_pay');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn('allowance_pay');
        });
    }
};
