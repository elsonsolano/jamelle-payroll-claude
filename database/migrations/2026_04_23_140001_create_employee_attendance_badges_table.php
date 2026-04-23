<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_badge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_cutoff_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_score_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('awarded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_badge_id', 'payroll_cutoff_id'], 'employee_badge_cutoff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_badges');
    }
};
