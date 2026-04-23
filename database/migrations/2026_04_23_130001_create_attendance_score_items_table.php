<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_score_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_score_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dtr_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date')->nullable();
            $table->string('rule_key');
            $table->string('description');
            $table->integer('points');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_score_items');
    }
};
