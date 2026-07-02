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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\DiplomaBackground;
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
    use WithFileUploads;

    /**
     * Phase 3 — disjoncteurs DoS/abus : sauvegarde de gabarit ET téléversement
     * d'arrière-plan, même seuil que les actions de formateur similaires
     * (AiAuthoringModal::AI_RATE_LIMIT_MAX / TranslateFieldModal), clé par
     * utilisateur ET par action.
     */
    private const SAVE_RATE_LIMIT_MAX = 20;

    private const SAVE_RATE_LIMIT_DECAY_SECONDS = 3600;

    private const BACKGROUND_UPLOAD_RATE_LIMIT_MAX = 20;

    private const BACKGROUND_UPLOAD_RATE_LIMIT_DECAY_SECONDS = 3600;

    /** Taille maximale (Ko) d'une image d'arrière-plan (5 Mo). */
    private const BACKGROUND_MAX_KB = 5120;

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

    /** Phase 3 — bibliothèque d'arrière-plans réutilisables. */
    public ?int $backgroundId = null;

    public string $newBackgroundName = 'Arrière-plan';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null Upload temporaire Livewire. */
    public $newBackgroundFile = null;

    public ?int $confirmingDeleteBackgroundId = null;

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

    /** Phase 3 — mes arrière-plans réutilisables (admin : tous), les plus récents en premier. */
    #[Computed]
    public function myBackgrounds(): Collection
    {
        return $this->backgroundScopedQuery()->orderByDesc('created_at')->get();
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
            // Phase 3 : background_id inclus pour que l'aperçu reflète l'arrière-plan sélectionné.
            $transientTemplate = new DiplomaTemplate([
                'layout_config' => ['elements' => $this->elements],
                'background_id' => $this->backgroundId,
            ]);

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

        // Phase 3 — disjoncteur DoS/abus sur la sauvegarde de gabarit.
        if ($this->tooManySaveAttempts()) {
            session()->flash('diploma_editor_error', "Vous avez atteint la limite d'enregistrements de gabarits pour cette heure (20/heure). Réessayez plus tard.");

            return;
        }

        $userId = Auth::id();

        $template = $this->templateId !== null
            ? $this->scopedQuery()->findOrFail($this->templateId)
            : new DiplomaTemplate(['created_by' => $userId]);

        // Phase 3 — anti-IDOR : l'arrière-plan choisi doit appartenir à l'utilisateur courant
        // (ou être visible par l'admin academy.manage) ; sinon on retombe silencieusement à null.
        $template->name          = $this->name;
        $template->layout_config = ['elements' => $this->elements];
        $template->is_default    = $this->isDefault;
        $template->background_id = $this->resolveOwnedBackgroundId($this->backgroundId, $userId);

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
        $this->backgroundId       = $template->background_id;
    }

    /** Réinitialise l'état à un nouveau gabarit vierge. */
    public function newTemplate(): void
    {
        $this->templateId        = null;
        $this->name              = 'Nouveau gabarit';
        $this->isDefault         = false;
        $this->selectedElementId = null;
        $this->elements          = [array_merge(['id' => uniqid('el_', true)], self::DEFAULT_ELEMENT)];
        $this->backgroundId      = null;
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

    // ─────────────────────────────────────────────────────────────────────
    // Phase 3 — bibliothèque d'arrière-plans réutilisables (owner-scopée,
    // anti-IDOR strict, rate-limitée). Une image est indépendante des
    // gabarits qui l'utilisent : sa suppression fait retomber CE composant
    // sur $backgroundId = null (le gabarit persisté ne sera mis à jour
    // qu'au prochain save(), même esprit que la suppression d'un gabarit
    // assigné à un cours — nullOnDelete côté FK).
    // ─────────────────────────────────────────────────────────────────────

    /** Sélectionne un arrière-plan pour le gabarit courant — anti-IDOR (silencieux). */
    public function selectBackground(?int $id): void
    {
        if ($id === null) {
            $this->backgroundId = null;

            return;
        }

        $owned               = $this->backgroundScopedQuery()->where('id', $id)->exists();
        $this->backgroundId  = $owned ? $id : null;
    }

    /** Téléverse un nouvel arrière-plan dans la bibliothèque (owner-scopée, rate-limitée). */
    public function uploadBackground(): void
    {
        if ($this->tooManyBackgroundUploadAttempts()) {
            session()->flash('diploma_editor_error', "Vous avez atteint la limite de téléversements d'arrière-plans pour cette heure (20/heure). Réessayez plus tard.");

            return;
        }

        $this->validate([
            'newBackgroundName' => ['required', 'string', 'max:120'],
            'newBackgroundFile' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::BACKGROUND_MAX_KB],
        ]);

        try {
            $background = DiplomaBackground::create([
                'name'       => $this->newBackgroundName,
                'created_by' => Auth::id(),
            ]);

            $background->addMedia($this->newBackgroundFile)
                ->usingFileName($this->safeBackgroundFileName($this->newBackgroundFile))
                ->toMediaCollection('background');
        } catch (\Throwable) {
            session()->flash('diploma_editor_error', "Le téléversement de l'arrière-plan a échoué.");

            return;
        }

        $this->backgroundId       = $background->id;
        $this->newBackgroundFile  = null;
        $this->newBackgroundName  = 'Arrière-plan';
        session()->flash('diploma_editor_status', 'Arrière-plan téléversé.');
    }

    public function confirmDeleteBackground(int $id): void
    {
        $this->confirmingDeleteBackgroundId = $id;
    }

    public function cancelDeleteBackground(): void
    {
        $this->confirmingDeleteBackgroundId = null;
    }

    /** Supprime un arrière-plan — UNIQUEMENT après confirmation inline, scopé (anti-IDOR). */
    public function deleteBackground(int $id): void
    {
        if ($this->confirmingDeleteBackgroundId !== $id) {
            return;
        }

        $background = $this->backgroundScopedQuery()->findOrFail($id);

        if ($this->backgroundId === $id) {
            $this->backgroundId = null;
        }

        $background->delete();

        $this->confirmingDeleteBackgroundId = null;
        session()->flash('diploma_editor_status', 'Arrière-plan supprimé.');
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

    /**
     * Requête owner-scopée sur la bibliothèque d'arrière-plans (Phase 3),
     * même esprit que scopedQuery() — admin academy.manage voit tout.
     *
     * @return \Illuminate\Database\Eloquent\Builder<DiplomaBackground>
     */
    private function backgroundScopedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = DiplomaBackground::query();

        if (! $this->isManager()) {
            $query->where('created_by', Auth::id());
        }

        return $query;
    }

    /**
     * Anti-IDOR : résout l'arrière-plan choisi UNIQUEMENT s'il appartient à
     * l'utilisateur courant (ou est visible par l'admin academy.manage).
     * Jamais d'erreur affichée pour un id forgé — retombe silencieusement à null.
     */
    private function resolveOwnedBackgroundId(?int $backgroundId, ?int $userId): ?int
    {
        if ($backgroundId === null) {
            return null;
        }

        $owned = DiplomaBackground::query()
            ->where('id', $backgroundId)
            ->when(! $this->isManager(), fn ($q) => $q->where('created_by', $userId))
            ->exists();

        return $owned ? $backgroundId : null;
    }

    /**
     * Disjoncteur DoS/abus sur la SAUVEGARDE de gabarit — même pattern que
     * AiAuthoringModal::tooManyAiRequests(), clé par utilisateur.
     */
    private function tooManySaveAttempts(): bool
    {
        $key = 'diploma-template-save:' . (string) Auth::id();

        if (RateLimiter::tooManyAttempts($key, self::SAVE_RATE_LIMIT_MAX)) {
            return true;
        }

        RateLimiter::hit($key, self::SAVE_RATE_LIMIT_DECAY_SECONDS);

        return false;
    }

    /**
     * Disjoncteur DoS/abus sur le TÉLÉVERSEMENT d'arrière-plan — même pattern
     * que tooManySaveAttempts(), clé par utilisateur ET par action.
     */
    private function tooManyBackgroundUploadAttempts(): bool
    {
        $key = 'diploma-background-upload:' . (string) Auth::id();

        if (RateLimiter::tooManyAttempts($key, self::BACKGROUND_UPLOAD_RATE_LIMIT_MAX)) {
            return true;
        }

        RateLimiter::hit($key, self::BACKGROUND_UPLOAD_RATE_LIMIT_DECAY_SECONDS);

        return false;
    }

    /**
     * Nom de fichier stocké non devinable (même pattern que
     * HandlesH5p::safeFileName() / HandlesItemMedia — dupliqué localement,
     * ce composant n'inclut pas ces traits couplés à CourseEditor).
     */
    private function safeBackgroundFileName(mixed $file): string
    {
        $ext  = strtolower((string) $file->getClientOriginalExtension());
        $safe = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'bin';

        return Str::slug('arriere-plan') . '-' . Str::random(16) . '.' . $safe;
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
