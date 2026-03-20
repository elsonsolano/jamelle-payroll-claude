<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('sss_no')->nullable()->after('email');
            $table->string('phic_no')->nullable()->after('sss_no');
            $table->string('pagibig_no')->nullable()->after('phic_no');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['sss_no', 'phic_no', 'pagibig_no']);
        });
    }
};
