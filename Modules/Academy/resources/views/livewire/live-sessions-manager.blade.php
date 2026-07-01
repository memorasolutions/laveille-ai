{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
     Séances en direct - GÉRANT (CRUD formateur). Charte tokens var(--sys-*) / x-core::button.
     Heures saisies en heure du Québec (America/Toronto), affichées Québec d'abord (UTC entre parenthèses). --}}
<div>
    {{-- Formulaire de création / édition --}}
    <div style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:16px; margin-bottom:16px;">
        <h3 style="font-family: var(--f-heading); font-size:1rem; margin:0 0 12px;">
            {{ $editingId ? 'Modifier la séance' : 'Planifier une séance en direct' }}
        </h3>

        <form wire:submit="save">
            <div class="mb-3">
                <label for="ls-title" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Titre de la séance</label>
                <input type="text" id="ls-title" wire:model="title" class="form-control" maxlength="200" required>
                @error('title') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
            </div>

            <div class="mb-3">
                <label for="ls-desc" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Description (facultative)</label>
                <textarea id="ls-desc" wire:model="description" class="form-control" rows="2" maxlength="2000"></textarea>
                @error('description') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="ls-provider" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Plateforme</label>
                    <select id="ls-provider" wire:model="provider" class="form-select">
                        {{-- Google Meet en tête (fournisseur par défaut). --}}
                        @foreach ($this->providerLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('provider') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
                </div>

                @if ($this->cohorts->isNotEmpty())
                    <div class="col-md-8">
                        <label for="ls-cohort" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Cohorte (facultatif)</label>
                        <select id="ls-cohort" wire:model="cohort_id" class="form-select">
                            <option value="">Tous les inscrits</option>
                            @foreach ($this->cohorts as $cohort)
                                <option value="{{ $cohort->id }}">{{ $cohort->name }}</option>
                            @endforeach
                        </select>
                        @error('cohort_id') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="mb-3">
                <label for="ls-url" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Lien de la réunion</label>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <input type="url" id="ls-url" wire:model="join_url" class="form-control" style="flex:1 1 320px;"
                        placeholder="https://meet.google.com/xxx-xxxx-xxx" maxlength="2048" required>
                    {{-- Le formateur crée la salle sous SON compte Google, copie le lien, le colle ici.
                         TODO phase 2 : auto-création du lien Meet via Google Calendar API (fin du copier-coller). --}}
                    <a href="https://meet.google.com/new" target="_blank" rel="noopener"
                       class="btn btn-outline-secondary btn-sm" style="white-space:nowrap;">
                        <span aria-hidden="true">➕</span> Créer une réunion Google Meet
                    </a>
                </div>
                <p style="margin:6px 0 0; font-size:0.8rem; color: var(--sys-text-muted, #6B7280);">
                    Crée la salle, copie le lien, colle-le ici.
                </p>
                @error('join_url') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="ls-start" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Début (heure du Québec)</label>
                    <input type="datetime-local" id="ls-start" wire:model="starts_at" class="form-control" required>
                    @error('starts_at') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
                </div>
                <div class="col-md-6">
                    <label for="ls-end" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Fin (facultative, heure du Québec)</label>
                    <input type="datetime-local" id="ls-end" wire:model="ends_at" class="form-control">
                    @error('ends_at') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <x-core::button type="submit" variant="primary" size="sm">
                    {{ $editingId ? 'Enregistrer' : 'Planifier la séance' }}
                </x-core::button>
                @if ($editingId)
                    <x-core::button type="button" variant="ghost" size="sm" wire:click="newSession">Annuler</x-core::button>
                @endif
            </div>
        </form>
    </div>

    {{-- Liste des séances planifiées --}}
    <div>
        <h3 style="font-family: var(--f-heading); font-size:1rem; margin:0 0 10px;">Séances planifiées</h3>

        @if ($this->sessions->isEmpty())
            <p style="color: var(--sys-text-muted, #6B7280); margin:0;">Aucune séance planifiée pour l'instant.</p>
        @else
            <ul class="list-unstyled" style="margin:0;">
                @foreach ($this->sessions as $session)
                    @php
                        $qc = $session->starts_at->copy()->setTimezone('America/Toronto');
                        $utc = $session->starts_at->copy()->utc();
                    @endphp
                    <li style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:12px 14px; margin-bottom:10px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <p style="margin:0 0 4px; font-weight:700;">{{ $session->title }}</p>
                                <p style="margin:0 0 2px; font-size:0.85rem; color: var(--sys-text-muted, #6B7280);">
                                    {{ $qc->translatedFormat('D d M Y \à H\hi') }} Québec ({{ $utc->format('H:i') }} UTC)
                                    · {{ $session->providerLabel() }}
                                    @if ($session->cohort_id) · Cohorte @endif
                                    @unless($session->isUpcoming()) · <span style="color:#92400E;">passée</span> @endunless
                                </p>
                                <a href="{{ $session->join_url }}" target="_blank" rel="noopener"
                                   style="font-size:0.85rem; color: var(--sys-action-primary, #064E5A); text-decoration: underline; word-break: break-all;">
                                    {{ $session->join_url }}
                                </a>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if ($confirmingDeleteId === $session->id)
                                    {{-- Confirmation inline (modale du thème / boutons), jamais de popup navigateur natif. --}}
                                    <span style="font-size: 0.82rem; font-weight: 600;">Supprimer ?</span>
                                    <x-core::button type="button" variant="danger" size="sm" wire:click="deleteSession({{ $session->id }})">Confirmer</x-core::button>
                                    <x-core::button type="button" variant="ghost" size="sm" wire:click="cancelDelete">Annuler</x-core::button>
                                @else
                                    <x-core::button type="button" variant="ghost" size="sm" wire:click="edit({{ $session->id }})">Modifier</x-core::button>
                                    <x-core::button type="button" variant="ghost" size="sm"
                                        wire:click="confirmDelete({{ $session->id }})"
                                        aria-label="Supprimer la séance « {{ $session->title }} »">Supprimer</x-core::button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
