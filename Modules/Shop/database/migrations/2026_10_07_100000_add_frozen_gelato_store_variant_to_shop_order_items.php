<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive et réversible : identifiant Gelato du produit catalogue FIGÉ à la création de la commande
// (un changement de mapping après le paiement ne doit jamais refuser un article déjà encaissé).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shop_order_items', 'gelato_store_product_variant_id')) {
            return;
        }
        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->string('gelato_store_product_variant_id')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('shop_order_items', 'gelato_store_product_variant_id')) {
            Schema::table('shop_order_items', function (Blueprint $table) {
                $table->dropColumn('gelato_store_product_variant_id');
            });
        }
    }
};
