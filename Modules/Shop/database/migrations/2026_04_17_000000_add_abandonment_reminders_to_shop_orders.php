<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->timestamp('abandonment_reminder_24h_sent_at')->nullable()->after('created_at');
            $table->timestamp('abandonment_reminder_72h_sent_at')->nullable()->after('abandonment_reminder_24h_sent_at');
            $table->index(['status', 'created_at'], 'shop_orders_status_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropIndex('shop_orders_status_created_at_idx');
            $table->dropColumn([
                'abandonment_reminder_24h_sent_at',
                'abandonment_reminder_72h_sent_at',
            ]);
        });
    }
};
