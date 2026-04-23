<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_scores', function (Blueprint $table) {
            $table->unsignedInteger('no_absent_days')->default(0)->after('same_day_complete_days');
            $table->unsignedInteger('late_days')->default(0)->after('approved_ot_days');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_scores', function (Blueprint $table) {
            $table->dropColumn(['no_absent_days', 'late_days']);
        });
    }
};
