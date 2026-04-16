<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
        });
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->dropForeign(['status_changed_by']);
            $table->dropColumn(['status_changed_by', 'status_changed_at']);
        });
    }
};
