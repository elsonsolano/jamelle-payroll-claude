<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rank_up_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('old_rank_number');
            $table->string('old_rank_name');
            $table->unsignedSmallInteger('new_rank_number');
            $table->string('new_rank_name');
            $table->integer('points');
            $table->string('source', 50);
            $table->timestamp('occurred_at');
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'new_rank_number']);
            $table->index(['user_id', 'seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_up_events');
    }
};
