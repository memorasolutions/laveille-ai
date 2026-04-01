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
        if (Schema::hasColumn('newsletter_subscribers', 'bounce_count')) return;
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->unsignedInteger('bounce_count')->default(0)->after('unsubscribed_at');
            $table->string('bounce_reason')->nullable()->after('bounce_count');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['bounce_count', 'bounce_reason']);
        });
    }
};
