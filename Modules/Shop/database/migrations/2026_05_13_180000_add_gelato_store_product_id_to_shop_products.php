<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            // UUID du store_product Gelato (ex: 41394873-2eaf-4950-bafc-15f6769ede41)
            // Différent du legacy `gelato_product_id` qui contenait un product_uid catalog (ex: apparel_product_gca_t-shirt_...)
            $table->string('gelato_store_product_id', 64)->nullable()->after('gelato_product_id')->index();
            // Backup de l'ancien gelato_product_id en cas de rollback
            $table->string('legacy_gelato_uid')->nullable()->after('gelato_store_product_id');
            // Dernière sync Gelato
            $table->timestamp('gelato_synced_at')->nullable()->after('legacy_gelato_uid');
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['gelato_store_product_id', 'legacy_gelato_uid', 'gelato_synced_at']);
        });
    }
};
