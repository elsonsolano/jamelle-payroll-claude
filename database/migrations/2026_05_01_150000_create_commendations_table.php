<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->json('trait_ids');
            $table->unsignedTinyInteger('points');
            $table->timestamps();

            $table->unique(['sender_user_id', 'recipient_employee_id']);
            $table->index('recipient_employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commendations');
    }
};
