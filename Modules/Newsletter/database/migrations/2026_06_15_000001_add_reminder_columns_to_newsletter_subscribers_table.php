<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table): void {
            $table->unsignedInteger('reminder_count')->default(0)->after('unsubscribe_feedback');
            $table->timestamp('last_reminded_at')->nullable()->after('reminder_count');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table): void {
            $table->dropColumn(['reminder_count', 'last_reminded_at']);
        });
    }
};
