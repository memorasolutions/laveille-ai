<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('author_posts')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE author_posts MODIFY COLUMN status ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft'");

            return;
        }

        // SQLite (tests) and others : enum est une contrainte CHECK ; bascule en string portable.
        Schema::table('author_posts', function (Blueprint $table): void {
            $table->string('status', 20)->default('draft')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('author_posts')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE author_posts MODIFY COLUMN status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft'");
        }
    }
};
