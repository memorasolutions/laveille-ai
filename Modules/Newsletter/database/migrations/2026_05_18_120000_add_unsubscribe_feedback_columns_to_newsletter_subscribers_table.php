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
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_subscribers', 'unsubscribe_reason')) {
                // values attendues (enum applicatif, pas DB enum pour flexibilité) :
                // 'too_frequent', 'not_relevant', 'no_value', 'life_change', 'other'
                $table->string('unsubscribe_reason', 50)->nullable()->after('unsubscribed_at')->index();
            }

            if (! Schema::hasColumn('newsletter_subscribers', 'unsubscribe_feedback')) {
                $table->text('unsubscribe_feedback')->nullable()->after('unsubscribe_reason');
            }

            if (! Schema::hasColumn('newsletter_subscribers', 'paused_until')) {
                $table->timestamp('paused_until')->nullable()->after('unsubscribe_feedback')->index();
            }

            if (! Schema::hasColumn('newsletter_subscribers', 'frequency_preference')) {
                // values : 'weekly' (default current), 'biweekly', 'monthly'
                $table->string('frequency_preference', 20)->nullable()->after('paused_until');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (Schema::hasColumn('newsletter_subscribers', 'unsubscribe_reason')) {
                $table->dropIndex(['unsubscribe_reason']);
            }

            if (Schema::hasColumn('newsletter_subscribers', 'paused_until')) {
                $table->dropIndex(['paused_until']);
            }

            $table->dropColumn([
                'unsubscribe_reason',
                'unsubscribe_feedback',
                'paused_until',
                'frequency_preference',
            ]);
        });
    }
};
