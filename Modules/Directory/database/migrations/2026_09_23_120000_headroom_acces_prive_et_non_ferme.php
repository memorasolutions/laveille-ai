<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Reclasse la fiche Headroom de « Plus en ligne » vers « Accès privé », statut ajouté en v1.295.0.
 *
 * LE DÉFAUT, mesuré le 2026-09-23 sur la page servie : le bandeau annonçait « Cette plateforme a
 * fermé ses portes. » alors que la note portée quelques lignes plus bas disait le contraire - le
 * service répond 401 avec « walls.sh is private », donc il est devenu privé, pas disparu. La fiche
 * se contredisait elle-même, et c'est le bandeau, en gros caractères, que le visiteur lit.
 *
 * Ce n'était pas une négligence : aucun des huit statuts existants n'était juste, et la personne
 * qui a classé cette fiche a pris la case la moins fausse. Le vrai défaut était l'absence de case
 * juste, comblée par le statut « private ».
 *
 * DEUX PIÈGES DE CE PROJET, TOUS DEUX DÉJÀ PAYÉS, ÉVITÉS ICI PAR CONSTRUCTION :
 *
 * 1. `directory_tools.slug` est TRADUISIBLE (Spatie) : il contient
 *    {"fr_CA":"headroom","fr":"headroom"}, jamais la chaîne nue. Un where('slug', 'headroom') n'y
 *    trouve RIEN et sort DONE en une milliseconde - exactement l'échec silencieux qui a fait
 *    échouer quatre fois le correctif de l'anonymiseur, la veille.
 * 2. Le filtrage JSON côté SQL est écarté : `JSON_UNQUOTE` n'existe pas sur SQLite, où tournent
 *    les tests et la CI, et `RefreshDatabase` rejoue toutes les migrations à chaque test. Une
 *    telle clause ferait échouer la suite entière, donc bloquerait la livraison de tout le reste.
 *    On ramène donc les candidats par un LIKE, identique sur les deux moteurs, et on tranche en
 *    PHP sur la valeur décodée.
 *
 * La garde sur le statut actuel est volontaire : si quelqu'un a déjà reclassé cette fiche à la
 * main dans l'écran d'administration, sa décision est respectée et la migration ne fait rien.
 * Réversible : down() remet le statut d'avant, à la même condition.
 */
return new class extends Migration
{
    private const SLUG = 'headroom';

    public function up(): void
    {
        $this->reclasser('closed', 'private');
    }

    public function down(): void
    {
        $this->reclasser('private', 'closed');
    }

    private function reclasser(string $de, string $vers): void
    {
        if (! Schema::hasTable('directory_tools')) {
            return;
        }

        foreach ($this->fichesCibles() as $fiche) {
            if ($fiche->lifecycle_status !== $de) {
                continue; // déjà reclassée à la main, ou jamais dans cet état : on n'y touche pas
            }

            DB::table('directory_tools')
                ->where('id', $fiche->id)
                ->update(['lifecycle_status' => $vers]);
        }
    }

    /**
     * Rend la ou les fiches visées, quelle que soit la représentation de leur slug.
     *
     * Le LIKE est volontairement large - « headroom » attraperait aussi « headroom-pro ». C'est la
     * comparaison EXACTE de slugCorrespond() qui tranche, jamais le LIKE.
     *
     * @return array<int, object>
     */
    private function fichesCibles(): array
    {
        $candidats = DB::table('directory_tools')
            ->where('slug', 'like', '%'.self::SLUG.'%')
            ->get(['id', 'slug', 'lifecycle_status']);

        return array_values(array_filter(
            $candidats->all(),
            fn ($fiche) => $this->slugCorrespond((string) $fiche->slug, self::SLUG)
        ));
    }

    /** Vrai si le slug stocké désigne bien la fiche visée, qu'il soit une chaîne nue ou du JSON. */
    private function slugCorrespond(string $stocke, string $attendu): bool
    {
        if ($stocke === $attendu) {
            return true;
        }

        $traductions = json_decode($stocke, true);

        return is_array($traductions) && in_array($attendu, $traductions, true);
    }
};
