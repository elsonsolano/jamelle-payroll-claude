<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dtr_log_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dtr_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->enum('event_key', ['time_in', 'am_out', 'pm_in', 'time_out']);
            $table->time('logged_time');
            $table->timestamp('submitted_at')->nullable();
            $table->string('source')->default('staff_dashboard');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'work_date']);
            $table->index(['dtr_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dtr_log_events');
    }
};
