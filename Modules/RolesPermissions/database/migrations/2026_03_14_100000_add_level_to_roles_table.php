<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(10)->after('guard_name');
        });

        DB::table('roles')->where('name', 'super_admin')->update(['level' => 100]);
        DB::table('roles')->where('name', 'admin')->update(['level' => 80]);
        DB::table('roles')->where('name', 'editor')->update(['level' => 40]);
        DB::table('roles')->where('name', 'user')->update(['level' => 10]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
