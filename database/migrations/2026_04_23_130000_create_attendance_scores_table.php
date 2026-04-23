<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_cutoff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('total_points')->default(0);
            $table->integer('complete_dtr_days')->default(0);
            $table->integer('on_time_days')->default(0);
            $table->integer('proper_time_out_days')->default(0);
            $table->integer('approved_ot_days')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_cutoff_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scores');
    }
};
