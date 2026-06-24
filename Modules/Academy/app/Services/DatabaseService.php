<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : SOURCE UNIQUE (DRY) de l'activité « database »
 * (item de leçon « database », type Moodle « Database » : le gérant définit un SCHÉMA de
 * champs, puis les inscrits SOUMETTENT des fiches (entrées) selon ce schéma ; tous
 * consultent la collection). Lue côté SERVEUR par le contrôleur d'actions, le lecteur
 * (lesson.blade) et l'éditeur (CourseEditor). La configuration, la synchronisation du
 * schéma, la liste des entrées, la validation par type et le rendu sûr des valeurs ne
 * vivent qu'ICI.
 *
 * Le payload de l'item porte (aucune nouvelle colonne, comme forum/wiki) :
 *   - intro             : texte d'introduction facultatif ;
 *   - allow_student_add : les inscrits peuvent AJOUTER une fiche (défaut true) ;
 *                         false => seul un gérant ajoute ; les inscrits consultent ;
 *   - require_approval  : une fiche d'étudiant naît « en attente » (défaut false) ;
 *                         visible de son seul auteur + des gérants jusqu'à approbation.
 *
 * Le SCHÉMA (les champs) vit dans la table academy_database_fields (pas dans le payload),
 * car les valeurs des fiches y font référence (FK). syncFields() applique le schéma défini
 * dans l'éditeur (création / mise à jour / soft-suppression des champs retirés).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Academy\Models\DatabaseEntry;
use Modules\Academy\Models\DatabaseField;
use Modules\Academy\Models\DatabaseValue;
use Modules\Academy\Models\LessonItem;

final class DatabaseService
{
    /** Types de champ pris en charge (liste blanche stricte). */
    public const FIELD_TYPES = ['text', 'textarea', 'number', 'url', 'select'];

    /** Type par défaut quand un type forgé / inconnu est reçu. */
    public const DEFAULT_TYPE = 'text';

    /** Nombre maximal de champs dans un schéma (anti-explosion). */
    public const MAX_FIELDS = 30;

    /** Nombre maximal d'options pour un champ « select ». */
    public const MAX_OPTIONS = 50;

    /** Longueur maximale d'un libellé / d'une clé technique. */
    public const LABEL_MAX = 200;

    /** Bornes de longueur des valeurs selon le type (anti-abus). */
    public const TEXT_MAX = 1000;

    public const TEXTAREA_MAX = 5000;

    public const URL_MAX = 2000;

    public const OPTION_MAX = 200;

    /** Nom du champ honeypot anti-spam (caché, doit rester vide). */
    public const HONEYPOT = 'hp_url';

    /** Nombre d'entrées par page d'affichage. */
    public const ENTRIES_PER_PAGE = 20;

    // ─────────────────────────────────────────────────────────────────────────────
    // LECTURE DE LA CONFIGURATION (payload)
    // ─────────────────────────────────────────────────────────────────────────────

    public static function intro(LessonItem $item): string
    {
        $intro = is_array($item->payload ?? null) ? ($item->payload['intro'] ?? '') : '';

        return is_string($intro) ? $intro : '';
    }

