<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if(session('academy_dashboard_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; margin-bottom: 18px; font-size: 0.9rem;">
            {{ session('academy_dashboard_status') }}
        </div>
    @endif
    {{-- ───────────────────────── Mes formations (tous rôles) ───────────────────────── --}}
    <section aria-labelledby="academy-mes-formations" class="mb-5">
        <h2 id="academy-mes-formations"
            style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 16px;">
            Mes formations
        </h2>

        @if($this->enrollments->isNotEmpty())
            <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                @foreach($this->enrollments as $row)
                    <li style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div style="flex: 1 1 280px; min-width: 220px;">
                                <h3 style="font-family: var(--f-heading); font-size: 1.1rem; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                                    {{ $row['course']->title }}
                                </h3>
                                <div role="progressbar"
                                     aria-valuenow="{{ $row['percent'] }}" aria-valuemin="0" aria-valuemax="100"
                                     aria-label="Progression : {{ $row['percent'] }} %"
                                     style="background: #E5E7EB; border-radius: 999px; height: 8px; max-width: 360px; margin: 8px 0;">
                                    <span style="display: block; height: 8px; border-radius: 999px; width: {{ $row['percent'] }}%; background: var(--sys-action-primary, #064E5A);"></span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                    {{ $row['percent'] }} % complété
                                </p>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if($row['firstLesson'])
                                    <x-core::button
                                        :href="route('academy.lessons.show', ['course' => $row['course']->slug, 'lesson' => $row['firstLesson']->id])"
                                        variant="primary" size="sm">
                                        Continuer
                                    </x-core::button>
                                @else
                                    <x-core::button
                                        :href="route('academy.courses.show', $row['course']->slug)"
                                        variant="secondary" size="sm">
                                        Voir la formation
                                    </x-core::button>
                                @endif

                                @if($row['certificate'])
                                    <x-core::button
                                        :href="route('academy.certificates.show', $row['certificate']->public_url_slug)"
                                        variant="ghost" size="sm">
                                        Mon certificat
                                    </x-core::button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 28px; text-align: center;">
                <p style="color: var(--sys-text-default, #1A1D23); margin-bottom: 6px; font-weight: 600;">
                    Vous n'êtes inscrit à aucune formation pour l'instant.
                </p>
                <p style="color: var(--sys-text-muted, #6B7280); margin-bottom: 18px;">
                    Parcourez le catalogue et lancez votre première formation IA.
                </p>
                <x-core::button :href="route('academy.index')" variant="primary" size="sm">
                    Découvrir les formations
                </x-core::button>
            </div>
        @endif
    </section>

    {{-- ───────────────────── Mes cours (formateur / admin) ───────────────────── --}}
    @if($this->canManageCourses)
        <section aria-labelledby="academy-mes-cours" class="mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 id="academy-mes-cours"
                    style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0;">
                    Mes cours
                </h2>

                @if($this->canCreateCourse)
                    <x-core::button :href="route('academy.courses.create')" variant="secondary" size="sm">
                        Créer un cours
                    </x-core::button>
                @endif
            </div>

            @if($this->managedCourses->isNotEmpty())
                <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                    @foreach($this->managedCourses as $course)
                        <li style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div style="flex: 1 1 280px; min-width: 220px;">
                                    <div class="d-flex flex-wrap align-items-center gap-2" style="margin-bottom: 6px;">
                                        <h3 style="font-family: var(--f-heading); font-size: 1.1rem; color: var(--sys-text-default, #1A1D23); margin: 0;">
                                            {{ $course->title }}
                                        </h3>
                                        <span style="font-size: 0.72rem; font-weight: 600; padding: 2px 10px; border-radius: 999px; background: #EEF2F1; color: var(--sys-action-primary, #064E5A);">
                                            {{ $course->viewer_role }}
                                        </span>
                                    </div>
                                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                        {{ $course->status === 'published' ? 'Publié' : 'Brouillon' }}
                                        · {{ $course->lessons_count }} {{ $course->lessons_count > 1 ? 'leçons' : 'leçon' }}
                                    </p>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <x-core::button
                                        :href="route('academy.courses.manage', $course->slug)"
                                        variant="primary" size="sm">
                                        Gérer
                                    </x-core::button>

                                    {{-- D1 — Statistiques par cours. Gâté manageEnrollments
                                         (admin OU owner/instructor) : la vraie garde reste
                                         authorize() serveur dans CourseAnalytics::mount(). --}}
                                    @can('manageEnrollments', $course)
                                        <x-core::button
                                            :href="route('academy.courses.analytics', $course->slug)"
                                            variant="secondary" size="sm">
                                            <span aria-hidden="true">📊</span> Statistiques
                                        </x-core::button>
                                    @endcan

                                    @if($confirmingDuplicationId === $course->id)
                                        <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Dupliquer ce cours ?</span>
                                        <x-core::button
                                            type="button" variant="primary" size="sm"
                                            wire:click="duplicate({{ $course->id }})"
                                            wire:loading.attr="disabled" wire:target="duplicate">
                                            Oui, dupliquer
                                        </x-core::button>
                                        <x-core::button
                                            type="button" variant="ghost" size="sm"
                                            wire:click="cancelDuplication">
                                            Annuler
                                        </x-core::button>
                                    @else
                                        <x-core::button
                                            type="button" variant="secondary" size="sm"
                                            wire:click="confirmDuplication({{ $course->id }})">
                                            Dupliquer
                                        </x-core::button>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 24px; text-align: center;">
                    <p style="color: var(--sys-text-muted, #6B7280); margin: 0;">
                        Vous ne gérez aucun cours pour l'instant.
                    </p>
                </div>
            @endif
        </section>

        {{-- ───────────────────── Modèles réutilisables (C3) ───────────────────── --}}
        @if($this->managedTemplates->isNotEmpty())
            <section aria-labelledby="academy-modeles" class="mb-5">
                <h2 id="academy-modeles"
                    style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Modèles
                </h2>
                <p style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 16px;">
                    Partez d'un modèle réutilisable : « Utiliser ce modèle » crée une copie modifiable en brouillon.
                </p>

                <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                    @foreach($this->managedTemplates as $template)
                        <li style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div style="flex: 1 1 280px; min-width: 220px;">
                                    <h3 style="font-family: var(--f-heading); font-size: 1.1rem; color: var(--sys-text-default, #1A1D23); margin: 0;">
                                        {{ $template->title }}
                                    </h3>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @if($confirmingDuplicationId === $template->id)
                                        <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Créer une copie ?</span>
                                        <x-core::button
                                            type="button" variant="primary" size="sm"
                                            wire:click="duplicate({{ $template->id }})"
                                            wire:loading.attr="disabled" wire:target="duplicate">
                                            Oui, créer
                                        </x-core::button>
                                        <x-core::button
                                            type="button" variant="ghost" size="sm"
                                            wire:click="cancelDuplication">
                                            Annuler
                                        </x-core::button>
                                    @else
                                        <x-core::button
                                            type="button" variant="primary" size="sm"
                                            wire:click="confirmDuplication({{ $template->id }})">
                                            Utiliser ce modèle
                                        </x-core::button>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif

    {{-- ───────────────────── Vue admin (academy.manage) ───────────────────── --}}
    @if($this->isAcademyAdmin)
        <section aria-labelledby="academy-vue-admin"
                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px; background: #F9FAFB;">
            <h2 id="academy-vue-admin"
                style="font-family: var(--f-heading); font-size: 1rem; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                Administration
            </h2>
            <p style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 0;">
                En tant qu'administrateur, vous gérez tous les cours directement ci-dessus : création, édition du contenu, inscriptions et rôles.
            </p>
        </section>
    @endif
</div>
