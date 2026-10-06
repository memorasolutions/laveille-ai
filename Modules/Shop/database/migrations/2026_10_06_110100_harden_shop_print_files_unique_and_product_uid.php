<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Additive et réversible.
// - product_uid : productUid Gelato utilisé à la PRÉPARATION (comparé à celui de l'item à la commande).
// - variant_key : sentinelle non nulle ('' = tout le produit) ; l'unicité (product_id, variant_uid, print_area)
//   était inopérante quand variant_uid est NULL (NULL != NULL dans un index unique).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_print_files', function (Blueprint $table) {
            $table->string('product_uid', 255)->nullable();
            $table->string('variant_key', 191)->default('');
        });

        DB::table('shop_print_files')->update(['variant_key' => DB::raw("COALESCE(variant_uid, '')")]);

        // Nouvel index AVANT de retirer l'ancien (la clé étrangère product_id exige un index sous MySQL).
        Schema::table('shop_print_files', function (Blueprint $table) {
            $table->unique(['product_id', 'variant_key', 'print_area'], 'shop_print_files_target_key_unique');
        });
        Schema::table('shop_print_files', function (Blueprint $table) {
            $table->dropUnique('shop_print_files_target_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shop_print_files', function (Blueprint $table) {
            $table->unique(['product_id', 'variant_uid', 'print_area'], 'shop_print_files_target_unique');
        });
        Schema::table('shop_print_files', function (Blueprint $table) {
            $table->dropUnique('shop_print_files_target_key_unique');
        });
        Schema::table('shop_print_files', function (Blueprint $table) {
            $table->dropColumn(['product_uid', 'variant_key']);
        });
    }
};
