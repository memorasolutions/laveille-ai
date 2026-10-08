<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive et réversible : garde d'envoi unique du courriel de confirmation (rejeu Stripe).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shop_orders', 'confirmation_sent_at')) {
            Schema::table('shop_orders', function (Blueprint $table) {
                $table->timestamp('confirmation_sent_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shop_orders', 'confirmation_sent_at')) {
            Schema::table('shop_orders', function (Blueprint $table) {
                $table->dropColumn('confirmation_sent_at');
            });
        }
    }
};
