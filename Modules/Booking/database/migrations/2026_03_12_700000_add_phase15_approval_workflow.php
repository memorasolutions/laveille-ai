<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('booking_services', 'require_approval')) {
            Schema::table('booking_services', function (Blueprint $table) {
                $table->boolean('require_approval')->default(false)->after('is_group');
            });
        }

        if (! Schema::hasColumn('booking_appointments', 'approval_note')) {
            Schema::table('booking_appointments', function (Blueprint $table) {
                $table->text('approval_note')->nullable()->after('cancel_reason');
                $table->timestamp('approved_at')->nullable()->after('approval_note');
            });
        }
    }

    public function down(): void
    {
        Schema::table('booking_appointments', function (Blueprint $table) {
            $table->dropColumn(['approval_note', 'approved_at']);
        });

        Schema::table('booking_services', function (Blueprint $table) {
            $table->dropColumn('require_approval');
        });
    }
};
