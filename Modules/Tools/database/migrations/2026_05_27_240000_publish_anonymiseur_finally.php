<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #313 — Sprint S129 final : publication publique officielle de
 * l'anonymiseur de texte après validation user de la refonte UX/UI 2026
 * (32 todos livrés, 14 commits, best practices pp_search appliquées).
 * Décision user 2026-05-27 : « GO Tu décides du mieux pour la plateforme ».
 * Idempotent (hasColumn check + UPDATE WHERE slug).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }
        DB::table('tools')->where('slug', 'anonymiseur')->update(['is_under_construction' => false]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tools', 'is_under_construction')) {
            return;
        }
        DB::table('tools')->where('slug', 'anonymiseur')->update(['is_under_construction' => true]);
    }
};
