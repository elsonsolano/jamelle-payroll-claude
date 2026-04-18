<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_uploads', function (Blueprint $table) {
            $table->string('name')->nullable()->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_uploads', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
