<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Aligne la fiche annuaire LucidNest sur le NOUVEAU positionnement de son frontend (lucidnest.io) :
 * le terme clinique « TDAH » est remplacé par le vocabulaire de marque officiel « cerveau-éclair »
 * (« qui pensent vite », « pensée non linéaire »). Décision du fondateur (2026-10-13) : « on a changé
 * le frontend à ce propos ». Le frontend public ne contient plus AUCUNE mention « TDAH » - la fiche
 * s'aligne.
 *
 * Remplacements CIBLÉS (sous-chaînes), appliqués sur les 6 champs qui portaient « TDAH » :
 * description, short_description, use_cases, pros, faq, target_audience. Travaille sur le JSON brut
 * stocké (JSON_UNESCAPED_UNICODE à l'origine : accents littéraux, apostrophes droites) - les
 * sous-chaînes ne contiennent ni guillemet double ni barre oblique inverse, donc str_replace sur la
 * chaîne encodée est sûr. L'auto-lien du glossaire vers « TDAH » disparaît de lui-même une fois le
 * mot retiré du texte. Réversible : down() applique les remplacements inverses.
 */
return new class extends Migration
{
    private const URL_LIKE = '%lucidnest.io%';

    private const COLS = ['description', 'short_description', 'use_cases', 'pros', 'faq', 'target_audience'];

    /** Paires ANCIEN => NOUVEAU (up applique dans ce sens, down l'inverse). */
    private const PAIRS = [
        // description (fr_CA)
        "conçue d'abord pour les personnes TDAH, et utile à tout cerveau qui pense vite"
            => "conçue d'abord pour les cerveaux-éclairs qui pensent vite, et utile à tout esprit à la pensée non linéaire",
        "par un fondateur TDAH, avec un souci fort"
            => "par un fondateur au cerveau-éclair, avec un souci fort",
        // short_description
        "Conçu par un fondateur TDAH pour les cerveaux qui pensent vite"
            => "Conçu par un fondateur au cerveau-éclair pour les cerveaux-éclairs qui pensent vite",
        // use_cases
        "Un adulte TDAH qui perd ses idées"
            => "Un cerveau-éclair qui perd ses idées",
        // pros (2 premiers items, séparés par « , » à l'affichage)
        "Conçu pour le fonctionnement TDAH, par un fondateur TDAH,"
            => "Conçu pour les cerveaux-éclairs qui pensent vite, Par un fondateur au cerveau-éclair,",
        // faq (question + réponse)
        "Faut-il un diagnostic de TDAH ?"
            => "Faut-il un diagnostic ou une étiquette particulière ?",
        "Non. Aucun diagnostic n'est demandé : l'outil est conçu pour le fonctionnement TDAH mais ouvert à toute personne."
            => "Non. Aucun diagnostic ni aucune étiquette n'est demandé : l'outil est pensé pour les cerveaux-éclairs qui pensent vite, et reste ouvert à toute personne.",
        // target_audience (badges + bloc « pour qui »)
        "Adultes TDAH" => "Cerveaux-éclairs qui pensent vite",
        "Étudiants TDAH" => "Étudiants",
    ];

    public function up(): void
    {
        $this->apply(self::PAIRS);
    }

    public function down(): void
    {
        $this->apply(array_flip(self::PAIRS));
    }

    /** @param array<string,string> $pairs */
    private function apply(array $pairs): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $row = DB::table('directory_tools')->where('url', 'like', self::URL_LIKE)->first();

        if (! $row) {
            return;
        }

        $updates = [];

        foreach (self::COLS as $col) {
            $value = $row->{$col};

            if ($value === null) {
                continue;
            }

            $value = str_replace(array_keys($pairs), array_values($pairs), $value);
            $updates[$col] = $value;
        }

        if ($updates === []) {
            return;
        }

        $updates['updated_at'] = now();

        DB::table('directory_tools')->where('id', $row->id)->update($updates);
    }
};
