<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseAssignments — CRUD des échelles
 * personnalisées (F14) : création, édition, suppression owner-scoped, et liste
 * des échelles sélectionnables (les siennes + les échelles système + toutes si
 * admin). Inclut les helpers de sérialisation des niveaux (textarea ↔ tableau).
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse()
 * et re-autorise via $this->authorize('manageStructure', ...). Les échelles sont
 * re-résolues avec un scope owner (anti-IDOR) via resolveManageableScale(). Les
 * échelles système (owner_id null) ne sont modifiables que par un admin.
 * Aucun comportement modifié par rapport au composant d'origine.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Computed;
use Modules\Academy\Models\Scale;

trait HandlesScales
{
    // ─────────────────────────────────────────────────────────────────────────
    // F14 : ÉCHELLES personnalisées (scales) : CRUD owner-scopé (gâté manageStructure)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parse le textarea « niveaux » en liste ordonnée [{label, value}]. Une ligne =
     * « libellé | valeur » (séparateur « | »). Sans valeur explicite → valeur = rang
     * (1, 2, 3…) dans l'ordre saisi (du plus faible au plus fort). Lignes vides ignorées.
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function parseScaleItems(string $raw): array
    {
        $items = [];
        $rank  = 0;
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $rank++;
            $parts = explode('|', $line, 2);
            $label = trim($parts[0]);
            if ($label === '') {
                continue;
            }
            $value   = (count($parts) === 2 && is_numeric(trim($parts[1]))) ? (float) trim($parts[1]) : (float) $rank;
            $items[] = ['label' => $label, 'value' => $value];
        }

        return Scale::sanitizeItems($items);
    }

    /** Re-sérialise des niveaux en texte « libellé | valeur » (une ligne par niveau). */
    private function scaleItemsToText(array $items): string
    {
        $lines = [];
        foreach (Scale::sanitizeItems($items) as $lvl) {
            $value   = rtrim(rtrim(number_format($lvl['value'], 2, '.', ''), '0'), '.') ?: '0';
            $lines[] = $lvl['label'].' | '.$value;
        }

        return implode("\n", $lines);
    }

    /** Ouvre/ferme le panneau de gestion des échelles (gâté manageStructure). */
    public function toggleScales(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->showScales = ! $this->showScales;
    }

    /**
     * Re-résout une échelle GÉRABLE par l'utilisateur : une échelle qu'il POSSÈDE,
     * OU n'importe laquelle s'il est admin (academy.manage). Les échelles système
     * (owner_id null) ne sont éditables QUE par un admin. Sinon → ModelNotFound.
     */
    private function resolveManageableScale(int $scaleId): Scale
    {
        $query = Scale::where('id', $scaleId);

        if (! (bool) auth()->user()?->can('academy.manage')) {
            $query->where('owner_id', (int) auth()->id());
        }

        return $query->firstOrFail();
    }

    /** Crée une échelle POSSÉDÉE par l'utilisateur courant (owner_id = auth). */
    public function addScale(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->validate([
            'newScaleName'  => 'required|string|max:120',
            'newScaleItems' => 'required|string|max:5000',
        ], [], [
            'newScaleName'  => 'nom de l\'échelle',
            'newScaleItems' => 'niveaux',
        ]);

        $items = $this->parseScaleItems($this->newScaleItems);
        if (count($items) < 2) {
            $this->addError('newScaleItems', 'Une échelle doit comporter au moins deux niveaux (un par ligne).');

            return;
        }

        Scale::create([
            'owner_id' => (int) auth()->id(),
            'name'     => trim($this->newScaleName),
            'slug'     => \Illuminate\Support\Str::slug(trim($this->newScaleName)).'-'.\Illuminate\Support\Str::random(6),
            'items'    => $items,
        ]);

        $this->reset('newScaleName', 'newScaleItems');
        $this->flashSaved('Échelle créée.');
    }

    /** Charge une échelle gérable en édition (anti-IDOR). */
    public function editScale(int $scaleId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $scale = $this->resolveManageableScale($scaleId);

        $this->editingScale   = $scale->id;
        $this->editScaleName  = $scale->name;
        $this->editScaleItems = $this->scaleItemsToText((array) $scale->items);
        $this->resetErrorBag(['editScaleName', 'editScaleItems']);
    }

    public function saveScale(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if ($this->editingScale === null) {
            return;
        }
        $scale = $this->resolveManageableScale((int) $this->editingScale);

        $this->validate([
            'editScaleName'  => 'required|string|max:120',
            'editScaleItems' => 'required|string|max:5000',
        ], [], [
            'editScaleName'  => 'nom de l\'échelle',
            'editScaleItems' => 'niveaux',
        ]);

        $items = $this->parseScaleItems($this->editScaleItems);
        if (count($items) < 2) {
            $this->addError('editScaleItems', 'Une échelle doit comporter au moins deux niveaux (un par ligne).');

            return;
        }

        $scale->update([
            'name'  => trim($this->editScaleName),
            'items' => $items,
        ]);

        $this->reset('editingScale', 'editScaleName', 'editScaleItems');
        $this->flashSaved('Échelle modifiée.');
    }

    public function cancelScaleEdit(): void
    {
        $this->reset('editingScale', 'editScaleName', 'editScaleItems');
        $this->resetErrorBag(['editScaleName', 'editScaleItems']);
    }

    public function confirmScaleRemoval(int $scaleId): void
    {
        $this->confirmingScaleRemoval = $scaleId;
    }

    public function cancelScaleRemoval(): void
    {
        $this->confirmingScaleRemoval = null;
    }

    /**
     * Supprime une échelle gérable (anti-IDOR). Les devoirs qui la référencent
     * redeviennent NUMÉRIQUES (scale_id → null via FK nullOnDelete), jamais cassés.
     */
    public function deleteScale(int $scaleId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $scale = $this->resolveManageableScale($scaleId);
        $scale->delete();

        $this->confirmingScaleRemoval = null;
        if ($this->editingScale === $scaleId) {
            $this->reset('editingScale', 'editScaleName', 'editScaleItems');
        }
        $this->flashSaved('Échelle supprimée (les devoirs concernés redeviennent numériques).');
    }

    /**
     * Échelles SÉLECTIONNABLES par l'utilisateur (les siennes + les échelles système),
     * ou TOUTES s'il est admin. Sert au sélecteur d'échelle du devoir et au panneau.
     *
     * @return EloquentCollection<int, Scale>
     */
    #[Computed]
    public function selectableScales(): EloquentCollection
    {
        $query = Scale::query()->orderBy('name');

        if (! (bool) auth()->user()?->can('academy.manage')) {
            $uid = (int) auth()->id();
            $query->where(fn ($q) => $q->where('owner_id', $uid)->orWhereNull('owner_id'));
        }

        return $query->get();
    }
}
