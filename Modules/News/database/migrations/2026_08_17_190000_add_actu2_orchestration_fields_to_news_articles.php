<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Implémentation /actu2 - volet serveur (design doc "Actus - composition manuelle assistée"
 * 2026-08-15, section "Implémentation /actu2 - volet serveur (2026-08-17)") : le skill Claude
 * Code local orchestre désormais la composition d'une fiche (retrouve l'ORIGINAL, rédige, prouve,
 * révise, choisit la photo, publie) ; les règles vérifiables restent serveur. Trois colonnes
 * additives et réversibles, toutes nullable (aucune fiche existante n'en porte) :
 *
 * - 'nature_original' (string, nullable) : classification de la NATURE de l'original retrouvé -
 *   annonce_commerciale, etude_evaluee, preimpression ou message_personnel. Interne (jamais
 *   affiché tel quel sur la fiche publique), utile au propriétaire pour juger la fiabilité de la
 *   composition a posteriori.
 * - 'niveau_preuve' (string, nullable) : primaire, mixte ou relais - degré auquel la fiche
 *   s'appuie sur l'original plutôt que sur un texte secondaire. PUBLIC (traduit en français
 *   courant, jamais l'étiquette technique brute) : badge sobre près de la section « Sources » de
 *   Modules\News\resources\views\public\show.blade.php.
 * - 'original_post' (JSON, nullable) : {text, author, handle, date, url} - citation STATIQUE d'un
 *   post X quand l'ORIGINAL retrouvé par le skill est lui-même un post (jamais le widget
 *   platform.x.com : script tiers interdit, pistage/CSP/fragilité). PUBLIC, affiché après le
 *   résumé sur la fiche publique.
 *
 * Seul écrivain : Modules\News\Console\NewsApplyCommand (--payload), même porte bornée que les
 * champs de composition existants (primary_sources, image_credit) - aucune dérogation.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'nature_original')) {
                $table->string('nature_original')->nullable()->after('image_credit');
            }
            if (! Schema::hasColumn('news_articles', 'niveau_preuve')) {
                $table->string('niveau_preuve')->nullable()->after('nature_original');
            }
            if (! Schema::hasColumn('news_articles', 'original_post')) {
                $table->json('original_post')->nullable()->after('niveau_preuve');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'original_post')) {
                $table->dropColumn('original_post');
            }
            if (Schema::hasColumn('news_articles', 'niveau_preuve')) {
                $table->dropColumn('niveau_preuve');
            }
            if (Schema::hasColumn('news_articles', 'nature_original')) {
                $table->dropColumn('nature_original');
            }
        });
    }
};
