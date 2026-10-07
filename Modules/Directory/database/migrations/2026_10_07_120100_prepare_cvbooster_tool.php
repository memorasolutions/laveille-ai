<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * ACTION: prépare la fiche CVBooster (soumise par un membre, en modération) : descriptif factuel
 * (short_description), URL, catégorie existante « Écriture IA » (aucune catégorie CV n'existe),
 * fait vérifié de politique de confidentialité. NE PUBLIE PAS : le statut n'est jamais touché.
 * Idempotente et sans effet si la fiche est absente (local, tests) ou n'est plus en modération.
 * description (longue) est conservée telle quelle : sous 500 caractères, l'enrichissement planifié
 * la réécrirait. down() restaure l'ancien descriptif et l'ancienne URL.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_SHORT = "Collez une offre d'emploi : score ATS de votre CV, mots-clés manquants, et en Pro un CV et une lettre adaptés par IA.";

    private const OLD_URL = 'https://cvbooster.ai/fr/';

    private function toolId(): ?int
    {
        if (! Schema::hasTable('directory_tools') || ! Schema::hasColumn('directory_tools', 'privacy_checked_at')) {
            return null;
        }
        $id = DB::table('directory_tools')->where('status', 'pending')->where('url', 'like', 'https://cvbooster.ai/%')->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    public function up(): void
    {
        $id = $this->toolId();
        if ($id === null) {
            return;
        }

        $short = "CVBooster utilise l'IA pour comparer un CV à une offre d'emploi, fournir un score de correspondance et repérer les mots-clés manquants. Il offre la réécriture de CV, la rédaction de lettres de présentation, 99 modèles, la prise en charge de sept langues et l'exportation en PDF ou DOCX. L'accès gratuit comprend le score et un aperçu des suggestions; un abonnement payant débloque les fonctions complètes.";

        DB::table('directory_tools')->where('id', $id)->where('status', 'pending')->update([
            'short_description' => json_encode(['fr_CA' => $short], JSON_UNESCAPED_UNICODE),
            'url' => 'https://cvbooster.ai/fr',
            'third_party_ai_note' => 'CVBooster transmet le CV et l\'offre d\'emploi à un modèle d\'IA tiers (OpenRouter) pour les traiter',
            'privacy_policy_url' => 'https://cvbooster.ai/privacy',
            'privacy_checked_at' => '2026-10-07',
        ]);

        $categoryId = DB::table('directory_categories')->where('slug->fr_CA', 'ecriture-ia')->value('id');
        if ($categoryId && ! DB::table('directory_category_tool')->where('directory_tool_id', $id)->where('directory_category_id', $categoryId)->exists()) {
            DB::table('directory_category_tool')->insert(['directory_tool_id' => $id, 'directory_category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        $id = $this->toolId();
        if ($id === null) {
            return;
        }

        DB::table('directory_tools')->where('id', $id)->update([
            'short_description' => json_encode(['fr_CA' => self::OLD_SHORT], JSON_UNESCAPED_UNICODE),
            'url' => self::OLD_URL,
            'third_party_ai_note' => null,
            'privacy_policy_url' => null,
            'privacy_checked_at' => null,
        ]);
    }
};
