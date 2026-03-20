<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->enum('source', ['device', 'manual'])->default('device')->after('status');
            $table->time('ot_end_time')->nullable()->after('source');
            $table->enum('ot_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('ot_end_time');
            $table->foreignId('ot_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('ot_status');
            $table->timestamp('ot_approved_at')->nullable()->after('ot_approved_by');
            $table->text('ot_rejection_reason')->nullable()->after('ot_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('dtrs', function (Blueprint $table) {
            $table->dropForeign(['ot_approved_by']);
            $table->dropColumn(['source', 'ot_end_time', 'ot_status', 'ot_approved_by', 'ot_approved_at', 'ot_rejection_reason']);
        });
    }
};
