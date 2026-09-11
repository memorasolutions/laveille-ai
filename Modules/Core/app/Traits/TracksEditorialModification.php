<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Carbon;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Introduit la notion de date de modification ÉDITORIALE (le CONTENU a changé), distincte de
 * `updated_at` (la LIGNE a été touchée - y compris par un simple compteur de vues, cf.
 * Modules\Core\Services\ViewCounterService). Les deux notions sont confondues depuis toujours
 * sur ce projet : News/Tools/Directory/Authors publient `updated_at` comme si c'était une date
 * éditoriale (JSON-LD `dateModified`, `lastmod` de sitemap, texte visible « Mis à jour le… »),
 * et le glossaire (Dictionary) affiche de même `updated_at` comme texte « Mis à jour le… » -
 * alors qu'une simple consultation le rafraîchit dans les cinq cas. Mesuré en production
 * (2026-09-11, glossaire, seul module avec un point de comparaison exploitable) : 78 des 80
 * termes vérifiables dérivaient de plus de 24h, jusqu'à ~50 jours - voir
 * docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md.
 *
 * La RÈGLE (qu'est-ce qu'une modification éditoriale, quand la faire avancer) est UNE seule et
 * même chose pour tous les modules concernés : elle vit ICI, une seule fois. La LISTE des champs
 * qui constituent le contenu éditorial, elle, est propre à chaque modèle (déclarée via la
 * propriété $editorialFields) - ce n'est pas la même donnée que $activitylogFields (Spatie
 * Activitylog, quand présent) : les deux se ressemblent souvent mais n'ont pas vocation à
 * toujours coïncider (journal d'audit interne vs. signal de fraîcheur publié), donc ne sont
 * jamais fusionnées entre elles (DRY nuancé, CLAUDE.md).
 *
 * `ViewCounterService::record()` n'appelle jamais `save()` (il passe par le query builder brut
 * `increment()`, qui ne déclenche aucun événement Eloquent) : un compteur de vues ne peut donc
 * jamais, même indirectement, faire avancer `content_updated_at`.
 */
trait TracksEditorialModification
{
    public static function bootTracksEditorialModification(): void
    {
        static::saving(function ($model): void {
            $fields = property_exists($model, 'editorialFields') ? $model->editorialFields : [];

            if ($fields === []) {
                return;
            }

            // À la création, tous les champs éditoriaux renseignés sont "nouveaux" (dirty) : la
            // création EST une modification éditoriale. On le force explicitement (plutôt que de
            // dépendre du hasard des champs réellement remplis) pour que content_updated_at ne
            // reste jamais nul sur une ligne neuve.
            if (! $model->exists || $model->isDirty($fields)) {
                $model->content_updated_at = Carbon::now();
            }
        });
    }

    /**
     * Date de dernière modification éditoriale, avec repli honnête : jamais une date plus
     * récente que ce qu'on peut prouver. `content_updated_at` nul (ligne jamais réévaluée depuis
     * l'introduction de la colonne, cas qui ne devrait plus se produire après la migration de
     * bascule) retombe sur la date de création - jamais sur `updated_at` (qui peut avoir été
     * touché par autre chose qu'une édition de contenu) ni sur `now()`.
     */
    public function editorialModifiedAt(): ?Carbon
    {
        return $this->content_updated_at ?? $this->created_at;
    }

    /**
     * Distingue une VRAIE révision connue (content_updated_at a avancé au-delà de created_at -
     * soit backfillé depuis une entrée d'activity_log réelle, soit posé par un save() ultérieur
     * dont un champ éditorial était dirty) du simple repli technique posé quand aucune trace
     * n'existe (content_updated_at = created_at, cf. migration de bascule). Sert à décider si un
     * affichage public doit montrer une date de révision - jamais présenter la date de CRÉATION
     * comme une date de RÉVISION, ce serait le même mensonge sous un autre nom.
     */
    public function hasKnownEditorialRevision(): bool
    {
        if ($this->content_updated_at === null || $this->created_at === null) {
            return false;
        }

        return ! $this->content_updated_at->equalTo($this->created_at);
    }
}
