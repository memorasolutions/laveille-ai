<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('preview_token', 64)->nullable()->unique()->after('content_password');
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->string('preview_token', 64)->nullable()->unique()->after('content_password');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('preview_token');
        });

        Schema::table('static_pages', function (Blueprint $table) {
            $table->dropColumn('preview_token');
        });
    }
};
