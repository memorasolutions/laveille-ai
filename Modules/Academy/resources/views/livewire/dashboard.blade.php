<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if(session('academy_dashboard_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; margin-bottom: 18px; font-size: 0.9rem;">
            {{ session('academy_dashboard_status') }}
        </div>
    @endif
    {{-- ───────────── SRS : bouton de révision (visible si cartes dues ; masqué si drapeau off) ───────────── --}}
    @if($this->srsDueCount > 0)
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;border:1px solid #99F6E4;background:#F0FDFA;border-radius:var(--sys-radius-md, 0.75rem);padding:16px 20px;margin-bottom:22px;">
            <div>
                <p style="margin:0;font-weight:700;color:#064E5A;font-size:1.05rem;">Il est temps de réviser</p>
                <p style="margin:2px 0 0;color:#334155;font-size:0.9rem;">
                    {{ $this->srsDueCount }} {{ $this->srsDueCount > 1 ? 'cartes vous attendent' : 'carte vous attend' }} - moins de deux minutes suffisent.
                </p>
            </div>
            <a href="{{ route('academy.srs.review') }}"
               style="display:inline-block;background:#064E5A;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:600;min-height:44px;line-height:20px;white-space:nowrap;">
                Réviser ({{ $this->srsDueCount }})
            </a>
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

                                @if(is_array($row['completion'] ?? null) && ($row['completion']['type'] ?? 'all_required') !== 'all_required')
                                    <p style="font-size: 0.8rem; color: var(--sys-text-default, #374151); margin: 6px 0 0;">
                                        <span style="font-weight: 600; color: var(--c-primary, #064E5A);">Pour valider :</span>
                                        {{ $row['completion']['label'] ?? '' }}
                                        @if(!empty($row['completion']['complete']))
                                            <span style="color: var(--c-primary, #064E5A); font-weight: 600;">- ✓ atteint</span>
                                        @else
                                            <span style="color: var(--sys-text-muted, #6B7280);">- {{ (int) ($row['completion']['percentOfGoal'] ?? 0) }} %</span>
                                        @endif
                                    </p>
                                @endif
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

    {{-- ───────── V5-b : Échéances à venir (bandeau, masqué si vide) ─────── --}}
    @if($this->upcomingDeadlines->isNotEmpty())
        <section aria-labelledby="academy-echeances" class="mb-5">
            <h2 id="academy-echeances"
                style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 14px;">
                Échéances à venir
            </h2>

            @php
                $typeLabels = [
                    'due'    => 'Devoir',
                    'exam'   => 'Examen',
                    'live'   => 'En direct',
                    'manual' => 'Événement',
                ];
                $typeColors = [
                    'due'    => 'background: var(--sys-status-danger-bg, #FEE2E2); color: var(--sys-status-danger-text, #7F1414);',
                    'exam'   => 'background: var(--sys-status-warning-bg, #FFEDD5); color: var(--sys-status-warning-text, #7C2D12);',
                    'live'   => 'background: var(--sys-status-success-bg, #D1FAE5); color: var(--sys-status-success-text, #054F3A);',
                    'manual' => 'background: var(--sys-status-info-bg, #E0F2FE); color: var(--sys-status-info-text, #0C4A6E);',
                ];
            @endphp

            <ul class="list-unstyled d-flex flex-column gap-2" role="list" style="margin: 0;">
                @foreach($this->upcomingDeadlines as $ev)
                    @php
                        $label      = $typeLabels[$ev['type']] ?? 'Événement';
                        $badgeStyle = $typeColors[$ev['type']] ?? $typeColors['manual'];
                        // timezone() sur starts_at évite l'écart d'un jour autour de minuit.
                        $daysLeft   = (int) now('America/Toronto')->diffInDays($ev['starts_at']->timezone('America/Toronto'), false);
                        $daysLabel  = match(true) {
                            $daysLeft === 0 => "aujourd'hui",
                            $daysLeft === 1 => 'demain',
                            default         => 'dans ' . $daysLeft . ' j',
                        };
                    @endphp
                    <li role="listitem"
                        style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);
                               padding: 14px 18px; display: flex; flex-wrap: wrap;
                               align-items: center; gap: 12px; background: #FFFFFF;">

                        {{-- Badge type --}}
                        <span style="font-size: 0.72rem; font-weight: 700; padding: 2px 10px;
                                     border-radius: 999px; white-space: nowrap; {{ $badgeStyle }}">
                            {{ $label }}
                        </span>

                        {{-- Titre et cours --}}
                        <span style="flex: 1 1 180px; font-size: 0.92rem;
                                     color: var(--sys-text-default, #1A1D23); font-weight: 500;">
                            {{ $ev['title'] }}
                            <span style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);
                                         font-weight: 400; margin-left: 6px;">
                                ({{ $ev['course_title'] }})
                            </span>
                        </span>

                        {{-- Date relative + absolue --}}
                        <time datetime="{{ $ev['starts_at']->timezone('America/Toronto')->toIso8601String() }}"
                              style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);
                                     white-space: nowrap;">
                            {{ $ev['starts_at']->timezone('America/Toronto')->translatedFormat('j M Y') }}
                            <span style="font-weight: 600; color: var(--sys-action-primary, #064E5A);">
                                ({{ $daysLabel }})
                            </span>
                        </time>

                        {{-- Lien vers le calendrier du cours --}}
                        <a href="{{ route('academy.courses.calendar', $ev['course_slug']) }}"
                           style="font-size: 0.8rem; color: var(--sys-action-primary, #064E5A);
                                  text-decoration: underline; white-space: nowrap;"
                           aria-label="Voir le calendrier de {{ $ev['course_title'] }}">
                            Voir le calendrier
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ───────────────────────── Mes devoirs (E2) ─────────────────────────
         Composant role-aware : requêtes scopées à auth()->id() et aux cours où
         l'utilisateur est inscrit ACTIF. Ne s'affiche que s'il y a des devoirs. --}}
    @livewire('academy.student-assignments')

    {{-- ───────────────────────── Mes notes (V2-b carnet pondéré) ─────────────────────────
         Composant LECTURE SEULE, scopé à auth()->id() : note finale pondérée + lettre +
         détail par catégorie pour chaque cours pondéré suivi. Ne s'affiche que s'il y a
         au moins un cours pondéré. --}}
    @livewire('academy.student-grades')

    {{-- ───────────────────────── Gamification moderne (XP/niveau/classement) ─────────────────────────
         Drapeau academy.gamification_enabled (défaut OFF). Double garde : ce @if
         ET le composant lui-même (Gamification::enabled). Voir GamificationService. --}}
    @if(config('academy.gamification_enabled'))
        @livewire('academy.gamification')
    @endif

    {{-- ─────────── Sections secondaires en accordéon (Option E hybride) ───────────
         Mes formations + Échéances restent TOUJOURS visibles ci-dessus (consultées
         quotidiennement). Badges/Annonces/Modèles/Vue admin sont repliables pour
         raccourcir la page. Même pattern Bootstrap que public/show.blade.php
         (syllabus) : .accordion/.accordion-item/.accordion-button/.accordion-collapse,
         DRY, zéro nouveau CSS. Tous les id="academy-xxx" existants sont conservés
         (ancres utilisées ailleurs). --}}
    @php
        // Annonce publiée il y a moins de 7 jours = contenu « urgent/nouveau » →
        // on ouvre l'accordéon Annonces par défaut pour ne pas la cacher.
        $__hasRecentAnnouncement = $this->announcements->contains(
            fn ($a) => $a->published_at && $a->published_at->timezone('America/Toronto')->diffInDays(now('America/Toronto')) < 7
        );
    @endphp
    <div class="accordion" id="academySecondaryAccordion">

        {{-- ───────────────────────── Vos badges (E1) ───────────────────────── --}}
        <div class="accordion-item" style="border-radius: 8px; margin-bottom: 6px; overflow: hidden; border: 1px solid #E5E7EB;">
            <h2 class="accordion-header" id="academy-mes-badges">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#academy-mes-badges-collapse"
                        aria-expanded="false"
                        aria-controls="academy-mes-badges-collapse">
                    <span aria-hidden="true">🏅</span>&nbsp;Vos badges
                </button>
            </h2>
            <div id="academy-mes-badges-collapse"
                 class="accordion-collapse collapse"
                 aria-labelledby="academy-mes-badges"
                 data-bs-parent="#academySecondaryAccordion">
            <div class="accordion-body">

        @if($this->earnedBadges->isNotEmpty())
            <ul class="list-unstyled d-flex flex-wrap gap-3" role="list" style="margin: 0;">
                @foreach($this->earnedBadges as $earned)
                    @php($badge = $earned->badge)
                    @if($badge)
                        <li role="listitem"
                            aria-label="Badge obtenu : {{ $badge->name }}{{ $earned->awarded_at ? ', le '.$earned->awarded_at->timezone('America/Toronto')->format('Y-m-d') : '' }}"
                            style="flex: 0 1 220px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px; display: flex; gap: 12px; align-items: flex-start; background: #FFFFFF;">
                            <span aria-hidden="true" style="font-size: 1.8rem; line-height: 1;">{{ $badge->icon ?: '🏅' }}</span>
                            <div>
                                <p style="font-family: var(--f-heading); font-size: 0.98rem; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin: 0 0 2px;">
                                    {{ $badge->name }}
                                </p>
                                @if($badge->description)
                                    <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 4px;">
                                        {{ $badge->description }}
                                    </p>
                                @endif
                                @if($earned->awarded_at)
                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                        Obtenu le {{ $earned->awarded_at->timezone('America/Toronto')->format('Y-m-d') }}
                                    </p>
                                @endif
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        @else
            <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 24px; text-align: center;">
                <p style="color: var(--sys-text-default, #1A1D23); margin: 0;">
                    <span aria-hidden="true">🏅</span> Complétez votre première formation pour décrocher un badge&nbsp;!
                </p>
            </div>
        @endif

        {{-- Badges à débloquer (grisés) pour motiver la progression. --}}
        @if($this->lockedBadges->isNotEmpty())
            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 18px 0 10px;">
                À débloquer
            </p>
            <ul class="list-unstyled d-flex flex-wrap gap-3" role="list" style="margin: 0;">
                @foreach($this->lockedBadges as $badge)
                    <li role="listitem"
                        aria-label="Badge à débloquer : {{ $badge->name }}"
                        style="flex: 0 1 220px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px; display: flex; gap: 12px; align-items: flex-start; background: #F9FAFB; opacity: 0.75;">
                        <span aria-hidden="true" style="font-size: 1.8rem; line-height: 1; filter: grayscale(1);">{{ $badge->icon ?: '🔒' }}</span>
                        <div>
                            <p style="font-family: var(--f-heading); font-size: 0.98rem; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin: 0 0 2px;">
                                {{ $badge->name }}
                            </p>
                            @if($badge->description)
                                <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                    {{ $badge->description }}
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

            </div>
            </div>
        </div>

        {{-- ───────────────────── Annonces de vos formations (D3) ───────────────────── --}}
        @if($this->announcements->isNotEmpty())
            <div class="accordion-item" style="border-radius: 8px; margin-bottom: 6px; overflow: hidden; border: 1px solid #E5E7EB;">
                <h2 class="accordion-header" id="academy-annonces">
                    <button class="accordion-button {{ $__hasRecentAnnouncement ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#academy-annonces-collapse"
                            aria-expanded="{{ $__hasRecentAnnouncement ? 'true' : 'false' }}"
                            aria-controls="academy-annonces-collapse">
                        Annonces de vos formations
                        @if($__hasRecentAnnouncement)
                            <span aria-hidden="true" style="margin-left: 8px; font-size: 0.72rem; font-weight: 700; padding: 2px 10px; border-radius: 999px; background: var(--sys-status-info-bg, #E0F2FE); color: var(--sys-status-info-text, #0C4A6E);">
                                Nouveau
                            </span>
                        @endif
                    </button>
                </h2>
                <div id="academy-annonces-collapse"
                     class="accordion-collapse collapse {{ $__hasRecentAnnouncement ? 'show' : '' }}"
                     aria-labelledby="academy-annonces"
                     data-bs-parent="#academySecondaryAccordion">
                    <div class="accordion-body">
                        <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                            @foreach($this->announcements as $announcement)
                                <li wire:key="dash-announcement-{{ $announcement->id }}"
                                    style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                                        <h3 style="font-family: var(--f-heading); font-size: 1.05rem; color: var(--sys-text-default, #1A1D23); margin: 0;">
                                            {{ $announcement->title }}
                                        </h3>
                                        <span style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">
                                            {{ $announcement->course?->title }}
                                        </span>
                                    </div>
                                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 10px;">
                                        Publiée le {{ $announcement->published_at->timezone('America/Toronto')->format('Y-m-d H\hi') }}
                                        @if($announcement->author?->name) · par {{ $announcement->author->name }} @endif
                                    </p>
                                    <div style="font-size: 0.92rem; color: var(--sys-text-default, #1A1D23); line-height: 1.7;">
                                        {!! $announcement->renderedBody() !!}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- ───────────────────── Mes cours (formateur / admin) ───────────────────── --}}
    @if($this->canManageCourses)
        <section aria-labelledby="academy-mes-cours" class="mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 id="academy-mes-cours"
                    style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0;">
                    Mes cours
                </h2>

                @if($this->canCreateCourse)
                    <div class="d-flex flex-wrap gap-2">
                        <x-core::button :href="route('academy.courses.create')" variant="secondary" size="sm">
                            Créer un cours
                        </x-core::button>
                        {{-- F15 - Importer un cours depuis une sauvegarde .json (gâté create()). --}}
                        <x-core::button :href="route('academy.courses.import')" variant="ghost" size="sm">
                            Importer un cours
                        </x-core::button>
                    </div>
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
                                        {{-- F23 — Rapports et journaux par cours. Même gate
                                             manageEnrollments ; vraie garde = authorize() serveur. --}}
                                        <x-core::button
                                            :href="route('academy.courses.reports', $course->slug)"
                                            variant="secondary" size="sm">
                                            <span aria-hidden="true">📋</span> Rapports
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

        {{-- ─────────── Sections secondaires gestion, en accordéon ───────────
             Modèles + Vue admin sont repliables (fermées par défaut, rien d'urgent
             à signaler ici). Même pattern Bootstrap DRY que ci-dessus / show.blade.php. --}}
        @if($this->managedTemplates->isNotEmpty() || $this->isAcademyAdmin)
        <div class="accordion" id="academyManageAccordion">

        {{-- ───────────────────── Modèles réutilisables (C3) ───────────────────── --}}
        @if($this->managedTemplates->isNotEmpty())
            <div class="accordion-item" style="border-radius: 8px; margin-bottom: 6px; overflow: hidden; border: 1px solid #E5E7EB;">
                <h2 class="accordion-header" id="academy-modeles">
                    <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#academy-modeles-collapse"
                            aria-expanded="false"
                            aria-controls="academy-modeles-collapse">
                        Modèles
                    </button>
                </h2>
                <div id="academy-modeles-collapse"
                     class="accordion-collapse collapse"
                     aria-labelledby="academy-modeles"
                     data-bs-parent="#academyManageAccordion">
                <div class="accordion-body">
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
                </div>
                </div>
            </div>
        @endif

        {{-- ───────────────────── Vue admin (academy.manage) ───────────────────── --}}
        @if($this->isAcademyAdmin)
            <div class="accordion-item" style="border-radius: 8px; margin-bottom: 6px; overflow: hidden; border: 1px solid #E5E7EB; background: #F9FAFB;">
                <h2 class="accordion-header" id="academy-vue-admin">
                    <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#academy-vue-admin-collapse"
                            aria-expanded="false"
                            aria-controls="academy-vue-admin-collapse"
                            style="font-size: 1rem;">
                        Administration
                    </button>
                </h2>
                <div id="academy-vue-admin-collapse"
                     class="accordion-collapse collapse"
                     aria-labelledby="academy-vue-admin"
                     data-bs-parent="#academyManageAccordion">
                    <div class="accordion-body">
                        <p style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 16px;">
                            En tant qu'administrateur, vous gérez tous les cours directement ci-dessus : création, édition du contenu, inscriptions et rôles.
                        </p>

                        {{-- Analytiques prédictifs organisation — masqué si le drapeau est off
                             (sinon la route retourne un rendu neutre, jamais un lien mort). --}}
                        @if(config('academy.predictive_analytics_enabled'))
                            <div class="mb-3">
                                <x-core::button :href="route('academy.admin.org-analytics')" variant="secondary" size="sm">
                                    <span aria-hidden="true">📈</span> Analytiques organisation
                                </x-core::button>
                            </div>
                        @endif

                        {{-- Exports CSV admin (M7) — gâtés academy.reports.view ; vraie garde
                             = middleware `can:academy.reports.view` sur les routes. --}}
                        @can('academy.reports.view')
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <x-core::button :href="route('academy.admin.export.enrollments')" variant="ghost" size="sm">
                                    <span aria-hidden="true">⬇️</span> Exporter les inscriptions
                                </x-core::button>
                                <x-core::button :href="route('academy.admin.export.completions')" variant="ghost" size="sm">
                                    <span aria-hidden="true">⬇️</span> Exporter les complétions
                                </x-core::button>
                                <x-core::button :href="route('academy.admin.export.progress')" variant="ghost" size="sm">
                                    <span aria-hidden="true">⬇️</span> Exporter la progression
                                </x-core::button>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        </div>
        @endif
    @endif
</div>
