<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive et réversible : mode de livraison CHOISI et payé par le client (shipmentMethodUid Gelato),
// transmis à la commande Gelato au lieu de laisser Gelato choisir la méthode la moins chère.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shop_orders', 'shipping_method_uid')) {
            return;
        }
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->string('shipping_method_uid')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('shop_orders', 'shipping_method_uid')) {
            Schema::table('shop_orders', function (Blueprint $table) {
                $table->dropColumn('shipping_method_uid');
            });
        }
    }
};
