<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            // Snapshot of the schedule at the time of request
            $table->time('current_work_start_time')->nullable();
            $table->time('current_work_end_time')->nullable();
            $table->boolean('is_current_day_off')->default(false);

            // What the staff is requesting
            $table->time('requested_work_start_time')->nullable();
            $table->time('requested_work_end_time')->nullable();
            $table->boolean('is_day_off')->default(false);
            $table->text('reason');

            // Review
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // What was actually approved (approver may adjust times)
            $table->time('approved_start_time')->nullable();
            $table->time('approved_end_time')->nullable();
            $table->text('rejection_reason')->nullable();

            // The DailySchedule created/updated on approval
            $table->foreignId('daily_schedule_id')->nullable()->constrained('daily_schedules')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_change_requests');
    }
};
