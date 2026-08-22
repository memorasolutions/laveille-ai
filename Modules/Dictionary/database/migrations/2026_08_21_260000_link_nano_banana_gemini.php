<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * Relie "Nano Banana" à "Gemini (Google)" dans le graphe du glossaire (2026-08-21).
 *
 * Le terme parent existe sous le slug "gemini-google", et NON "gemini" : la sonde faite avant
 * d'écrire la fiche Nano Banana avait interrogé "/glossaire/gemini" (404) et conclu à tort que
 * le glossaire n'avait aucun terme sur Gemini. Le vrai slug n'est apparu qu'en lisant les liens
 * générés par l'auto-lien sur une fiche d'actualité. Leçon retenue : sonder un slug DEVINÉ ne
 * prouve pas l'absence d'un terme - c'est la liste réelle qui fait foi.
 *
 * Les deux termes ne font PAS doublon et n'ont pas été fusionnés : "Gemini (Google)" couvre la
 * famille de modèles dans son ensemble, "Nano Banana" couvre spécifiquement la ligne image et la
 * correspondance entre ses noms commerciaux et ses noms techniques. C'est exactement une relation
 * parent/enfant, celle que broader_slugs/narrower_slugs servent à porter.
 *
 * ADDITIVE ET IDEMPOTENTE par construction : chaque liste est relue puis complétée par un
 * array_values(array_unique(array_merge(...))), jamais remplacée. Rejouer la migration ne
 * duplique rien, et une relation posée à la main entre-temps survit. Le terme absent (base
 * locale partielle, 132 termes contre ~430 en production) est simplement ignoré, sans échec.
 *
 * RÉVERSIBLE : down() retire UNIQUEMENT les deux slugs ajoutés ici, en préservant le reste des
 * listes - jamais un vidage brutal, qui détruirait des relations qui ne viennent pas de nous.
 */
return new class extends Migration
{
    /** @var array<string, array{broader: list<string>, narrower: list<string>}> */
    private const LIENS = [
        'nano-banana' => ['broader' => ['gemini-google'], 'narrower' => []],
        'gemini-google' => ['broader' => [], 'narrower' => ['nano-banana']],
    ];

    private function appliquer(bool $ajouter): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class)) {
            return;
        }

        foreach (self::LIENS as $slug => $relations) {
            $term = Term::where('slug->fr_CA', $slug)->first()
                ?? Term::where('slug->fr', $slug)->first();

            if (! $term) {
                echo "[glossaire] terme absent, lien ignoré : {$slug}\n";

                continue;
            }

            foreach (['broader' => 'broader_slugs', 'narrower' => 'narrower_slugs'] as $cle => $colonne) {
                if ($relations[$cle] === []) {
                    continue;
                }

                $actuels = (array) ($term->{$colonne} ?? []);

                $term->{$colonne} = $ajouter
                    ? array_values(array_unique(array_merge($actuels, $relations[$cle])))
                    : array_values(array_diff($actuels, $relations[$cle]));
            }

            $term->save();
            echo '[glossaire] '.($ajouter ? 'lié' : 'délié')." : {$slug}\n";
        }
    }

    public function up(): void
    {
        $this->appliquer(true);
    }

    public function down(): void
    {
        $this->appliquer(false);
    }
};
