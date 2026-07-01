{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)

    DISCUSSION SOCIALE PAR VIDÉO (dette D-video-discussion, LMS 2026, note 81/100).
    Réutilise INTÉGRALEMENT le forum existant (ForumTopic/ForumPost/ForumController/
    ForumService, DRY strict - aucun nouveau système) : l'item VIDÉO porte son propre
    forum (sujets scopés à son lesson_item_id, exactement comme un item « forum »
    classique) via ForumController::resolveItem() qui accepte le type « video » quand
    config('academy.video_discussion_enabled') === true (défaut false).

    OBJECTIF : un apprenant pose une question/commentaire ancré à un instant précis de
    la vidéo (« à 2:34, je ne comprends pas... »), affiché en badge horodaté cliquable.

    LIMITE HONNÊTE (lecteur ScreenPal, voir components/video-player.blade.php) :
    le lecteur est un iframe TIERS cross-origin, sandboxé, SANS pont postMessage codé
    dans ce projet. Le badge horodaté ne peut donc PAS faire avancer (seekTo) le
    lecteur programmatiquement. Comportement dégradé PROPRE et assumé : le badge
    ramène la vidéo à l'écran (scrollIntoView vers #lesson-video-{id}) et affiche le
    repère textuel « ⏱ 2:34 » - jamais d'erreur JS, jamais de faux contrôle inventé.
    Pour la même raison, le champ « à quel moment ? » n'est PAS pré-rempli avec le
    temps courant du lecteur (non lisible depuis ce contexte) : saisie manuelle assumée.

    Variables requises : $item (LessonItem type video), $course, $lesson, $hasAccess,
    $isEnrolled.
--}}
@php
    $vdEnabled = config('academy.video_discussion_enabled') === true;
