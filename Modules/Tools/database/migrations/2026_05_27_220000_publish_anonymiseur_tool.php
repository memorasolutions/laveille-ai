<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * #313 P1.7 : publication publique de l'anonymiseur de texte.
 * Bascule is_under_construction=false (MVP fonctionnel livré v1.44.0).
 * Idempotent : check hasColumn + skip si tool absent.
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
