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
