<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('bio');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('must_change_password')->default(false)->after('phone_verified_at');
        });

        $rolesTable = config('permission.table_names.roles', 'roles');
        Schema::table($rolesTable, function (Blueprint $table) {
            $table->boolean('requires_password')->default(true)->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at', 'must_change_password']);
        });

        $rolesTable = config('permission.table_names.roles', 'roles');
        Schema::table($rolesTable, function (Blueprint $table) {
            $table->dropColumn('requires_password');
        });
    }
};
