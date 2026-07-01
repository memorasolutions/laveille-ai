<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\DiplomaTemplate;
use Modules\Academy\Services\DiplomaRenderService;

/**
 * Éditeur visuel de gabarits de diplômes (Konva.js pour le placement/redimensionnement
 * interactif des éléments — le RENDU FINAL du diplôme reste en HTML/CSS via
 * DiplomaRenderService, jamais en canvas).
 *
 * - OWNER-SCOPED : un formateur ne voit/gère QUE ses propres gabarits (created_by) ;
 *   l'admin (permission academy.manage) voit tout — même modèle que QuestionBankManager.
 * - Gâté par le drapeau academy.diploma_editor_enabled : OFF ⇒ abort(404), aucune
 *   route/action accessible, le système de certificat existant reste inchangé.
 * - Anti-IDOR strict : chaque action persistante (save/load/delete) RE-RÉSOUT le
 *   gabarit scopé au propriétaire — jamais confiance en un ID reçu du client seul.
 * - Suppression confirmée INLINE à 2 temps (confirmingDeleteId), jamais confirm() natif.
 */
class DiplomaTemplateEditor extends Component
{
    /** Élément de départ d'un gabarit vierge : nom de l'apprenant, centré en haut. */
    private const DEFAULT_ELEMENT = [
        'kind'        => 'text',
        'content'     => '{system.learner_name}',
        'variable'    => 'system.learner_name',
        'x'           => 30.0,
        'y'           => 20.0,
        'width'       => 40.0,
        'height'      => 10.0,
        'font_size'   => 28,
        'font_weight' => '600',
        'color'       => '#1A1D23',
        'align'       => 'center',
    ];

    /** @var array<int, array<string, mixed>> */
    public array $elements = [];

    public ?int $templateId = null;

    public string $name = 'Nouveau gabarit';

    public bool $isDefault = false;

    public ?string $selectedElementId = null;

    public ?int $confirmingDeleteId = null;

    public function mount(?int $templateId = null): void
    {
        // Drapeau OFF ⇒ fonctionnalité entièrement invisible (même garde que les
        // autres capacités LMS 2026 : OpenBadge, tuteur IA, nudges...).
        abort_unless((bool) config('academy.diploma_editor_enabled', false), 404);

        $user    = Auth::user();
        $allowed = $user !== null && ($user->can('academy.manage') || $user->hasRole('instructor'));
        abort_unless($allowed, 403);

        if ($templateId !== null) {
            $this->loadTemplate($templateId);

            return;
        }

        $this->newTemplate();
    }

    /** Mes gabarits (admin : tous), les plus récents en premier. */
    #[Computed]
    public function myTemplates(): Collection
    {
        return $this->scopedQuery()->orderByDesc('updated_at')->get();
    }

    /** Taxonomie des 4 familles de variables pour le panneau d'insertion. */
    #[Computed]
    public function variableCatalog(): array
    {
        return DiplomaRenderService::variableCatalog();
    }

