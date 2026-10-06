<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive : colonne nullable. Sert de verrou atomique d'idempotence pour la commande Gelato.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->string('gelato_submit_key', 80)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropUnique(['gelato_submit_key']);
            $table->dropColumn('gelato_submit_key');
        });
    }
};
