<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            if (! Schema::hasColumn('short_urls', 'last_visited_at')) {
                $table->dateTime('last_visited_at')->nullable()->after('clicks_count');
            }
            if (! Schema::hasColumn('short_urls', 'expiry_notified_at')) {
                $table->dateTime('expiry_notified_at')->nullable()->after('last_visited_at');
            }
        });

        // Mettre expires_at = now + 12 mois pour les liens existants sans expiration
        \DB::table('short_urls')->whereNull('expires_at')->update([
            'expires_at' => now()->addMonths(12),
        ]);
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumn(['last_visited_at', 'expiry_notified_at']);
        });
    }
};
