<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('employee_code')->unique();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('timemark_id')->unique();
            $table->enum('salary_type', ['daily', 'monthly']);
            $table->decimal('rate', 10, 2);
            $table->boolean('active')->default(true);
            $table->date('hired_date')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
