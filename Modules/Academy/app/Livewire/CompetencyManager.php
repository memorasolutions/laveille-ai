<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - RÉFÉRENTIEL de compétences (CRUD owner-scopé). Un formateur gère SES propres
 * compétences ; l'admin (academy.manage) voit tout.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - OWNER-SCOPED : owner_id TOUJOURS forcé = auth()->id() à la création (jamais du
 *    navigateur). L'admin (academy.manage) est le SEUL à voir/éditer toutes les
 *    compétences (écart explicite au scoping owner).
 *  - À CHAQUE mutation on RE-RÉSOUT la compétence SCOPÉE à l'utilisateur (resolve) :
 *    une compétence d'un autre owner → ModelNotFound (anti-IDOR), aucune écriture.
 *  - L'échelle F14 choisie (scale_id) est validée contre MES échelles (ou système) ;
 *    une échelle d'un autre owner est refusée (anti-IDOR).
 *  - Suppression : confirmation INLINE à 2 temps (jamais confirm()/alert() natif).
 *    Les liens cours/items partent en cascade (FK), aucun orphelin ; les données
 *    d'achèvement/notes des étudiants ne sont JAMAIS touchées (acquisition dérivée).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\Competency;
use Modules\Academy\Models\Scale;

class CompetencyManager extends Component
{
    /** Id de la compétence en cours d'édition (null = création). Verrouillé : non modifiable par le navigateur. */
    #[Locked]
    public ?int $editingId = null;

    public string $name = '';
    public ?string $description = null;
    public ?int $scaleId = null;
    /** Seuil de note appliqué aux items notés liés (1..100) ; vide = achèvement seul. */
    public ?int $passThreshold = null;
    public bool $isActive = true;

    /** Compétence dont la suppression est en attente de confirmation (inline 2 temps). Verrouillé : non modifiable par le navigateur. */
    #[Locked]
    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        // Autorisation d'ENTRÉE : formateur OU admin. Chaque mutation est ré-autorisée
        // + owner-scopée plus bas (la vraie garde est SERVEUR, pas cette porte).
        abort_unless(
            Auth::check() && (Auth::user()->can('academy.manage') || Auth::user()->hasRole('instructor')),
            403
        );
    }

    /** L'utilisateur courant voit-il TOUTES les compétences (admin) ? */
    private function isManager(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    /**
     * Mes compétences (ou toutes, si admin). Owner-scopé strict pour un formateur.
     *
     * @return \Illuminate\Support\Collection<int, Competency>
     */
    #[Computed]
    public function competencies()
    {
        $query = Competency::query()->with('scale')->withCount('links')->orderBy('name');

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * Échelles F14 disponibles pour le niveau : les MIENNES + les échelles système
     * (owner_id null). Jamais celles d'un autre formateur (anti-IDOR).
     *
     * @return \Illuminate\Support\Collection<int, Scale>
     */
    #[Computed]
    public function scales()
    {
        return Scale::query()
            ->where(function ($q): void {
                $q->where('owner_id', Auth::id())->orWhereNull('owner_id');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Re-résout une compétence SCOPÉE à l'utilisateur (anti-IDOR). Admin = toutes ;
     * formateur = uniquement les siennes. Lève ModelNotFound sinon.
     */
    private function resolve(int $id): Competency
    {
        $query = Competency::query()->whereKey($id);

        if (! $this->isManager()) {
            $query->where('owner_id', Auth::id());
        }

        return $query->firstOrFail();
    }

    /** Validation : scale_id contraint à MES échelles (ou système). */
    private function rules(): array
    {
        $scaleIds = $this->scales->pluck('id')->all();

        return [
            'name'          => ['required', 'string', 'max:160'],
            'description'   => ['nullable', 'string', 'max:2000'],
            'scaleId'       => ['nullable', 'integer', Rule::in($scaleIds)],
            'passThreshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'isActive'      => ['boolean'],
        ];
    }

    public function edit(int $id): void
    {
        $c = $this->resolve($id);

        $this->editingId     = $c->id;
        $this->name          = (string) $c->name;
        $this->description   = $c->description;
        $this->scaleId       = $c->scale_id;
        $this->passThreshold = $c->pass_threshold;
        $this->isActive      = (bool) $c->is_active;
        $this->confirmingDeleteId = null;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'scaleId', 'passThreshold']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        // owner_id : à la création TOUJOURS = auth (jamais du client). En édition,
        // l'owner est immuable (la compétence est re-résolue scopée).
        if ($this->editingId !== null) {
            $c = $this->resolve($this->editingId);
        } else {
            $c = new Competency();
            $c->owner_id = (int) Auth::id();
        }

        $c->name           = trim($data['name']);
        $c->slug           = $this->uniqueSlug($c->name, $c->owner_id, $c->id);
        $c->description    = $data['description'] !== null ? trim((string) $data['description']) : null;
        $c->scale_id       = $data['scaleId'] ?? null;
        $c->pass_threshold = $data['passThreshold'] ?? null;
        $c->is_active      = (bool) $data['isActive'];

        // Protection contre la collision de slug en accès concurrent (race condition).
        try {
            $c->save();
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $this->addError('name', 'Ce nom de compétence produit un slug déjà utilisé. Choisissez un nom légèrement différent.');

            return;
        }

        $this->resetForm();
        $this->dispatch('competency-saved');
    }

    public function confirmDelete(int $id): void
    {
        // S'assure que la compétence M'appartient avant d'armer la confirmation (anti-IDOR).
        $this->resolve($id);
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(int $id): void
    {
        $c = $this->resolve($id);
        $c->delete(); // les liens cours/items partent en cascade (FK) ; achèvements/notes intacts.

        if ($this->editingId === $id) {
            $this->resetForm();
        }
        $this->confirmingDeleteId = null;
        $this->dispatch('competency-deleted');
    }

    /**
     * Slug unique PAR propriétaire (suffixe -2, -3… en cas de collision). Exclut la
     * compétence courante (édition). Base « competence » si le libellé ne produit rien.
     */
    private function uniqueSlug(string $name, ?int $ownerId, ?int $ignoreId): string
    {
        $base = Competency::slugify($name) ?: 'competence';
        $slug = $base;
        $i    = 2;

        while (
            Competency::query()
                ->where('owner_id', $ownerId)
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function render()
    {
        return view('academy::livewire.competency-manager');
    }
}