    /** Index de l'élément sélectionné dans $elements (liaison wire:model directe côté vue). */
    #[Computed]
    public function selectedIndex(): ?int
    {
        foreach ($this->elements as $index => $element) {
            if (($element['id'] ?? null) === $this->selectedElementId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Aperçu HTML en direct avec des données d'EXEMPLE (jamais une vraie donnée
     * d'apprenant). Défensif : ne casse jamais l'éditeur en cas d'état invalide.
     */
    #[Computed]
    public function previewHtml(): string
    {
        try {
            $transientTemplate = new DiplomaTemplate(['layout_config' => ['elements' => $this->elements]]);

            return app(DiplomaRenderService::class)->renderHtml(
                $transientTemplate,
                new CertificateIssued(),
                sample: true,
            );
        } catch (\Throwable) {
            return '<div style="padding:1rem;color:#B91C1C;font-size:0.85rem;">Aperçu momentanément indisponible.</div>';
        }
    }

    /** Ajoute un élément au canevas (liste blanche de types). */
    public function addElement(string $kind, ?string $variableKey = null): void
    {
        if (! in_array($kind, ['text', 'qr', 'image'], true)) {
            return;
        }

        $isText = $kind === 'text';

        $element = [
            'id'          => uniqid('el_', true),
            'kind'        => $kind,
            'content'     => $isText ? ($variableKey !== null ? '{' . $variableKey . '}' : '') : null,
            'variable'    => $kind === 'image' ? $variableKey : ($isText ? $variableKey : null),
            'x'           => $isText ? 30.0 : 42.5,
            'y'           => $isText ? 40.0 : 42.5,
            'width'       => $isText ? 40.0 : 15.0,
            'height'      => $isText ? 10.0 : 15.0,
            'font_size'   => 20,
            'font_weight' => '400',
            'color'       => '#1A1D23',
            'align'       => 'center',
        ];

        $this->elements[]        = $element;
        $this->selectedElementId = $element['id'];
    }

    /** Appelée depuis Konva (JS) au dragend/transformend — positions en %. */
    public function updateElementGeometry(string $id, float $x, float $y, float $width, float $height): void
    {
        foreach ($this->elements as &$element) {
            if (($element['id'] ?? null) === $id) {
                $element['x']      = max(0.0, min(100.0, $x));
                $element['y']      = max(0.0, min(100.0, $y));
                $element['width']  = max(0.0, min(100.0, $width));
                $element['height'] = max(0.0, min(100.0, $height));
                break;
            }
        }
        unset($element);
    }

    /** Met à jour un champ de style/contenu (liste blanche stricte). */
    public function updateElementStyle(string $id, string $field, string $value): void
    {
        if (! in_array($field, ['content', 'font_size', 'font_weight', 'color', 'align'], true)) {
            return;
        }

        foreach ($this->elements as &$element) {
            if (($element['id'] ?? null) === $id) {
                $element[$field] = $field === 'font_size' ? max(6, min(120, (int) $value)) : $value;
                break;
            }
        }
        unset($element);
    }

    public function removeElement(string $id): void
    {
        $this->elements = array_values(array_filter($this->elements, static fn (array $e): bool => ($e['id'] ?? null) !== $id));

        if ($this->selectedElementId === $id) {
            $this->selectedElementId = null;
        }
    }

    public function selectElement(?string $id): void
    {
        $this->selectedElementId = $id;
    }

    /** Enregistre le gabarit (anti-IDOR : ré-autorise + re-résout scopé avant update). */
    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:120']);

        if ($this->elements === []) {
            session()->flash('diploma_editor_error', 'Le gabarit doit contenir au moins un élément.');

            return;
        }

        $userId = Auth::id();

        $template = $this->templateId !== null
            ? $this->scopedQuery()->findOrFail($this->templateId)
            : new DiplomaTemplate(['created_by' => $userId]);

        $template->name          = $this->name;
        $template->layout_config = ['elements' => $this->elements];
        $template->is_default    = $this->isDefault;

        DB::transaction(function () use ($template, $userId): void {
            $template->save();

            // Un seul gabarit par défaut par propriétaire — jamais deux défauts simultanés.
            if ($template->is_default) {
                DiplomaTemplate::query()
                    ->when(! $this->isManager(), fn ($q) => $q->where('created_by', $userId))
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }
        });

        $this->templateId = $template->id;

        session()->flash('diploma_editor_status', 'Gabarit enregistré.');
    }

    /** Charge un gabarit EXISTANT, scopé au propriétaire (anti-IDOR). */
    public function loadTemplate(int $id): void
    {
        $template = $this->scopedQuery()->findOrFail($id);

        $this->templateId         = $template->id;
        $this->name               = $template->name;
        $this->elements           = $template->elements();
        $this->isDefault          = $template->is_default;
        $this->selectedElementId  = null;
    }

    /** Réinitialise l'état à un nouveau gabarit vierge. */
    public function newTemplate(): void
    {
        $this->templateId        = null;
        $this->name              = 'Nouveau gabarit';
        $this->isDefault         = false;
        $this->selectedElementId = null;
        $this->elements          = [array_merge(['id' => uniqid('el_', true)], self::DEFAULT_ELEMENT)];
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /** Supprime un gabarit — UNIQUEMENT après confirmation inline, scopé (anti-IDOR). */
    public function delete(int $id): void
    {
        if ($this->confirmingDeleteId !== $id) {
            return;
        }

        $template   = $this->scopedQuery()->findOrFail($id);
        $wasCurrent = $this->templateId === $id;

        $template->delete();

        if ($wasCurrent) {
            $this->newTemplate();
        }

        $this->confirmingDeleteId = null;
        session()->flash('diploma_editor_status', 'Gabarit supprimé.');
    }

    /**
     * Requête owner-scopée (admin academy.manage voit tout).
     *
     * @return \Illuminate\Database\Eloquent\Builder<DiplomaTemplate>
     */
    private function scopedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = DiplomaTemplate::query();

        if (! $this->isManager()) {
            $query->where('created_by', Auth::id());
        }

        return $query;
    }

    private function isManager(): bool
    {
        $user = Auth::user();

        return $user !== null && $user->can('academy.manage');
    }

    public function render(): \Illuminate\View\View
    {
        return view('academy::livewire.diploma-template-editor');
    }
}