    /** Les inscrits peuvent-ils ajouter une fiche ? DÉFAUT true (clé absente = autorisé). */
    public static function allowsStudentAdd(LessonItem $item): bool
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['allow_student_add'] ?? null) : null;

        return $raw === null ? true : (bool) $raw;
    }

    /** Les fiches d'étudiants exigent-elles une approbation ? DÉFAUT false. */
    public static function requiresApproval(LessonItem $item): bool
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['require_approval'] ?? null) : null;

        return $raw === null ? false : (bool) $raw;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // SCHÉMA (champs)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Champs NON supprimés de l'item, dans l'ordre du schéma (position puis id).
     *
     * @return Collection<int, DatabaseField>
     */
    public static function fields(LessonItem $item): Collection
    {
        return DatabaseField::forItem($item->id)->limit(self::MAX_FIELDS)->get();
    }

    /**
     * Normalise une liste BRUTE de définitions de champs (venue de l'éditeur) en gabarits
     * propres prêts à persister. Ignore les champs sans libellé. Slugifie une clé technique
     * UNIQUE par schéma (dérivée du name fourni ou du libellé). Borne le type (liste blanche),
     * le booléen required et les options (pour « select » : multiligne ou tableau -> liste
     * dédoublonnée, bornée). Conserve l'id existant (édition d'un champ déjà persisté).
     *
     * @param  array<int|string, array<string, mixed>>  $raw
     * @return array<int, array{id: int|null, label: string, name: string, type: string, options: array<int, string>|null, required: bool}>
     */
    public static function normalizeFields(array $raw): array
    {
        $clean   = [];
        $usedKey = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue; // un champ sans libellé est ignoré (parité « ligne vide »).
            }
            $label = Str::limit($label, self::LABEL_MAX, '');

            $type = (string) ($row['type'] ?? self::DEFAULT_TYPE);
            if (! in_array($type, self::FIELD_TYPES, true)) {
                $type = self::DEFAULT_TYPE;
            }

            // Clé technique : name fourni sinon dérivée du libellé ; unique par schéma.
            $base = Str::slug((string) ($row['name'] ?? $label), '_');
            $base = $base !== '' ? Str::limit($base, 100, '') : 'champ';
            $key  = $base;
            $i    = 2;
            while (in_array($key, $usedKey, true)) {
                $key = $base.'_'.$i;
                $i++;
            }
            $usedKey[] = $key;

            $options = null;
            if ($type === 'select') {
                $options = self::parseOptions($row['options'] ?? []);
            }

            $id = isset($row['id']) && (int) $row['id'] > 0 ? (int) $row['id'] : null;

            $clean[] = [
                'id'       => $id,
                'label'    => $label,
                'name'     => $key,
                'type'     => $type,
                'options'  => $options,
                'required' => (bool) ($row['required'] ?? false),
            ];

            if (count($clean) >= self::MAX_FIELDS) {
                break;
            }
        }

        return $clean;
    }

    /**
     * Synchronise le SCHÉMA d'une activité « database » avec la liste brute de l'éditeur.
     * Crée les nouveaux champs, met à jour les champs conservés (par id, re-scopés à l'item
     * anti-IDOR), et SOFT-SUPPRIME les champs retirés (leurs valeurs déjà saisies restent
     * rattachées). La position suit l'ordre de la liste fournie.
     *
     * @param  array<int|string, array<string, mixed>>  $rawFields
     */
    public static function syncFields(LessonItem $item, array $rawFields): void
    {
        $normalized = self::normalizeFields($rawFields);

        // Champs actuels de l'item (re-scopés : anti-IDOR sur l'id fourni par l'éditeur).
        $existing = DatabaseField::forItem($item->id)->get()->keyBy('id');
        $keptIds  = [];

        foreach ($normalized as $position => $def) {
            $attrs = [
                'lesson_item_id' => $item->id,
                'label'          => $def['label'],
                'name'           => $def['name'],
                'type'           => $def['type'],
                'options'        => $def['type'] === 'select' ? $def['options'] : null,
                'required'       => $def['required'],
                'position'       => $position,
            ];

            $current = $def['id'] !== null ? $existing->get($def['id']) : null;

            if ($current !== null) {
                $current->update($attrs);
                $keptIds[] = $current->id;
            } else {
                $created   = DatabaseField::create($attrs);
                $keptIds[] = $created->id;
            }
        }

        // Champs retirés du schéma : soft-suppression (les valeurs restent en base).
        foreach ($existing as $id => $field) {
            if (! in_array((int) $id, $keptIds, true)) {
                $field->delete();
            }
        }
    }

    /**
     * Parse les options d'un champ « select » : tableau (déjà structuré) ou chaîne
     * MULTILIGNE (une option par ligne). Trim, retrait des vides, dédoublonnage, cap.
     *
     * @param  mixed  $raw
     * @return array<int, string>
     */
    public static function parseOptions(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        }

        $clean = [];
        foreach ((array) $raw as $label) {
            if (! is_string($label)) {
                continue;
            }
            $label = trim(Str::limit($label, self::OPTION_MAX, ''));
            if ($label !== '' && ! in_array($label, $clean, true)) {
                $clean[] = $label;
            }
        }

        return array_slice($clean, 0, self::MAX_OPTIONS);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ENTRÉES (fiches)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Entrées visibles de l'item, paginées (récentes d'abord). Précharge l'auteur et les
     * valeurs (anti N+1). Un gérant voit TOUT (approuvées + en attente) ; un inscrit voit
     * les approuvées + SES PROPRES fiches en attente ; un anonyme ne voit que les approuvées.
     */
    public static function entries(LessonItem $item, ?int $viewerId, bool $isManager): LengthAwarePaginator
    {
        return DatabaseEntry::forItem($item->id)
            ->with(['author:id,name', 'values:id,database_entry_id,database_field_id,value'])
            ->when(! $isManager, function ($q) use ($viewerId): void {
                // Non-gérant : approuvées OU les siennes (en attente comprises).
                $q->where(function ($sub) use ($viewerId): void {
                    $sub->where('is_approved', true);
                    if ($viewerId !== null) {
                        $sub->orWhere('user_id', $viewerId);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(self::ENTRIES_PER_PAGE, ['*'], 'dbentry'.$item->id.'page');
    }

    /** Valeurs d'une entrée indexées par database_field_id (lecture par le gabarit). */
    public static function valuesByField(DatabaseEntry $entry): array
    {
        $map = [];
        foreach ($entry->values as $value) {
            $map[(int) $value->database_field_id] = $value->value;
        }

        return $map;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // VALIDATION PAR TYPE + ÉCRITURE DES VALEURS
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Règles de validation Laravel d'une soumission, dérivées du SCHÉMA. Clé d'entrée
     * attendue : `values.{field_id}`. Chaque type impose sa contrainte (number = numérique,
     * url = url valide, select = dans les options) ; un champ requis passe de « nullable »
     * à « required ». Source UNIQUE des règles (réutilisée création + édition).
     *
     * @param  Collection<int, DatabaseField>  $fields
     * @return array<string, mixed>
     */
    public static function entryRules(Collection $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key      = 'values.'.$field->id;
            $required = $field->required ? 'required' : 'nullable';

            $rules[$key] = match ($field->type) {
                'textarea' => [$required, 'string', 'max:'.self::TEXTAREA_MAX],
                'number'   => [$required, 'numeric'],
                'url'      => [$required, 'url', 'max:'.self::URL_MAX],
                'select'   => [$required, 'string', Rule::in($field->options ?? [])],
                default    => [$required, 'string', 'max:'.self::TEXT_MAX],
            };
        }

        return $rules;
    }

    /**
     * Écrit (remplace) les valeurs d'une entrée d'après le schéma et l'entrée brute déjà
     * VALIDÉE. Une valeur absente => chaîne vide. Le texte est stocké BRUT (rendu strippé
     * à l'affichage, anti-XSS). Remplace l'existant (édition) de façon idempotente.
     *
     * @param  Collection<int, DatabaseField>  $fields
     * @param  array<int|string, mixed>        $rawValues  indexées par field_id
     */
    public static function storeValues(DatabaseEntry $entry, Collection $fields, array $rawValues): void
    {
        foreach ($fields as $field) {
            $value = $rawValues[$field->id] ?? null;
            $value = is_array($value) ? '' : trim((string) ($value ?? ''));

            DatabaseValue::updateOrCreate(
                ['database_entry_id' => $entry->id, 'database_field_id' => $field->id],
                ['value' => $value === '' ? null : $value],
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // RENDU SÛR DES VALEURS (anti-XSS)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Rend la valeur d'un champ en HTML SÛR selon son type :
     *   - text / textarea : markdown html_input=strip (anti-XSS) ;
     *   - url             : lien échappé (rel=nofollow noopener) si l'URL est valide ;
     *   - number / select : valeur échappée (e()).
     * Le résultat peut être rendu via {!! … !!} sans danger.
     */
    public static function renderValue(DatabaseField $field, ?string $value): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') {
            return '<span class="academy-db-empty" aria-hidden="true">-</span>';
        }

        return match ($field->type) {
            'textarea', 'text' => LessonItem::renderRichText($value),
            'url'              => filter_var($value, FILTER_VALIDATE_URL)
                ? '<a class="academy-db-link" href="'.e($value).'" rel="nofollow noopener" target="_blank">'.e($value).'</a>'
                : e($value),
            default            => e($value),
        };
    }
}
