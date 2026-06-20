<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
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
                    <x-core::button href="#" variant="secondary" size="sm" title="La création de cours arrive en phase 3.">
                        Créer un cours
                    </x-core::button>
                @endif
            </div>

            @if($this->canCreateCourse)
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 16px;">
                    La création de cours directement ici arrive en phase 3.
                </p>
            @endif

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

                                <div class="d-flex align-items-center gap-2">
                                    <x-core::button
                                        :href="route('academy.courses.show', $course->slug)"
                                        variant="primary" size="sm">
                                        Gérer
                                    </x-core::button>
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
    @endif

    {{-- ───────────────────── Vue admin (academy.manage) ───────────────────── --}}
    @if($this->isAcademyAdmin)
        <section aria-labelledby="academy-vue-admin"
                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px; background: #F9FAFB;">
            <h2 id="academy-vue-admin"
                style="font-family: var(--f-heading); font-size: 1rem; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                Administration
            </h2>
            <p style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 14px;">
                La gestion front-end complète arrive en phase 3. En attendant, le filet d'administration reste disponible.
            </p>
            <x-core::button href="/admin/academy/courses" variant="secondary" size="sm">
                Ouvrir l'administration des cours
            </x-core::button>
        </section>
    @endif
</div>