@endphp
@if($vdEnabled && $hasAccess)
    @php
        $vdManager = auth()->check() && auth()->user()->can('manageEnrollments', $course);
        // Un étudiant peut ouvrir une question si les sujets étudiants sont permis (défaut
        // true) et que le forum n'est pas verrouillé (défaut false) ; un gérant toujours.
        $vdCanCreate = $vdManager
            || (auth()->check() && \Modules\Academy\Services\ForumService::allowsStudentTopics($item) && ! \Modules\Academy\Services\ForumService::isLocked($item));
        // Tri opt-in par ordre du contenu vidéo (défaut : plus récent, comme le forum classique).
        $vdSortByVideoTime = request('sort') === 'video_time';
        $vdTopics = \Modules\Academy\Services\ForumService::topics($item, 10, $vdSortByVideoTime);
        $vdLocked = \Modules\Academy\Services\ForumService::isLocked($item);
    @endphp
    @once
        <style>
            .academy-video-discussion { max-width: 64ch; margin-top: 1.25rem; border-top: 1px solid #E5E7EB; padding-top: 1rem; }
            .academy-video-discussion-title { font-weight: 600; color: var(--sys-text-default, #1A1D23); margin: 0 0 0.5rem; font-size: 0.95rem; }
            .academy-video-ts-field { width: 8rem; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 8px; font: inherit; display: block; margin-bottom: 4px; }
            .academy-video-ts-hint { font-size: 0.75rem; color: var(--sys-text-muted, #6B7280); margin: 2px 0 10px; }
            .academy-video-ts-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #E0F2F1; color: #064E5A; border: 1px solid transparent; cursor: pointer; margin-right: 6px; }
            .academy-video-ts-badge:hover, .academy-video-ts-badge:focus-visible { background: #064E5A; color: #fff; outline: 2px solid #064E5A; outline-offset: 2px; }
            .academy-video-sort { display: flex; flex-wrap: wrap; gap: 4px 10px; align-items: center; font-size: 0.8rem; margin-bottom: 0.75rem; color: var(--sys-text-muted, #6B7280); }
            .academy-video-sort a { color: var(--sys-text-muted, #6B7280); text-decoration: underline; }
            .academy-video-sort a[aria-current="true"] { color: #064E5A; font-weight: 700; text-decoration: none; }
        </style>
    @endonce
    <div class="academy-video-discussion" data-video-discussion-item="{{ $item->id }}">
        <p class="academy-video-discussion-title">💬 Questions et commentaires sur cette vidéo</p>

        @if($vdLocked && ! $vdManager)
            <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                <span aria-hidden="true">🔒</span> Cette discussion est en lecture seule.
            </p>
        @endif

        {{-- Tri : plus récent (défaut) ou ordre du contenu vidéo. --}}
        @if($vdTopics->total() > 1)
            <div class="academy-video-sort" role="group" aria-label="Trier les questions">
                <span>Trier par :</span>
                <a href="{{ request()->fullUrlWithQuery(['sort' => null]) }}" aria-current="{{ $vdSortByVideoTime ? 'false' : 'true' }}">Plus récent</a>
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'video_time']) }}" aria-current="{{ $vdSortByVideoTime ? 'true' : 'false' }}">Moment dans la vidéo</a>
            </div>
        @endif

        {{-- Ouvrir une question/commentaire ancré à un instant précis (a11y : champs
             étiquetés ; honeypot caché anti-spam, même patron que le forum classique). --}}
        @if($vdCanCreate)
            <details class="academy-forum-topic" style="border-style: dashed;">
                <summary>
                    <span class="academy-forum-topic-title">+ Poser une question sur un passage</span>
                </summary>
                <div class="academy-forum-body">
                    <form method="POST" action="{{ route('academy.forum.topics.create', [$course, $lesson, $item->id]) }}">
                        @csrf
                        <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                            <label for="vd-hp-{{ $item->id }}">Ne pas remplir</label>
                            <input type="text" id="vd-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\ForumService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                        </div>
                        <label for="vd-title-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">Titre</label>
                        <input type="text" id="vd-title-{{ $item->id }}" name="title" class="academy-forum-field"
                               maxlength="{{ \Modules\Academy\Services\ForumService::TITLE_MAX }}" required>

                        <label for="vd-ts-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">À quel moment ? (mm:ss, facultatif)</label>
                        <input type="text" id="vd-ts-{{ $item->id }}" name="video_timestamp" class="academy-video-ts-field"
                               placeholder="ex. 2:34" inputmode="numeric" aria-describedby="vd-ts-hint-{{ $item->id }}"
                               pattern="^(?:[0-9]{1,2}:)?[0-9]{1,3}:[0-5][0-9]$">
                        <p id="vd-ts-hint-{{ $item->id }}" class="academy-video-ts-hint">Le lecteur ne transmet pas l'instant courant automatiquement : indiquez-le manuellement (facultatif).</p>

                        <label for="vd-body-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">Votre question ou commentaire</label>
                        <textarea id="vd-body-{{ $item->id }}" name="body" class="academy-forum-text" rows="3"
                                  maxlength="{{ \Modules\Academy\Services\ForumService::BODY_MAX }}" required></textarea>
                        <div class="mt-2">
                            <x-core::button type="submit" variant="primary" size="sm">Publier</x-core::button>
                        </div>
                    </form>
                </div>
            </details>
        @endif

        {{-- Fil de discussion (mêmes gabarits/classes que le forum classique, DRY visuel). --}}
        @forelse($vdTopics as $vdTopic)
            @php
                $vdReplyAllowed = $vdManager || (! $vdLocked && ! $vdTopic->is_locked && auth()->check());
                $vdTopicTs      = $vdTopic->formattedVideoTimestamp();
            @endphp
            <details class="academy-forum-topic" wire:key="video-discussion-topic-{{ $vdTopic->id }}">
                <summary>
                    <span class="academy-forum-topic-title">
                        @if($vdTopicTs)
                            {{-- Comportement dégradé HONNÊTE : ramène la vidéo à l'écran (le lecteur
                                 ScreenPal, iframe tiers cross-origin sans pont postMessage codé ici,
                                 n'est pas contrôlable en JS) ; jamais d'erreur si l'ancre est absente. --}}
                            <button type="button" class="academy-video-ts-badge"
                                    onclick="document.getElementById('lesson-video-{{ $item->id }}')?.scrollIntoView({behavior:'smooth', block:'center'});"
                                    aria-label="Repère vidéo {{ $vdTopicTs }} : fait défiler jusqu'à la vidéo">
                                <span aria-hidden="true">⏱</span> {{ $vdTopicTs }}
                            </button>
                        @endif
                        @if($vdTopic->is_locked)<span aria-hidden="true" title="Verrouillé">🔒</span><span class="visually-hidden">Verrouillé</span> @endif
                        {{ $vdTopic->title }}
                        <span class="academy-forum-badge">{{ $vdTopic->posts_count }} {{ $vdTopic->posts_count === 1 ? 'réponse' : 'réponses' }}</span>
                    </span>
                    <span class="academy-forum-meta">{{ $vdTopic->user?->name ?? '(inconnu)' }} · {{ $vdTopic->created_at?->diffForHumans() }}</span>
                </summary>
                <div class="academy-forum-body">
                    {{-- SÉCURITÉ : renderRichText() (markdown, html_input=strip) neutralise tout HTML brut => anti-XSS. --}}
                    <div class="prose academy-richtext">{!! \Modules\Academy\Models\LessonItem::renderRichText($vdTopic->body) !!}</div>

                    @foreach($vdTopic->posts as $vdPost)
                        @php $vdPostTs = $vdPost->formattedVideoTimestamp(); @endphp
                        <div class="academy-forum-post" wire:key="video-discussion-post-{{ $vdPost->id }}">
                            <div class="academy-forum-post-meta">
                                @if($vdPostTs)
                                    <button type="button" class="academy-video-ts-badge"
                                            onclick="document.getElementById('lesson-video-{{ $item->id }}')?.scrollIntoView({behavior:'smooth', block:'center'});"
                                            aria-label="Repère vidéo {{ $vdPostTs }} : fait défiler jusqu'à la vidéo">
                                        <span aria-hidden="true">⏱</span> {{ $vdPostTs }}
                                    </button>
                                @endif
                                {{ $vdPost->user?->name ?? '(inconnu)' }} · {{ $vdPost->created_at?->diffForHumans() }}
                            </div>
                            <div class="prose academy-richtext">{!! \Modules\Academy\Models\LessonItem::renderRichText($vdPost->body) !!}</div>
                        </div>
                    @endforeach

                    @if($vdTopic->posts_count > $vdTopic->posts->count())
                        <p class="text-muted mt-1" style="font-size: 0.8rem;">
                            {{ $vdTopic->posts->count() }} des {{ $vdTopic->posts_count }} réponses affichées.
                        </p>
                    @endif

                    @if($vdReplyAllowed)
                        <form method="POST" action="{{ route('academy.forum.topics.reply', [$course, $lesson, $item->id, $vdTopic->id]) }}" class="mt-2">
                            @csrf
                            <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                <label for="vd-rhp-{{ $vdTopic->id }}">Ne pas remplir</label>
                                <input type="text" id="vd-rhp-{{ $vdTopic->id }}" name="{{ \Modules\Academy\Services\ForumService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                            </div>
                            <label for="vd-rts-{{ $vdTopic->id }}" style="font-size: 0.8rem; font-weight: 600;">À quel moment ? (mm:ss, facultatif)</label>
                            <input type="text" id="vd-rts-{{ $vdTopic->id }}" name="video_timestamp" class="academy-video-ts-field"
                                   placeholder="ex. 5:10" pattern="^(?:[0-9]{1,2}:)?[0-9]{1,3}:[0-5][0-9]$">
                            <label for="vd-reply-{{ $vdTopic->id }}" style="font-size: 0.82rem; font-weight: 600;">Répondre</label>
                            <textarea id="vd-reply-{{ $vdTopic->id }}" name="body" class="academy-forum-text" rows="2"
                                      maxlength="{{ \Modules\Academy\Services\ForumService::BODY_MAX }}" required></textarea>
                            <div class="mt-2">
                                <x-core::button type="submit" variant="secondary" size="sm">Répondre</x-core::button>
                            </div>
                        </form>
                    @elseif($vdTopic->is_locked)
                        <p class="text-muted mt-2" style="font-size: 0.82rem;"><span aria-hidden="true">🔒</span> Cette question est verrouillée.</p>
                    @endif
                </div>
            </details>
        @empty
            <p class="text-muted" style="font-size: 0.9rem;">Aucune question pour l'instant. @if($vdCanCreate) Soyez le premier à en poser une. @endif</p>
        @endforelse

        @if($vdTopics->hasPages())
            <div class="mt-3">{{ $vdTopics->withQueryString()->links() }}</div>
        @endif
    </div>
@endif
