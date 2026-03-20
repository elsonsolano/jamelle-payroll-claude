<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the role enum to include 'staff' (MySQL only; SQLite uses strings)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff') NOT NULL DEFAULT 'admin'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->boolean('can_approve_ot')->default(false)->after('role');
            $table->longText('signature')->nullable()->after('can_approve_ot');
            $table->boolean('must_change_password')->default(false)->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['employee_id', 'can_approve_ot', 'signature', 'must_change_password']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin') NOT NULL DEFAULT 'admin'");
        }
    }
};
