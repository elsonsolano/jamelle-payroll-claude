<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_scores', function (Blueprint $table) {
            $table->integer('same_day_complete_days')->default(0)->after('proper_time_out_days');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_scores', function (Blueprint $table) {
            $table->dropColumn('same_day_complete_days');
        });
    }
};
