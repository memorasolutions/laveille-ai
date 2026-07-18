<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php($course = $this->course)
<div style="display: flex; flex-direction: column; gap: 28px;">

    {{-- ───────────────────────── Message de succès ───────────────────────── --}}
    @if (session('academy_roster_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; font-weight: 600;">
            {{ session('academy_roster_status') }}
        </div>
    @endif

    {{-- ───────────────────────── Annonces aux inscrits (D3) ───────────────────────── --}}
    @can('manageEnrollments', $course)
        <section aria-labelledby="roster-announcements"
                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
            <h2 id="roster-announcements" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.25rem;">
                Annonces aux inscrits
            </h2>
            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 18px;">
                Les annonces publiées s'affichent dans l'espace des personnes inscrites à ce cours. Mise en forme markdown acceptée (HTML brut retiré par sécurité).
            </p>

            {{-- Composer / éditer une annonce --}}
            <form wire:submit="saveAnnouncement(false)" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="announcement-title" style="font-weight: 600; font-size: 0.85rem;">Titre</label>
                    <input id="announcement-title" type="text" wire:model="announcementTitle" maxlength="160" autocomplete="off" placeholder="Ex. : nouvelle leçon disponible"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('announcementTitle') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="announcement-body" style="font-weight: 600; font-size: 0.85rem;">Message</label>
                    <textarea id="announcement-body" wire:model="announcementBody" rows="4" maxlength="5000" placeholder="Votre message aux personnes inscrites…"
                              style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                    @error('announcementBody') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <x-core::button type="submit" variant="secondary" size="sm">
                        {{ $editingAnnouncement ? 'Enregistrer' : 'Enregistrer comme brouillon' }}
                    </x-core::button>
                    <x-core::button type="button" wire:click="saveAnnouncement(true)" variant="primary" size="sm">
                        Publier maintenant
                    </x-core::button>
                    @if ($editingAnnouncement)
                        <x-core::button type="button" wire:click="resetAnnouncementForm" variant="ghost" size="sm">Annuler l'édition</x-core::button>
                    @endif
                </div>
            </form>

            {{-- Liste des annonces existantes --}}
            @if ($this->announcements->isNotEmpty())
                <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                    @foreach ($this->announcements as $announcement)
                        <li wire:key="announcement-{{ $announcement->id }}"
                            style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.5rem); padding: 14px 16px;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div style="flex: 1 1 260px; min-width: 200px;">
                                    <h3 style="font-family: var(--f-heading); font-size: 1rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 4px;">
                                        {{ $announcement->title }}
                                    </h3>
                                    @if ($announcement->published_at)
                                        <span style="display: inline-block; font-size: 0.72rem; font-weight: 700; color: #166534; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 999px; padding: 2px 10px;">
                                            Publiée le {{ $announcement->published_at->timezone('America/Toronto')->format('Y-m-d H\hi') }}
                                        </span>
                                    @else
                                        <span style="display: inline-block; font-size: 0.72rem; font-weight: 700; color: #92400E; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 999px; padding: 2px 10px;">
                                            Brouillon
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @if ($confirmingAnnouncementRemoval === $announcement->id)
                                        <span style="font-size: 0.82rem; font-weight: 600;">Supprimer ?</span>
                                        <x-core::button type="button" wire:click="deleteAnnouncement({{ $announcement->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                        <x-core::button type="button" wire:click="cancelAnnouncementRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                    @else
                                        @include('core::components.action-menu', ['actions' => [
                                            ['label' => 'Modifier', 'icon' => 'pencil', 'wireClick' => "editAnnouncement({$announcement->id})"],
                                            $announcement->published_at
                                                ? ['label' => 'Repasser en brouillon', 'icon' => 'undo-2', 'wireClick' => "unpublishAnnouncement({$announcement->id})"]
                                                : ['label' => 'Publier', 'icon' => 'send', 'wireClick' => "publishAnnouncement({$announcement->id})"],
                                            ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmAnnouncementRemoval({$announcement->id})", 'danger' => true],
                                        ]])
                                    @endif
                                </div>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--sys-text-default, #1A1D23); margin-top: 8px;">
                                {!! $announcement->renderedBody() !!}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune annonce pour l'instant.</p>
            @endif
        </section>
    @endcan

    {{-- ───────────────────────── Inscriptions (roster) ───────────────────────── --}}
    @can('manageEnrollments', $course)
        <section aria-labelledby="roster-enrollments"
                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
            <h2 id="roster-enrollments" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.25rem;">
                Inscriptions
            </h2>
            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 18px;">
                Inscrivez une personne par son courriel. Son compte doit déjà exister.
            </p>

            {{-- Inscrire un utilisateur par courriel --}}
            <form wire:submit="enrollByEmail"
                  style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 22px;">
                <div style="flex: 1 1 280px; display: flex; flex-direction: column; gap: 6px;">
                    <label for="roster-enroll-email" style="font-weight: 600; font-size: 0.85rem;">Courriel de la personne à inscrire</label>
                    <input id="roster-enroll-email" type="email" wire:model="enrollEmail" autocomplete="off" placeholder="personne@exemple.ca"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('enrollEmail') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div><x-core::button type="submit" variant="primary" size="sm">Inscrire</x-core::button></div>
            </form>

            {{-- ─────────── Importer des inscriptions (CSV) ─────────── --}}
            <div style="border-top: 1px dashed #E5E7EB; padding-top: 18px; margin-bottom: 22px;">
                <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.02rem;">
                    Importer des inscriptions (CSV)
                </h3>
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">
                    Une ligne par courriel (colonne <code>email</code>, en-tête facultatif). Seuls les comptes déjà existants sont inscrits ; un courriel sans compte est listé sans qu'aucun compte soit créé. Maximum 1000 lignes.
                </p>
                <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px;">
                    Format attendu :
                    <a download="modele-inscriptions.csv"
                       href="data:text/csv;charset=utf-8,email%2Crole%0Apersonne1%40exemple.ca%2Cstudent%0Apersonne2%40exemple.ca%2Cstudent"
                       style="color: var(--sys-action-primary, #0F766E); font-weight: 600;">
                        télécharger un modèle CSV
                    </a>
                </p>

                <form wire:submit="importCsv"
                      style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px;">
                    <div style="flex: 1 1 280px; display: flex; flex-direction: column; gap: 6px;">
                        <label for="roster-csv-file" style="font-weight: 600; font-size: 0.85rem;">Fichier CSV (max 2 Mo)</label>
                        <input id="roster-csv-file" type="file" accept=".csv,text/csv,text/plain" wire:model="csvFile"
                               style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); background: #FFFFFF;">
                        <div wire:loading wire:target="csvFile" style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">Téléversement en cours…</div>
                        @error('csvFile') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                    <div><x-core::button type="submit" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="importCsv">Importer</x-core::button></div>
                </form>

                {{-- Rapport d'import (a11y : role=status) --}}
                @if ($importReport !== null)
                    <div role="status" aria-live="polite"
                         style="margin-top: 14px; border: 1px solid #BFDBFE; background: #EFF6FF; color: #1E3A8A; border-radius: var(--sys-radius-md, 0.75rem); padding: 14px 16px; font-size: 0.88rem;">
                        <strong>Résultat de l'import</strong>
                        <ul style="margin: 8px 0 0; padding-left: 20px;">
                            <li>{{ $importReport['enrolled'] }} inscription(s) créée(s)</li>
                            <li>{{ $importReport['already'] }} déjà inscrit(s) (aucune modification)</li>
                            <li>{{ count($importReport['unknown_emails']) }} courriel(s) inconnu(s) — aucun compte créé</li>
                            <li>{{ $importReport['invalid'] }} ligne(s) ignorée(s) (courriel invalide)</li>
                        </ul>
                        @if (! empty($importReport['unknown_emails']))
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; font-weight: 600;">Voir les courriels inconnus ({{ count($importReport['unknown_emails']) }})</summary>
                                <ul style="margin: 8px 0 0; padding-left: 20px;">
                                    @foreach ($importReport['unknown_emails'] as $unknown)
                                        <li>{{ $unknown }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                        <div style="margin-top: 12px;">
                            <x-core::button type="button" wire:click="clearImportReport" variant="ghost" size="sm">Fermer le rapport</x-core::button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ─────────── Cohortes / groupes d'apprenants ─────────── --}}
            <div style="border-top: 1px dashed #E5E7EB; padding-top: 18px; margin-bottom: 22px;">
                <h3 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.02rem;">
                    Cohortes / groupes
                </h3>
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px;">
                    Regroupez des inscrits en cohortes (ex. : un groupe, une session). Un membre doit être inscrit à ce cours. Supprimer une cohorte ne désinscrit personne.
                </p>

                {{-- Créer une cohorte --}}
                <form wire:submit="createCohort"
                      style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 18px;">
                    <div style="flex: 1 1 260px; display: flex; flex-direction: column; gap: 6px;">
                        <label for="cohort-name" style="font-weight: 600; font-size: 0.85rem;">Nom de la nouvelle cohorte</label>
                        <input id="cohort-name" type="text" wire:model="cohortName" autocomplete="off" placeholder="Groupe A — automne 2026"
                               style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        @error('cohortName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                    </div>
                    <div><x-core::button type="submit" variant="primary" size="sm">Créer la cohorte</x-core::button></div>
                </form>

                {{-- Affecter un inscrit à une cohorte --}}
                @if ($this->cohorts->isNotEmpty() && $this->activeEnrollees->isNotEmpty())
                    <form wire:submit="assignToCohort"
                          style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 18px;">
                        <div style="flex: 1 1 220px; display: flex; flex-direction: column; gap: 6px;">
                            <label for="assign-cohort" style="font-weight: 600; font-size: 0.85rem;">Cohorte</label>
                            <select id="assign-cohort" wire:model="assignCohortId"
                                    style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); background: #FFFFFF;">
                                <option value="">— Choisir —</option>
                                @foreach ($this->cohorts as $cohort)
                                    <option value="{{ $cohort->id }}">{{ $cohort->name }}</option>
                                @endforeach
                            </select>
                            @error('assignCohortId') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        </div>
                        <div style="flex: 1 1 220px; display: flex; flex-direction: column; gap: 6px;">
                            <label for="assign-enrollee" style="font-weight: 600; font-size: 0.85rem;">Personne inscrite</label>
                            <select id="assign-enrollee" wire:model="assignEnrollmentUserId"
                                    style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); background: #FFFFFF;">
                                <option value="">— Choisir —</option>
                                @foreach ($this->activeEnrollees as $enrollee)
                                    <option value="{{ $enrollee->user_id }}">{{ $enrollee->user?->name ?? $enrollee->user?->email ?? '-' }}</option>
                                @endforeach
                            </select>
                            @error('assignEnrollmentUserId') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        </div>
                        <div><x-core::button type="submit" variant="primary" size="sm">Affecter</x-core::button></div>
                    </form>
                @endif

                {{-- Liste des cohortes + leurs membres --}}
                @if ($this->cohorts->isNotEmpty())
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach ($this->cohorts as $cohort)
                            <div wire:key="cohort-{{ $cohort->id }}"
                                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 14px 16px;">
                                <div class="d-flex flex-wrap align-items-center" style="gap: 10px; justify-content: space-between;">
                                    @if ($renamingCohort === $cohort->id)
                                        <form wire:submit="renameCohort" class="d-flex flex-wrap align-items-end" style="gap: 8px; flex: 1 1 280px;">
                                            <div style="flex: 1 1 200px; display: flex; flex-direction: column; gap: 4px;">
                                                <label for="rename-cohort-{{ $cohort->id }}" style="font-weight: 600; font-size: 0.82rem;">Nouveau nom</label>
                                                <input id="rename-cohort-{{ $cohort->id }}" type="text" wire:model="renameCohortName" autocomplete="off"
                                                       style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @error('renameCohortName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                            </div>
                                            <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                                            <x-core::button type="button" wire:click="cancelRenameCohort" variant="ghost" size="sm">Annuler</x-core::button>
                                        </form>
                                    @else
                                        <strong style="font-size: 0.95rem;">{{ $cohort->name }}
                                            <span style="font-weight: 400; color: var(--sys-text-muted, #6B7280); font-size: 0.82rem;">({{ $cohort->members->count() }} membre(s))</span>
                                        </strong>
                                        <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                                            @if ($confirmingCohortRemoval === $cohort->id)
                                                <span style="font-size: 0.82rem; font-weight: 600;">Supprimer cette cohorte ?</span>
                                                <x-core::button type="button" wire:click="deleteCohort({{ $cohort->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelCohortRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                            @else
                                                @include('core::components.action-menu', ['actions' => [
                                                    ['label' => 'Renommer', 'icon' => 'pencil', 'wireClick' => "startRenameCohort({$cohort->id})"],
                                                    ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmCohortRemoval({$cohort->id})", 'danger' => true],
                                                ]])
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @if ($cohort->members->isNotEmpty())
                                    <ul style="margin: 10px 0 0; padding-left: 0; list-style: none; display: flex; flex-direction: column; gap: 4px;">
                                        @foreach ($cohort->members as $member)
                                            <li wire:key="cohort-{{ $cohort->id }}-member-{{ $member->id }}"
                                                class="d-flex flex-wrap align-items-center" style="gap: 8px; font-size: 0.86rem;">
                                                <span>{{ $member->name ?? $member->email }}</span>
                                                <x-core::button type="button" wire:click="removeFromCohort({{ $cohort->id }}, {{ $member->id }})" variant="ghost" size="sm"
                                                                aria-label="Retirer {{ $member->name ?? $member->email }} de la cohorte {{ $cohort->name }}">Retirer</x-core::button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p style="margin: 8px 0 0; font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Aucun membre.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune cohorte pour l'instant.</p>
                @endif
            </div>

            {{-- Filtre des inscrits par cohorte --}}
            @if ($this->cohorts->isNotEmpty())
                <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; margin-bottom: 14px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="cohort-filter" style="font-weight: 600; font-size: 0.85rem;">Filtrer les inscrits par cohorte</label>
                        <select id="cohort-filter" wire:model.live="cohortFilter"
                                style="padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); background: #FFFFFF;">
                            <option value="">Tous les inscrits</option>
                            @foreach ($this->cohorts as $cohort)
                                <option value="{{ $cohort->id }}">{{ $cohort->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            {{-- Liste des inscrits --}}
            @if ($this->enrollments->isNotEmpty())
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <caption class="visually-hidden" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);">
                            Liste des personnes inscrites à ce cours
                        </caption>
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Personne</th>
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Courriel</th>
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Statut</th>
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->enrollments as $enrollment)
                                <tr wire:key="enrollment-{{ $enrollment->id }}" style="border-bottom: 1px solid #F3F4F6;">
                                    <td style="padding: 10px;">{{ $enrollment->user?->name ?? '-' }}</td>
                                    <td style="padding: 10px;">{{ $enrollment->user?->email ?? '-' }}</td>
                                    <td style="padding: 10px;">{{ $this->statusLabel($enrollment->status) }}</td>
                                    <td style="padding: 10px;">
                                        @if ($enrollment->status === 'cancelled')
                                            <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Inscription annulée</span>
                                        @elseif ($confirmingEnrollmentRemoval === $enrollment->id)
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span style="font-size: 0.82rem; font-weight: 600;">Désinscrire ?</span>
                                                <x-core::button type="button" wire:click="cancelEnrollment({{ $enrollment->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelEnrollmentRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                            </div>
                                        @else
                                            <x-core::button type="button" wire:click="confirmEnrollmentRemoval({{ $enrollment->id }})" variant="ghost" size="sm"
                                                            title="Désinscrire cette personne" aria-label="Désinscrire {{ $enrollment->user?->name ?? 'cette personne' }}">Désinscrire</x-core::button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune inscription pour l'instant.</p>
            @endif
        </section>
    @endcan

    {{-- ───────────────────────── Équipe : rôles de cours (owner/admin uniquement) ───────────────────────── --}}
    @can('manageRoles', $course)
        <section aria-labelledby="roster-team"
                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
            <h2 id="roster-team" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.25rem;">
                Équipe du cours
            </h2>
            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 18px;">
                Attribuez un rôle (formateur, assistant ou éditeur) à une personne par son courriel. Le rôle « propriétaire » ne s'attribue ni ne se retire ici.
            </p>

            {{-- Ajouter un rôle par courriel --}}
            <form wire:submit="addRoleByEmail"
                  style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin-bottom: 22px;">
                <div style="flex: 1 1 260px; display: flex; flex-direction: column; gap: 6px;">
                    <label for="roster-role-email" style="font-weight: 600; font-size: 0.85rem;">Courriel de la personne</label>
                    <input id="roster-role-email" type="email" wire:model="roleEmail" autocomplete="off" placeholder="personne@exemple.ca"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('roleEmail') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div style="flex: 0 1 200px; display: flex; flex-direction: column; gap: 6px;">
                    <label for="roster-role-name" style="font-weight: 600; font-size: 0.85rem;">Rôle</label>
                    <select id="roster-role-name" wire:model="roleName"
                            style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); background: #FFFFFF;">
                        <option value="instructor">Formateur</option>
                        <option value="assistant">Assistant</option>
                        <option value="editor">Éditeur</option>
                    </select>
                    @error('roleName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div><x-core::button type="submit" variant="primary" size="sm">Attribuer</x-core::button></div>
            </form>

            {{-- Liste de l'équipe --}}
            @if ($this->teamRoles->isNotEmpty())
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <caption class="visually-hidden" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);">
                            Membres de l'équipe pédagogique de ce cours
                        </caption>
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Personne</th>
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Courriel</th>
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Rôle</th>
                                <th scope="col" style="padding: 8px 10px; font-weight: 700;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->teamRoles as $role)
                                <tr wire:key="role-{{ $role->id }}" style="border-bottom: 1px solid #F3F4F6;">
                                    <td style="padding: 10px;">{{ $role->user?->name ?? '-' }}</td>
                                    <td style="padding: 10px;">{{ $role->user?->email ?? '-' }}</td>
                                    <td style="padding: 10px;">{{ $this->roleLabel($role->role) }}</td>
                                    <td style="padding: 10px;">
                                        @if ($role->role === 'owner')
                                            <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Non retirable</span>
                                        @elseif ($confirmingRoleRemoval === $role->id)
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span style="font-size: 0.82rem; font-weight: 600;">Retirer ce rôle ?</span>
                                                <x-core::button type="button" wire:click="removeRole({{ $role->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelRoleRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                            </div>
                                        @else
                                            <x-core::button type="button" wire:click="confirmRoleRemoval({{ $role->id }})" variant="ghost" size="sm"
                                                            title="Retirer ce rôle" aria-label="Retirer le rôle de {{ $role->user?->name ?? 'cette personne' }}">Retirer</x-core::button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucun membre d'équipe pour l'instant.</p>
            @endif
        </section>
    @endcan

</div>
