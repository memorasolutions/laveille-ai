<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * POURQUOI CETTE MIGRATION EXISTE.
 *
 * L'outil « Tirage de présentations » (/outils/tirage-presentations) a gagné 4 capacités
 * (demande fondateur 2026-09-25) : tirer les apprenants sans aucune question, n'utiliser qu'un
 * sous-ensemble des questions saisies, attribuer un nombre réglable de questions différentes à
 * chaque apprenant (sans doublon), et vider chaque liste (apprenants / questions) séparément.
 *
 * La description en table (« Tirez au sort l'ordre des présentations. ») ne décrivait plus
 * l'outil réel, et answer_summary/answer_points (boîte-réponse AEO/GEO) étaient vides. Le
 * seeder porte déjà le texte corrigé, mais il ne se rejoue pas en production (firstOrCreate) :
 * sans cette migration, la base garderait l'ancienne description. Même piège que la migration
 * 2026_09_22_170000 (anonymiseur).
 *
 * RÉVERSIBLE : down() restaure la description d'origine et revide answer_summary/answer_points,
 * mais UNIQUEMENT si leur contenu est encore exactement celui posé par up() - une modification
 * manuelle ultérieure dans l'admin n'est jamais écrasée dans un sens comme dans l'autre.
 */
return new class extends Migration
{
    private const SLUG = 'tirage-presentations';

    private const ANCIENNE_DESCRIPTION = "Tirez au sort l'ordre des présentations.";

    private const NOUVELLE_DESCRIPTION = "Tirez au sort l'ordre de vos présentations, avec ou sans question. Choisissez le nombre de questions par apprenant, sélectionnez celles à utiliser, et réinitialisez chaque liste séparément.";

    private const NOUVEAU_ANSWER_SUMMARY = "Un outil gratuit et 100 % local pour tirer au sort l'ordre de passage d'apprenants, avec ou sans question associée, et en choisissant combien de questions attribuer à chacun.";

    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $answerPoints = json_encode([
            "Fonctionne aussi sans aucune question, pour tirer au sort seulement les apprenants",
            "Sélectionnez un sous-ensemble des questions saisies à utiliser dans le tirage",
            "Choisissez le nombre de questions différentes attribuées à chaque apprenant, sans doublon",
            "Videz la liste des apprenants ou celle des questions indépendamment, sans tout perdre",
            "100 % gratuit et local, sans compte ni installation",
        ], JSON_UNESCAPED_UNICODE);

        // On ne touche QUE la ligne dont la description est encore l'ancienne, et dont
        // answer_summary est encore vide : une valeur déjà retouchée à la main dans l'admin ne
        // doit jamais être écrasée.
        DB::table('tools')
            ->where('slug', self::SLUG)
            ->where('description', self::ANCIENNE_DESCRIPTION)
            ->whereNull('answer_summary')
            ->update([
                'description' => self::NOUVELLE_DESCRIPTION,
                'answer_summary' => self::NOUVEAU_ANSWER_SUMMARY,
                'answer_points' => $answerPoints,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        DB::table('tools')
            ->where('slug', self::SLUG)
            ->where('description', self::NOUVELLE_DESCRIPTION)
            ->where('answer_summary', self::NOUVEAU_ANSWER_SUMMARY)
            ->update([
                'description' => self::ANCIENNE_DESCRIPTION,
                'answer_summary' => null,
                'answer_points' => null,
            ]);
    }
};
