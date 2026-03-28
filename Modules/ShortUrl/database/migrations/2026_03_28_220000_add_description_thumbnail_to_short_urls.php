<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            if (! Schema::hasColumn('short_urls', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('short_urls', 'thumbnail')) {
                $table->string('thumbnail', 500)->nullable()->after('og_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->dropColumn(['description', 'thumbnail']);
        });
    }
};
