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
        // 1. Détecter les doublons AVANT toute modification : on échoue proprement, jamais d'état à moitié migré.
        $duplicates = DB::table('shop_print_files')
            ->selectRaw("product_id, COALESCE(variant_uid, '') as vkey, print_area, COUNT(*) as n")
            ->groupBy('product_id', DB::raw("COALESCE(variant_uid, '')"), 'print_area')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($duplicates->isNotEmpty()) {
            $list = $duplicates->map(fn ($d) => "(product_id={$d->product_id}, variant='{$d->vkey}', zone={$d->print_area}) x{$d->n}")->implode(' ; ');
            throw new RuntimeException('Migration shop_print_files interrompue AVANT toute modification : doublons (product_id, variant_uid, print_area) à résoudre manuellement (aucune donnée supprimée) : '.$list);
        }

        // 2. Colonnes seulement si absentes (reprise possible après un échec partiel).
        if (! Schema::hasColumn('shop_print_files', 'product_uid')) {
            Schema::table('shop_print_files', fn (Blueprint $table) => $table->string('product_uid', 255)->nullable());
        }
        if (! Schema::hasColumn('shop_print_files', 'variant_key')) {
            Schema::table('shop_print_files', fn (Blueprint $table) => $table->string('variant_key', 191)->default(''));
        }

        DB::table('shop_print_files')->update(['variant_key' => DB::raw("COALESCE(variant_uid, '')")]);

        // 3. Nouvel index AVANT de retirer l'ancien (la clé étrangère product_id exige un index sous MySQL).
        if (! $this->hasIndex('shop_print_files_target_key_unique')) {
            Schema::table('shop_print_files', function (Blueprint $table) {
                $table->unique(['product_id', 'variant_key', 'print_area'], 'shop_print_files_target_key_unique');
            });
        }
        if ($this->hasIndex('shop_print_files_target_unique')) {
            Schema::table('shop_print_files', function (Blueprint $table) {
                $table->dropUnique('shop_print_files_target_unique');
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        foreach (Schema::getIndexes('shop_print_files') as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
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
