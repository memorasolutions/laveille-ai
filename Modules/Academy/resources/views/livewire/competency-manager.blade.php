{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
     F22 - Référentiel de compétences (CRUD owner-scopé). Charte (tokens var(--sys-*) /
     x-core::button). Confirmations de suppression INLINE (jamais confirm()/alert() natif). --}}
<div>
    <div class="d-flex flex-column flex-lg-row gap-4">

        {{-- Formulaire création / édition --}}
        <div style="flex: 0 0 340px;">
            <form wire:submit="save"
                  style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px;">
                <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.05rem; margin: 0 0 14px;">
                    {{ $editingId ? 'Modifier la compétence' : 'Nouvelle compétence' }}
                </h2>

                <label for="comp-name" style="display:block; font-weight:600; font-size:0.9rem; margin-bottom:4px;">Nom</label>
                <input id="comp-name" type="text" wire:model="name" maxlength="160"
                       class="form-control" style="margin-bottom: 4px;">
                @error('name') <div style="color:#B91C1C; font-size:0.8rem;">{{ $message }}</div> @enderror

                <label for="comp-desc" style="display:block; font-weight:600; font-size:0.9rem; margin:12px 0 4px;">Description (optionnelle)</label>
                <textarea id="comp-desc" wire:model="description" rows="3" maxlength="2000" class="form-control"></textarea>
                @error('description') <div style="color:#B91C1C; font-size:0.8rem;">{{ $message }}</div> @enderror

                <label for="comp-scale" style="display:block; font-weight:600; font-size:0.9rem; margin:12px 0 4px;">Échelle de niveau (F14)</label>
                <select id="comp-scale" wire:model="scaleId" class="form-select">
                    <option value="">Barème binaire (Non atteint / Atteint)</option>
                    @foreach ($this->scales as $scale)
                        <option value="{{ $scale->id }}">{{ $scale->name }}</option>
                    @endforeach
                </select>
                @error('scaleId') <div style="color:#B91C1C; font-size:0.8rem;">{{ $message }}</div> @enderror

                <label for="comp-threshold" style="display:block; font-weight:600; font-size:0.9rem; margin:12px 0 4px;">Seuil de note (%) sur les items notés (optionnel)</label>
                <input id="comp-threshold" type="number" min="1" max="100" wire:model="passThreshold" class="form-control"
                       placeholder="Vide = acquisition par achèvement seul">
                @error('passThreshold') <div style="color:#B91C1C; font-size:0.8rem;">{{ $message }}</div> @enderror

                <label style="display:flex; align-items:center; gap:8px; margin:14px 0 0; font-size:0.9rem;">
                    <input type="checkbox" wire:model="isActive"> Compétence active
                </label>

                <div class="d-flex gap-2" style="margin-top: 16px;">
                    <x-core::button type="submit" variant="primary" size="sm">{{ $editingId ? 'Enregistrer' : 'Créer la compétence' }}</x-core::button>
                    @if ($editingId)
                        <x-core::button type="button" wire:click="resetForm" variant="ghost" size="sm">Annuler</x-core::button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Liste --}}
        <div style="flex: 1 1 auto;">
            <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.05rem; margin: 0 0 12px;">
                <span aria-hidden="true">🎯</span> Mes compétences ({{ $this->competencies->count() }})
            </h2>

            @forelse ($this->competencies as $competency)
                <div wire:key="comp-{{ $competency->id }}"
                     class="d-flex flex-wrap justify-content-between align-items-start gap-2"
                     style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:14px 16px; margin-bottom:10px;">
                    <div style="min-width: 0;">
                        <div style="font-weight:700; color: var(--sys-text-default, #1A1D23);">
                            {{ $competency->name }}
                            @unless ($competency->is_active)
                                <span style="font-size:0.75rem; color: var(--sys-text-muted, #6B7280);">(inactive)</span>
                            @endunless
                        </div>
                        @if ($competency->description)
                            <div style="font-size:0.85rem; color: var(--sys-text-muted, #6B7280); margin-top:2px;">{{ $competency->description }}</div>
                        @endif
                        <div style="font-size:0.8rem; color: var(--sys-text-muted, #6B7280); margin-top:6px;">
                            Niveau : {{ $competency->scale?->name ?? 'binaire' }}
                            · Seuil : {{ $competency->pass_threshold ? $competency->pass_threshold.' %' : 'achèvement' }}
                            · {{ $competency->links_count }} association(s)
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        @if ($confirmingDeleteId === $competency->id)
                            <x-core::button type="button" wire:click="delete({{ $competency->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                            <x-core::button type="button" wire:click="cancelDelete" variant="ghost" size="sm">Annuler</x-core::button>
                        @else
                            @include('core::components.action-menu', ['actions' => [
                                ['label' => 'Éditer', 'icon' => 'pencil', 'wireClick' => "edit({$competency->id})"],
                                ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmDelete({$competency->id})", 'danger' => true],
                            ]])
                        @endif
                    </div>
                </div>
            @empty
                <p style="color: var(--sys-text-muted, #6B7280);">Aucune compétence pour l'instant. Créez-en une pour commencer.</p>
            @endforelse
        </div>
    </div>
</div>
