<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lien court de marque permanent « qt » → page du quiz QT avec UTM.
 * Résolution par slug GLOBAL (host-indépendant) → lurl.ca/qt, 1lien.ca/qt, etc.
 * Sert d'URL courte propre dans le bloc de partage copiable du QT (tracking via UTM).
 * Permanent (user_id null, is_anonymous false, expires_at null). Réversible.
 */
return new class extends Migration
{
    private string $slug = 'qt';

    private string $dest = 'https://laveille.ai/outils/qt?utm_source=partage&utm_medium=social&utm_campaign=qt';

    public function up(): void
    {
        if (! Schema::hasTable('short_urls')) {
            return;
        }
        if (DB::table('short_urls')->where('slug', $this->slug)->exists()) {
            echo "[shorturl] slug 'qt' déjà présent, skip\n";

            return;
        }

        $domainId = DB::table('short_url_domains')->where('is_default', true)->value('id')
            ?? DB::table('short_url_domains')->where('domain', 'lurl.ca')->value('id');

        DB::table('short_urls')->insert([
            'user_id' => null,
            'domain_id' => $domainId,
            'slug' => $this->slug,
            'original_url' => $this->dest,
            'title' => 'QT — Quotient Techno',
            'is_active' => true,
            'is_anonymous' => false,
            'redirect_type' => 301,
            'clicks_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "[shorturl] lien 'qt' créé (domain_id=".var_export($domainId, true).")\n";
    }

    public function down(): void
    {
        if (Schema::hasTable('short_urls')) {
            DB::table('short_urls')->where('slug', $this->slug)->where('original_url', $this->dest)->delete();
        }
    }
};
