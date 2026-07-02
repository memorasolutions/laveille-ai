{{-- @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
     Partial DRY : corps de rendu dun item de leçon (tous types).
     Inclus par lesson.blade.php ET livewire/deck-player.blade.php.
     Variables requises : $item, $hasAccess, $isEnrolled, $isPreview, $isFree,
                         $course, $lesson, $choiceVotes, $feedbackResponses,
                         $videoRedirectUrls
--}}
                        {{-- ── TYPE VIDEO ── --}}
                        @if($item->type === 'video')
                            @php
                                // Champ canonique : player_url. Repli rétrocompat : ancien payload['embed'].
                                $videoUrl = $item->payload['player_url'] ?? ($item->payload['embed'] ?? null);

                                // PROXY VIDÉO SIGNÉ (« protéger l'accès, pas l'iframe ») : le lien
                                // ScreenPal brut ($videoUrl) ne doit JAMAIS être injecté dans le DOM.
                                // L'iframe pointe vers l'URL SIGNÉE à expiration (calculée par
                                // LessonController, 4 h — voir VideoRedirectController). À défaut
                                // (map absente/incomplète), rien n'est rendu plutôt que de retomber
                                // sur le lien brut.
                                $videoSignedUrl = ($videoRedirectUrls ?? [])[$item->id] ?? null;
                            @endphp
                            @if($hasAccess && !empty($videoUrl) && !empty($videoSignedUrl))
                                {{--
                                    GATING CRITIQUE :
                                    L'URL vidéo n'est injectée dans le DOM QUE si $hasAccess === true.
                                    Côté serveur, Blade ne rend pas le composant si la condition est fausse.
                                    Aucune URL vidéo ScreenPal ne fuite dans le HTML : seule l'URL SIGNÉE
                                    (proxy interne, à expiration) atteint le navigateur de l'élève.
                                --}}
                                {{-- id stable : ancre de défilement pour les badges horodatés de la
                                     discussion sociale par vidéo (voir partials/video-discussion). --}}
                                <div id="lesson-video-{{ $item->id }}">
                                    <x-academy::video-player
                                        :playerUrl="$videoSignedUrl"
                                        :poster="$item->posterUrl()"
                                        :title="$item->title ?? $lesson->title"
                                        :aspectRatio="$item->payload['aspect_ratio'] ?? null"
                                    />

                                    @if(isset($item->payload['duration_seconds']))
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">
                                            ⏱ Durée : {{ ceil($item->payload['duration_seconds'] / 60) }} min
                                        </p>
                                    @endif
                                </div>

                                {{-- DISCUSSION SOCIALE PAR VIDÉO (dette D-video-discussion, LMS 2026) :
                                     réutilise le forum existant sur l'item vidéo lui-même. No-op tant que
                                     ACADEMY_VIDEO_DISCUSSION_ENABLED=false (défaut). --}}
                                @include('academy::public.partials.video-discussion', [
                                    'item'       => $item,
                                    'course'     => $course,
                                    'lesson'     => $lesson,
                                    'hasAccess'  => $hasAccess,
                                    'isEnrolled' => $isEnrolled,
                                ])

                            @else
                                {{-- Panneau d'appel à l'action (pas d'URL dans le DOM) --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour visionner
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à cette vidéo
                                        @else
                                            Contenu en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour accéder aux leçons vidéo.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour regarder toutes les leçons.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        {{-- M5 : CTA Acheter depuis la leçon (cours payant) --}}
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE QUIZ ── --}}
                        @elseif($item->type === 'quiz')
                            @if($isPreview ?? false)
                                {{-- En prévisualisation : le quiz n'est PAS proposé (aucune progression
                                     enregistrée pour le gérant). Note discrète à la place. --}}
                                <p class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                    Les actions (progression, quiz) sont désactivées en prévisualisation.
                                </p>
                            @else
                                @php
                                    $qr         = session('academy.quiz_result');
                                    $quizResult = ($qr && ($qr['item_id'] ?? null) === $item->id) ? $qr : null;
                                @endphp
                                <x-academy::quiz-player
                                    :item="$item"
                                    :isEnrolled="$isEnrolled"
                                    :course="$course"
                                    :lesson="$lesson"
                                    :quizResult="$quizResult"
                                />
                            @endif

                        {{-- ── TYPE CHOICE (sondage / vote simple, non noté) ── --}}
                        @elseif($item->type === 'choice')
                            @php
                                $choiceOptions  = \Modules\Academy\Services\ChoiceService::options($item);
                                $choiceQuestion = \Modules\Academy\Services\ChoiceService::question($item);
                                $choiceMultiple = \Modules\Academy\Services\ChoiceService::allowsMultiple($item);
                                $choiceAnon     = \Modules\Academy\Services\ChoiceService::isAnonymous($item);
                                // Le formateur visualise via le mode prévisualisation : il voit
                                // toujours les résultats, mais ne vote pas (aucune progression).
                                $choiceIsManager = (bool) ($isPreview ?? false);
                                // C3 (anti N+1) : on consulte la map préchargée par le
                                // contrôleur (1 requête pour toute la leçon) au lieu de
                                // requêter par item. Repli inoffensif si la variable
                                // n'est pas fournie (ex. autre point d'entrée).
                                $choiceUserVote  = (! $choiceIsManager && auth()->check())
                                    ? \Modules\Academy\Services\ChoiceService::userVote($item, auth()->user(), $choiceVotes ?? null)
                                    : null;
                                $choiceHasVoted    = $choiceUserVote !== null;
                                $choiceShowResults = $choiceIsManager
                                    || \Modules\Academy\Services\ChoiceService::resultsVisibleToStudent($item, $choiceHasVoted);
                                $choiceTally = $choiceShowResults
                                    ? \Modules\Academy\Services\ChoiceService::tally($item)
                                    : null;
                            @endphp
                            @if($hasAccess && count($choiceOptions) >= 2)
                                <div class="academy-choice">
                                    {{-- Énoncé : e() (anti-XSS) ; l'énoncé est du texte simple. --}}
                                    @if($choiceQuestion !== '')
                                        <p class="academy-choice-question">{{ $choiceQuestion }}</p>
                                    @endif

                                    @if($choiceIsManager)
                                        {{-- Prévisualisation : pas de vote (le gérant n'enregistre rien). --}}
                                        <p class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                            Le vote est désactivé en prévisualisation. Les résultats ci-dessous reflètent les votes réels.
                                        </p>
                                    @else
                                        {{-- Formulaire de vote a11y (radio = choix unique, case = choix multiple).
                                             Le vote est modifiable : la sélection courante est pré-cochée. --}}
                                        <form method="POST"
                                              action="{{ route('academy.choice.vote', [$course, $lesson, $item->id]) }}"
                                              class="academy-choice-form">
                                            @csrf
                                            <fieldset style="border: 0; padding: 0; margin: 0;">
                                                <legend class="visually-hidden">{{ $choiceQuestion !== '' ? $choiceQuestion : 'Sondage' }}</legend>
                                                @foreach($choiceOptions as $ci => $optLabel)
                                                    <label class="academy-choice-option" for="choice-{{ $item->id }}-{{ $ci }}">
                                                        <input type="{{ $choiceMultiple ? 'checkbox' : 'radio' }}"
                                                               id="choice-{{ $item->id }}-{{ $ci }}"
                                                               name="{{ $choiceMultiple ? 'choices[]' : 'choice' }}"
                                                               value="{{ $ci }}"
                                                               @checked(is_array($choiceUserVote) && in_array($ci, $choiceUserVote, true))
                                                               style="width: 24px; height: 24px; flex: 0 0 auto; margin: 0;">
                                                        <span>{{ $optLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </fieldset>
                                            <div class="mt-3">
                                                <x-core::button type="submit" variant="primary" size="sm">
                                                    {{ $choiceHasVoted ? 'Modifier mon vote' : 'Voter' }}
                                                </x-core::button>
                                            </div>
                                        </form>

                                        @if($choiceHasVoted)
                                            <p class="mt-2" role="status" style="font-size: 0.85rem; color: #166534;">
                                                <span aria-hidden="true">✅</span> Votre vote est enregistré. Vous pouvez le modifier à tout moment.
                                            </p>
                                        @endif
                                    @endif

                                    {{-- Résultats agrégés (selon la visibilité). On ne montre QUE des
                                         comptes/pourcentages anonymisés, jamais l'identité des votants. --}}
                                    @if($choiceShowResults && $choiceTally)
                                        <div class="academy-choice-results mt-3" role="group" aria-label="Résultats du sondage">
                                            @foreach($choiceTally['options'] as $row)
                                                <div class="academy-choice-result">
                                                    <div class="academy-choice-result-head">
                                                        <span>{{ $row['label'] }}</span>
                                                        <span class="academy-choice-result-count">{{ $row['count'] }} ({{ $row['percent'] }}%)</span>
                                                    </div>
                                                    <div class="academy-choice-bar" role="presentation">
                                                        <span class="academy-choice-bar-fill" style="width: {{ $row['percent'] }}%;"></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <p class="text-muted mt-1" style="font-size: 0.8rem;">
                                                {{ $choiceTally['total_voters'] }} {{ $choiceTally['total_voters'] === 1 ? 'votant' : 'votants' }}
                                            </p>
                                        </div>

                                        {{-- Liste des votants : UNIQUEMENT au formateur (prévisualisation) ET
                                             si le sondage n'est PAS anonyme. Jamais affichée à un étudiant. --}}
                                        @if($choiceIsManager && !$choiceAnon)
                                            @php
                                                $choiceVoters = \Modules\Academy\Services\ChoiceService::voters($item);
                                            @endphp
                                            @if($choiceVoters->isNotEmpty())
                                                <div class="mt-2" style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">
                                                    <strong>Votants :</strong>
                                                    {{ $choiceVoters->map(fn ($u) => $u->name ?? '(nom inconnu)')->implode(', ') }}
                                                </div>
                                            @endif
                                        @endif
                                    @elseif(!$choiceIsManager && \Modules\Academy\Services\ChoiceService::visibility($item) === 'after_vote' && !$choiceHasVoted)
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">
                                            Les résultats s'afficheront après votre vote.
                                        </p>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour participer au sondage
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour participer à ce sondage
                                        @else
                                            Sondage en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour voter.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour participer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE FEEDBACK (questionnaire de rétroaction, multi-questions, non noté) ── --}}
                        @elseif($item->type === 'feedback')
                            @php
                                $fbQuestions = \Modules\Academy\Services\FeedbackService::questions($item);
                                $fbIntro     = \Modules\Academy\Services\FeedbackService::intro($item);
                                $fbAnon      = \Modules\Academy\Services\FeedbackService::isAnonymous($item);
                                // Le formateur visualise via la prévisualisation : il voit les
                                // résultats agrégés, ne répond pas (aucune progression).
                                $fbIsManager = (bool) ($isPreview ?? false);
                                $fbResponded = (! $fbIsManager && auth()->check())
                                    ? \Modules\Academy\Services\FeedbackService::hasResponded($item, auth()->user())
                                    : false;
                                // Pré-remplissage d'un sondage NOMMÉ (réponse modifiable) : les
                                // réponses précédentes de l'utilisateur courant, PRÉCHARGÉES par le
                                // contrôleur (C2 : aucune requête dans la vue). Jamais en anonyme
                                // (aucune réponse n'est liée à une identité ; previousAnswers le borne).
                                $fbPrev = (! $fbIsManager && auth()->check())
                                    ? \Modules\Academy\Services\FeedbackService::previousAnswers($item, auth()->user(), $feedbackResponses ?? null)
                                    : [];
                                // Résultats UNIQUEMENT pour le formateur (jamais l'étudiant).
                                $fbResults = $fbIsManager ? \Modules\Academy\Services\FeedbackService::results($item) : null;
                            @endphp
                            @if($hasAccess && count($fbQuestions) >= 1)
                                <div class="academy-feedback">
                                    @if($fbIntro !== '')
                                        <p class="academy-feedback-intro">{{ $fbIntro }}</p>
                                    @endif

                                    @if($fbIsManager)
                                        {{-- Prévisualisation formateur : aucune réponse, résultats AGRÉGÉS
                                             et anonymisés (jamais d'identité, même si non anonyme). --}}
                                        <p class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                            La réponse est désactivée en prévisualisation. Les résultats ci-dessous (agrégés et anonymisés) reflètent les réponses réelles.
                                        </p>
                                        <div class="academy-feedback-results" role="group" aria-label="Résultats du sondage">
                                            @foreach($fbResults['questions'] as $qr)
                                                <div style="margin-bottom: 1rem;">
                                                    <p style="font-weight: 600; margin: 0 0 0.4rem;">{{ $qr['label'] }}</p>
                                                    @if($qr['type'] === 'rating')
                                                        @for($s = 1; $s <= $qr['scale']; $s++)
                                                            @php
                                                                $cnt = $qr['counts'][$s] ?? 0;
                                                                $pct = ($qr['answered'] ?? 0) > 0 ? (int) round($cnt / $qr['answered'] * 100) : 0;
                                                            @endphp
                                                            <div class="academy-feedback-result">
                                                                <div class="academy-feedback-result-head"><span>{{ $s }}</span><span>{{ $cnt }} ({{ $pct }}%)</span></div>
                                                                <div class="academy-feedback-bar" role="presentation"><span class="academy-feedback-bar-fill" style="width: {{ $pct }}%;"></span></div>
                                                            </div>
                                                        @endfor
                                                        @if(! is_null($qr['average']))
                                                            <p class="text-muted" style="font-size: 0.8rem;">Moyenne : {{ $qr['average'] }} / {{ $qr['scale'] }}</p>
                                                        @endif
                                                    @elseif($qr['type'] === 'choice')
                                                        @foreach($qr['options'] as $oi => $ol)
                                                            @php
                                                                $cnt = $qr['counts'][$oi] ?? 0;
                                                                $pct = ($qr['answered'] ?? 0) > 0 ? (int) round($cnt / $qr['answered'] * 100) : 0;
                                                            @endphp
                                                            <div class="academy-feedback-result">
                                                                <div class="academy-feedback-result-head"><span>{{ $ol }}</span><span>{{ $cnt }} ({{ $pct }}%)</span></div>
                                                                <div class="academy-feedback-bar" role="presentation"><span class="academy-feedback-bar-fill" style="width: {{ $pct }}%;"></span></div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        @if(count($qr['texts']) > 0)
                                                            <ul class="academy-feedback-texts">
                                                                @foreach($qr['texts'] as $t)
                                                                    <li>{{ $t }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="text-muted" style="font-size: 0.85rem;">Aucune réponse pour l'instant.</p>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                            <p class="text-muted" style="font-size: 0.8rem;">
                                                {{ $fbResults['total'] }} {{ $fbResults['total'] === 1 ? 'réponse' : 'réponses' }}
                                            </p>
                                        </div>
                                    @elseif($fbAnon && $fbResponded)
                                        {{-- Sondage anonyme déjà rempli (borne de session) : remerciement seul,
                                             jamais les résultats (le retour va au formateur). --}}
                                        <p role="status" style="font-size: 0.9rem; color: #166534;">
                                            <span aria-hidden="true">✅</span> Merci, votre réponse anonyme a été enregistrée.
                                        </p>
                                    @else
                                        {{-- Formulaire de réponse a11y (fieldset/legend par question). L'étudiant
                                             ne voit JAMAIS les résultats : un sondage est un retour au formateur. --}}
                                        <form method="POST"
                                              action="{{ route('academy.feedback.submit', [$course, $lesson, $item->id]) }}"
                                              class="academy-feedback-form">
                                            @csrf
                                            @foreach($fbQuestions as $qi => $q)
                                                <fieldset class="academy-feedback-q">
                                                    <legend>{{ $q['label'] }}@if($q['required']) <span class="academy-feedback-required" aria-hidden="true">*</span><span class="visually-hidden"> (obligatoire)</span>@endif</legend>
                                                    @if($q['type'] === 'rating')
                                                        <div class="academy-feedback-rating" role="radiogroup" aria-label="{{ $q['label'] }}">
                                                            @for($s = 1; $s <= $q['scale']; $s++)
                                                                <label for="fb-{{ $item->id }}-{{ $qi }}-{{ $s }}">
                                                                    <input type="radio" id="fb-{{ $item->id }}-{{ $qi }}-{{ $s }}" name="answers[{{ $qi }}]" value="{{ $s }}"
                                                                           @checked((string) ($fbPrev[$qi] ?? '') === (string) $s) @required($q['required'])
                                                                           style="width: 20px; height: 20px; margin: 0;">
                                                                    <span>{{ $s }}</span>
                                                                </label>
                                                            @endfor
                                                        </div>
                                                    @elseif($q['type'] === 'choice')
                                                        @foreach($q['options'] as $oi => $ol)
                                                            <label class="academy-feedback-opt" for="fb-{{ $item->id }}-{{ $qi }}-{{ $oi }}">
                                                                <input type="radio" id="fb-{{ $item->id }}-{{ $qi }}-{{ $oi }}" name="answers[{{ $qi }}]" value="{{ $oi }}"
                                                                       @checked((string) ($fbPrev[$qi] ?? '') === (string) $oi) @required($q['required'])
                                                                       style="width: 24px; height: 24px; flex: 0 0 auto; margin: 0;">
                                                                <span>{{ $ol }}</span>
                                                            </label>
                                                        @endforeach
                                                    @else
                                                        <textarea class="academy-feedback-text" name="answers[{{ $qi }}]" rows="3"
                                                                  maxlength="{{ \Modules\Academy\Services\FeedbackService::MAX_TEXT }}"
                                                                  aria-label="{{ $q['label'] }}" @required($q['required'])>{{ is_string($fbPrev[$qi] ?? null) ? $fbPrev[$qi] : '' }}</textarea>
                                                    @endif
                                                </fieldset>
                                            @endforeach
                                            <div class="mt-2">
                                                <x-core::button type="submit" variant="primary" size="sm">
                                                    {{ (! $fbAnon && $fbResponded) ? 'Modifier ma réponse' : 'Envoyer ma réponse' }}
                                                </x-core::button>
                                            </div>
                                        </form>

                                        @if(! $fbAnon && $fbResponded)
                                            <p class="mt-2" role="status" style="font-size: 0.85rem; color: #166534;">
                                                <span aria-hidden="true">✅</span> Votre réponse est enregistrée. Vous pouvez la modifier à tout moment.
                                            </p>
                                        @endif
                                        @if($fbAnon)
                                            <p class="text-muted mt-2" style="font-size: 0.8rem;">
                                                <span aria-hidden="true">🔒</span> Ce sondage est anonyme : votre réponse n'est associée à aucune identité.
                                            </p>
                                        @endif
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour répondre au sondage
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour répondre à ce sondage
                                        @else
                                            Sondage en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour répondre.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour répondre.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE FORUM (discussions attachées à la leçon, type Moodle « Forum ») ── --}}
                        @elseif($item->type === 'forum')
                            @php
                                $forumIntro       = \Modules\Academy\Services\ForumService::intro($item);
                                $forumLocked      = \Modules\Academy\Services\ForumService::isLocked($item);
                                $forumAllowTopics = \Modules\Academy\Services\ForumService::allowsStudentTopics($item);
                                // Gérant de CE cours (admin OU owner/instructor) : peut modérer ET
                                // contribuer même hors inscription. L'autorisation réelle est TOUJOURS
                                // re-vérifiée côté serveur (ForumController) ; ici c'est de l'affichage.
                                $forumCanModerate = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                // Peut ouvrir un sujet : un gérant toujours ; un étudiant si l'accès
                                // est accordé, que les sujets étudiants sont permis et le forum non verrouillé.
                                $forumCanCreate = $forumCanModerate
                                    || ($hasAccess && auth()->check() && $forumAllowTopics && ! $forumLocked);
                                $forumTopics = ($hasAccess || $forumCanModerate)
                                    ? \Modules\Academy\Services\ForumService::topics($item)
                                    : null;
                            @endphp
                            @if($hasAccess || $forumCanModerate)
                                <div class="academy-forum">
                                    @if($forumIntro !== '')
                                        <p class="academy-forum-intro">{{ $forumIntro }}</p>
                                    @endif

                                    @if($forumLocked)
                                        <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                            <span aria-hidden="true">🔒</span> Ce forum est en lecture seule.
                                            @if($forumCanModerate) (Vous pouvez tout de même contribuer en tant que gérant.) @endif
                                        </p>
                                    @endif

                                    {{-- Nouveau sujet (a11y : champs étiquetés ; honeypot caché anti-spam). --}}
                                    @if($forumCanCreate)
                                        <details class="academy-forum-topic" style="border-style: dashed;">
                                            <summary>
                                                <span class="academy-forum-topic-title">+ Ouvrir un nouveau sujet</span>
                                            </summary>
                                            <div class="academy-forum-body">
                                                <form method="POST" action="{{ route('academy.forum.topics.create', [$course, $lesson, $item->id]) }}">
                                                    @csrf
                                                    {{-- Honeypot MAISON : doit rester vide ; hors écran, non focusable. --}}
                                                    <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                        <label for="forum-hp-{{ $item->id }}">Ne pas remplir</label>
                                                        <input type="text" id="forum-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\ForumService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                    </div>
                                                    <label for="forum-title-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">Titre du sujet</label>
                                                    <input type="text" id="forum-title-{{ $item->id }}" name="title" class="academy-forum-field"
                                                           maxlength="{{ \Modules\Academy\Services\ForumService::TITLE_MAX }}" required>
                                                    <label for="forum-body-{{ $item->id }}" style="font-size: 0.82rem; font-weight: 600;">Message</label>
                                                    <textarea id="forum-body-{{ $item->id }}" name="body" class="academy-forum-text" rows="3"
                                                              maxlength="{{ \Modules\Academy\Services\ForumService::BODY_MAX }}" required></textarea>
                                                    <div class="mt-2">
                                                        <x-core::button type="submit" variant="primary" size="sm">Publier le sujet</x-core::button>
                                                    </div>
                                                </form>
                                            </div>
                                        </details>
                                    @elseif(! $forumAllowTopics && ! $forumLocked)
                                        <p class="text-muted" style="font-size: 0.85rem;">Seul le formateur peut ouvrir de nouveaux sujets ; vous pouvez répondre aux sujets existants.</p>
                                    @endif

                                    {{-- Liste des sujets : épinglés en tête, puis récents (pagination simple). --}}
                                    @forelse($forumTopics as $topic)
                                        @php
                                            $topicReplyAllowed = $forumCanModerate || (! $forumLocked && ! $topic->is_locked && $hasAccess && auth()->check());
                                        @endphp
                                        <details class="academy-forum-topic" wire:key="forum-topic-{{ $topic->id }}" @if(request('forum_open') == $topic->id) open @endif>
                                            <summary>
                                                <span class="academy-forum-topic-title">
                                                    @if($topic->is_pinned)<span aria-hidden="true" title="Épinglé">📌</span><span class="visually-hidden">Épinglé</span> @endif
                                                    @if($topic->is_locked)<span aria-hidden="true" title="Verrouillé">🔒</span><span class="visually-hidden">Verrouillé</span> @endif
                                                    {{ $topic->title }}
                                                    <span class="academy-forum-badge">{{ $topic->posts_count }} {{ $topic->posts_count === 1 ? 'réponse' : 'réponses' }}</span>
                                                </span>
                                                <span class="academy-forum-meta">{{ $topic->user?->name ?? '(inconnu)' }} · {{ $topic->created_at?->diffForHumans() }}</span>
                                            </summary>
                                            <div class="academy-forum-body">
                                                {{-- SÉCURITÉ : renderRichText() (markdown, html_input=strip) neutralise tout HTML brut → anti-XSS. --}}
                                                <div class="prose academy-richtext">{!! \Modules\Academy\Models\LessonItem::renderRichText($topic->body) !!}</div>

                                                @foreach($topic->posts as $post)
                                                    <div class="academy-forum-post" wire:key="forum-post-{{ $post->id }}">
                                                        <div class="academy-forum-post-meta">{{ $post->user?->name ?? '(inconnu)' }} · {{ $post->created_at?->diffForHumans() }}</div>
                                                        <div class="prose academy-richtext">{!! \Modules\Academy\Models\LessonItem::renderRichText($post->body) !!}</div>
                                                        @if($forumCanModerate)
                                                            {{-- Modération : confirmation INLINE 2 temps (details), jamais de confirm() natif. --}}
                                                            <details class="mt-1">
                                                                <summary style="cursor: pointer; font-size: 0.78rem; color: var(--sys-action-danger, #DC2626);">Supprimer cette réponse</summary>
                                                                <form method="POST" action="{{ route('academy.forum.posts.delete', [$course, $lesson, $item->id, $post->id]) }}" class="mt-1">
                                                                    @csrf
                                                                    <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression</x-core::button>
                                                                </form>
                                                            </details>
                                                        @endif
                                                    </div>
                                                @endforeach

                                                {{-- Liste bornée (ForumService::POSTS_PER_TOPIC) : si le sujet a plus de
                                                     réponses que celles chargées, on l'indique sans charger le fil entier. --}}
                                                @if($topic->posts_count > $topic->posts->count())
                                                    <p class="text-muted mt-1" style="font-size: 0.8rem;">
                                                        {{ $topic->posts->count() }} des {{ $topic->posts_count }} réponses affichées (les plus anciennes en premier).
                                                    </p>
                                                @endif

                                                {{-- Répondre --}}
                                                @if($topicReplyAllowed)
                                                    <form method="POST" action="{{ route('academy.forum.topics.reply', [$course, $lesson, $item->id, $topic->id]) }}" class="mt-2">
                                                        @csrf
                                                        <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                            <label for="forum-rhp-{{ $topic->id }}">Ne pas remplir</label>
                                                            <input type="text" id="forum-rhp-{{ $topic->id }}" name="{{ \Modules\Academy\Services\ForumService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                        </div>
                                                        <label for="forum-reply-{{ $topic->id }}" style="font-size: 0.82rem; font-weight: 600;">Répondre</label>
                                                        <textarea id="forum-reply-{{ $topic->id }}" name="body" class="academy-forum-text" rows="2"
                                                                  maxlength="{{ \Modules\Academy\Services\ForumService::BODY_MAX }}" required></textarea>
                                                        <div class="mt-2">
                                                            <x-core::button type="submit" variant="secondary" size="sm">Répondre</x-core::button>
                                                        </div>
                                                    </form>
                                                @elseif($topic->is_locked)
                                                    <p class="text-muted mt-2" style="font-size: 0.82rem;"><span aria-hidden="true">🔒</span> Ce sujet est verrouillé.</p>
                                                @endif

                                                {{-- Modération du sujet (gérant) : épingler / verrouiller (bascule) + supprimer (2 temps). --}}
                                                @if($forumCanModerate)
                                                    <div class="academy-forum-mod">
                                                        <form method="POST" action="{{ route('academy.forum.topics.pin', [$course, $lesson, $item->id, $topic->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="ghost" size="sm">{{ $topic->is_pinned ? 'Désépingler' : 'Épingler' }}</x-core::button>
                                                        </form>
                                                        <form method="POST" action="{{ route('academy.forum.topics.lock', [$course, $lesson, $item->id, $topic->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="ghost" size="sm">{{ $topic->is_locked ? 'Déverrouiller' : 'Verrouiller' }}</x-core::button>
                                                        </form>
                                                        <details>
                                                            <summary style="cursor: pointer; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);">Supprimer le sujet</summary>
                                                            <form method="POST" action="{{ route('academy.forum.topics.delete', [$course, $lesson, $item->id, $topic->id]) }}" class="mt-1">
                                                                @csrf
                                                                <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression du sujet</x-core::button>
                                                            </form>
                                                        </details>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @empty
                                        <p class="text-muted" style="font-size: 0.9rem;">Aucun sujet pour l'instant. @if($forumCanCreate) Soyez le premier à en ouvrir un. @endif</p>
                                    @endforelse

                                    @if($forumTopics && $forumTopics->hasPages())
                                        <div class="mt-3">{{ $forumTopics->withQueryString()->links() }}</div>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder au forum
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour participer au forum
                                        @else
                                            Forum en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour participer aux discussions.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour participer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE WIKI (F19) : pages collaboratives + historique ── --}}
                        @elseif($item->type === 'wiki')
                            @php
                                $wikiIntro       = \Modules\Academy\Services\WikiService::intro($item);
                                $wikiAllowEdit   = \Modules\Academy\Services\WikiService::allowsStudentEdit($item);
                                // Gérant de CE cours (admin OU owner/instructor) : modère ET contribue
                                // même hors inscription. L'autorisation réelle est TOUJOURS re-vérifiée
                                // serveur (WikiController) ; ici c'est de l'affichage.
                                $wikiCanModerate = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                $wikiPages       = ($hasAccess || $wikiCanModerate)
                                    ? \Modules\Academy\Services\WikiService::pages($item)
                                    : collect();
                                // Page courante : ?wpage_{id}=slug, sinon accueil, sinon 1re page.
                                $wikiSlug    = request('wpage_'.$item->id);
                                $wikiCurrent = $wikiSlug ? $wikiPages->firstWhere('slug', $wikiSlug) : null;
                                $wikiCurrent = $wikiCurrent ?: ($wikiPages->firstWhere('is_home', true) ?: $wikiPages->first());
                                // Peut créer une page : gérant toujours ; étudiant si inscrit + édition permise.
                                $wikiCanCreate = $wikiCanModerate || ($hasAccess && auth()->check() && $wikiAllowEdit);
                                // Peut éditer la page courante : gérant ; ou inscrit + édition permise + page non verrouillée.
                                $wikiCanEdit = $wikiCurrent && ($wikiCanModerate || ($hasAccess && auth()->check() && $wikiAllowEdit && ! $wikiCurrent->is_locked));
                                // Peut restaurer : gérant ; ou auteur (created_by) sous les mêmes règles d'édition.
                                $wikiCanRestore = $wikiCurrent && ($wikiCanModerate || ($wikiCanEdit && (int) ($wikiCurrent->created_by ?? 0) === (int) auth()->id()));
                                // Historique demandé pour la page courante ?
                                $wikiHistOpen  = $wikiCurrent && (int) request('whist_'.$item->id) === (int) $wikiCurrent->id;
                                $wikiRevisions = $wikiHistOpen ? \Modules\Academy\Services\WikiService::revisions($wikiCurrent) : null;
                            @endphp
                            @if($hasAccess || $wikiCanModerate)
                                <div class="academy-wiki">
                                    @if($wikiIntro !== '')
                                        <p class="academy-wiki-intro">{{ $wikiIntro }}</p>
                                    @endif

                                    @if(! $wikiAllowEdit)
                                        <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                            <span aria-hidden="true">🔒</span> Wiki en lecture seule pour les étudiants.
                                            @if($wikiCanModerate) (Vous pouvez tout de même éditer en tant que gérant.) @endif
                                        </p>
                                    @endif

                                    <div class="academy-wiki-layout">
                                        {{-- Navigation : pages (accueil en tête) + nouvelle page. --}}
                                        <nav class="academy-wiki-nav" aria-label="Pages du wiki">
                                            <strong style="font-size: 0.8rem;">Pages</strong>
                                            <ul>
                                                @forelse($wikiPages as $p)
                                                    <li>
                                                        <a href="?wpage_{{ $item->id }}={{ urlencode($p->slug) }}#item-{{ $item->id }}"
                                                           @if($wikiCurrent && $p->id === $wikiCurrent->id) aria-current="page" @endif>
                                                            @if($p->is_home)<span aria-hidden="true" title="Accueil">🏠</span> @endif
                                                            {{ $p->title }}
                                                            @if($p->is_locked)<span aria-hidden="true" title="Verrouillée">🔒</span><span class="visually-hidden">Verrouillée</span>@endif
                                                        </a>
                                                    </li>
                                                @empty
                                                    <li class="text-muted" style="font-size: 0.82rem; padding: 4px 8px;">Aucune page pour l'instant.</li>
                                                @endforelse
                                            </ul>

                                            @if($wikiCanCreate)
                                                <details class="mt-2">
                                                    <summary style="cursor: pointer; font-size: 0.82rem; font-weight: 600;">+ Nouvelle page</summary>
                                                    <form method="POST" action="{{ route('academy.wiki.pages.create', [$course, $lesson, $item->id]) }}" class="mt-1">
                                                        @csrf
                                                        {{-- Honeypot MAISON : doit rester vide ; hors écran, non focusable. --}}
                                                        <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                            <label for="wiki-hp-{{ $item->id }}">Ne pas remplir</label>
                                                            <input type="text" id="wiki-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\WikiService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                        </div>
                                                        <label for="wiki-new-title-{{ $item->id }}" style="font-size: 0.8rem; font-weight: 600;">Titre de la page</label>
                                                        <input type="text" id="wiki-new-title-{{ $item->id }}" name="title" class="academy-wiki-field"
                                                               maxlength="{{ \Modules\Academy\Services\WikiService::TITLE_MAX }}" required>
                                                        <label for="wiki-new-body-{{ $item->id }}" style="font-size: 0.8rem; font-weight: 600;">Contenu (markdown ; lien interne : [[Titre]])</label>
                                                        <textarea id="wiki-new-body-{{ $item->id }}" name="body" class="academy-wiki-text" rows="4"
                                                                  maxlength="{{ \Modules\Academy\Services\WikiService::BODY_MAX }}"></textarea>
                                                        <div class="mt-2"><x-core::button type="submit" variant="primary" size="sm">Créer la page</x-core::button></div>
                                                    </form>
                                                </details>
                                            @endif
                                        </nav>

                                        {{-- Contenu de la page courante. --}}
                                        <div class="academy-wiki-main">
                                            @if($wikiCurrent)
                                                <h3 class="academy-wiki-page-title">
                                                    {{ $wikiCurrent->title }}
                                                    @if($wikiCurrent->is_locked)<span class="academy-wiki-badge"><span aria-hidden="true">🔒</span> Verrouillée</span>@endif
                                                </h3>
                                                <p class="academy-wiki-meta">
                                                    Version {{ $wikiCurrent->revision }} · modifiée par {{ $wikiCurrent->editor?->name ?? '(inconnu)' }}
                                                    @if($wikiCurrent->updated_at) · {{ $wikiCurrent->updated_at->diffForHumans() }} @endif
                                                </p>

                                                {{-- SÉCURITÉ : renderBody = markdown html_input=strip (anti-XSS) + liens [[..]] internes. --}}
                                                <div class="prose academy-richtext">{!! \Modules\Academy\Services\WikiService::renderBody($item, $wikiCurrent, $wikiPages) !!}</div>

                                                <div class="academy-wiki-actions">
                                                    {{-- Historique (lecture seule) : bascule via paramètre de requête. --}}
                                                    @if($wikiHistOpen)
                                                        <x-core::button :href="'?wpage_'.$item->id.'='.urlencode($wikiCurrent->slug).'#item-'.$item->id" variant="ghost" size="sm">Masquer l'historique</x-core::button>
                                                    @else
                                                        <x-core::button :href="'?wpage_'.$item->id.'='.urlencode($wikiCurrent->slug).'&whist_'.$item->id.'='.$wikiCurrent->id.'#item-'.$item->id" variant="ghost" size="sm">Historique ({{ $wikiCurrent->revision - 1 }})</x-core::button>
                                                    @endif

                                                    {{-- Modération (gérant) : verrouiller (bascule) + supprimer (sauf accueil, 2 temps). --}}
                                                    @if($wikiCanModerate)
                                                        <form method="POST" action="{{ route('academy.wiki.pages.lock', [$course, $lesson, $item->id, $wikiCurrent->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="ghost" size="sm">{{ $wikiCurrent->is_locked ? 'Déverrouiller' : 'Verrouiller' }}</x-core::button>
                                                        </form>
                                                        @unless($wikiCurrent->is_home)
                                                            <details>
                                                                <summary style="cursor: pointer; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);">Supprimer la page</summary>
                                                                <form method="POST" action="{{ route('academy.wiki.pages.delete', [$course, $lesson, $item->id, $wikiCurrent->id]) }}" class="mt-1">
                                                                    @csrf
                                                                    <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression de la page</x-core::button>
                                                                </form>
                                                            </details>
                                                        @endunless
                                                    @endif
                                                </div>

                                                {{-- Éditer la page courante (collaboratif). Confirmation par dépliage, jamais de popup. --}}
                                                @if($wikiCanEdit)
                                                    <details>
                                                        <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600;">Modifier cette page</summary>
                                                        <form method="POST" action="{{ route('academy.wiki.pages.update', [$course, $lesson, $item->id, $wikiCurrent->id]) }}" class="mt-1">
                                                            @csrf
                                                            <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                                <label for="wiki-ehp-{{ $wikiCurrent->id }}">Ne pas remplir</label>
                                                                <input type="text" id="wiki-ehp-{{ $wikiCurrent->id }}" name="{{ \Modules\Academy\Services\WikiService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                            </div>
                                                            <label for="wiki-edit-title-{{ $wikiCurrent->id }}" style="font-size: 0.8rem; font-weight: 600;">Titre</label>
                                                            <input type="text" id="wiki-edit-title-{{ $wikiCurrent->id }}" name="title" class="academy-wiki-field"
                                                                   value="{{ $wikiCurrent->title }}" maxlength="{{ \Modules\Academy\Services\WikiService::TITLE_MAX }}" required>
                                                            <label for="wiki-edit-body-{{ $wikiCurrent->id }}" style="font-size: 0.8rem; font-weight: 600;">Contenu (markdown ; lien interne : [[Titre]])</label>
                                                            <textarea id="wiki-edit-body-{{ $wikiCurrent->id }}" name="body" class="academy-wiki-text" rows="8"
                                                                      maxlength="{{ \Modules\Academy\Services\WikiService::BODY_MAX }}">{{ $wikiCurrent->body }}</textarea>
                                                            <div class="mt-2"><x-core::button type="submit" variant="secondary" size="sm">Enregistrer la page</x-core::button></div>
                                                        </form>
                                                    </details>
                                                @endif

                                                {{-- Panneau historique (révisions, lecture seule + restauration gatée). --}}
                                                @if($wikiHistOpen && $wikiRevisions)
                                                    <div class="mt-3" style="border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                                        <strong style="font-size: 0.85rem;">Historique des révisions</strong>
                                                        @forelse($wikiRevisions as $rev)
                                                            <div class="academy-wiki-rev" wire:key="wiki-rev-{{ $rev->id }}">
                                                                <span>Version {{ $rev->revision }} · {{ $rev->user?->name ?? '(inconnu)' }} · {{ $rev->snapshot_at?->diffForHumans() }}</span>
                                                                @if($wikiCanRestore)
                                                                    <form method="POST" action="{{ route('academy.wiki.pages.restore', [$course, $lesson, $item->id, $wikiCurrent->id, $rev->id]) }}">
                                                                        @csrf
                                                                        <x-core::button type="submit" variant="ghost" size="sm">Restaurer cette version</x-core::button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        @empty
                                                            <p class="text-muted" style="font-size: 0.85rem;">Aucune révision : cette page n'a pas encore été modifiée.</p>
                                                        @endforelse
                                                        @if($wikiRevisions->hasPages())
                                                            <div class="mt-2">{{ $wikiRevisions->withQueryString()->links() }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                <p class="text-muted" style="font-size: 0.9rem;">Ce wiki n'a pas encore de page. @if($wikiCanCreate) Créez la première page (elle deviendra l'accueil). @endif</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder au wiki
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder au wiki
                                        @else
                                            Wiki en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour consulter et contribuer au wiki.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit : inscrivez-vous pour contribuer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant : achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">Se connecter</x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">Créer un compte</x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">S'inscrire gratuitement</x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">Acheter ce cours</x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE DOC ── --}}
                        @elseif(in_array($item->type, ['doc', 'document'], true))
                            @if($hasAccess)
                                {{--
                                    GATING DOC (identique au gating vidéo) :
                                    Le contenu textuel n'est injecté dans le DOM QUE si $hasAccess === true.
                                    Un visiteur non inscrit ne voit PAS le rich_text dans le HTML rendu.
                                --}}
                                <div class="prose academy-richtext">
                                    @php
                                        $renderedDoc = \Modules\Academy\Models\LessonItem::renderRichText($item->payload['rich_text'] ?? null);
                                    @endphp
                                    @if($renderedDoc !== '')
                                        {{-- SÉCURITÉ : renderRichText() interprète le markdown avec html_input=strip
                                             (tout HTML brut est retiré) → liste blanche, aucune XSS stockée possible. --}}
                                        {!! $renderedDoc !!}
                                    @else
                                        <p class="text-muted">Contenu du document à venir.</p>
                                    @endif

                                    @if(!empty($item->payload['attachments']))
                                        <div class="mt-3">
                                            <strong style="font-size: 0.9rem;">Pièces jointes :</strong>
                                            <ul class="mt-1">
                                                @foreach($item->payload['attachments'] as $attachment)
                                                    <li><a href="{{ $attachment['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $attachment['name'] ?? 'Télécharger' }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- Panneau d'accès refusé - même logique que le type video (pas de contenu dans le DOM) --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour lire ce document
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à ce document
                                        @else
                                            Contenu en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour accéder aux documents de ce cours.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour lire tous les documents.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE BASE DE DONNÉES (F20, parité Moodle « Database ») ── --}}
                        @elseif($item->type === 'database')
                            @php
                                $dbIntro          = \Modules\Academy\Services\DatabaseService::intro($item);
                                $dbAllowAdd       = \Modules\Academy\Services\DatabaseService::allowsStudentAdd($item);
                                // Gérant de CE cours (admin OU owner/instructor) : modère ET contribue
                                // même hors inscription. L'autorisation réelle est TOUJOURS re-vérifiée
                                // serveur (DatabaseController) ; ici c'est de l'affichage.
                                $dbCanModerate = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                $dbFields  = ($hasAccess || $dbCanModerate)
                                    ? \Modules\Academy\Services\DatabaseService::fields($item)
                                    : collect();
                                $dbEntries = ($hasAccess || $dbCanModerate)
                                    ? \Modules\Academy\Services\DatabaseService::entries($item, auth()->id(), $dbCanModerate)
                                    : null;
                                // Peut ajouter une fiche : gérant toujours ; inscrit si l'ajout est permis.
                                $dbCanAdd = $dbCanModerate || ($hasAccess && auth()->check() && $dbAllowAdd);
                            @endphp
                            @if($hasAccess || $dbCanModerate)
                                <div class="academy-db">
                                    @if($dbIntro !== '')
                                        <p class="academy-db-intro">{{ $dbIntro }}</p>
                                    @endif

                                    @if(! $dbAllowAdd)
                                        <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                            <span aria-hidden="true">🔒</span> L'ajout de fiches est réservé au formateur.
                                            @if($dbCanModerate) (Vous pouvez tout de même ajouter une fiche en tant que gérant.) @endif
                                        </p>
                                    @endif

                                    {{-- Collection des fiches (approuvées pour tous ; en attente visibles à
                                         l'auteur + au gérant ; déjà filtrées côté service, anti-fuite). --}}
                                    <div class="academy-db-entries">
                                        @forelse($dbEntries as $entry)
                                            @php $dbVals = \Modules\Academy\Services\DatabaseService::valuesByField($entry); @endphp
                                            @php $dbIsOwner = auth()->check() && (int) ($entry->user_id ?? 0) === (int) auth()->id(); @endphp
                                            <div class="academy-db-entry @if(! $entry->is_approved) is-pending @endif" wire:key="db-entry-{{ $entry->id }}">
                                                <p class="academy-db-meta">
                                                    Par {{ $entry->author?->name ?? '(inconnu)' }}
                                                    @if($entry->created_at) · {{ $entry->created_at->diffForHumans() }} @endif
                                                    @unless($entry->is_approved)<span class="academy-db-badge">En attente d'approbation</span>@endunless
                                                </p>
                                                <dl class="academy-db-row">
                                                    @foreach($dbFields as $field)
                                                        <dt>{{ $field->label }}</dt>
                                                        <dd>{!! \Modules\Academy\Services\DatabaseService::renderValue($field, $dbVals[$field->id] ?? null) !!}</dd>
                                                    @endforeach
                                                </dl>

                                                <div class="academy-db-actions">
                                                    {{-- Modération : approuver une fiche en attente (gérant). --}}
                                                    @if($dbCanModerate && ! $entry->is_approved)
                                                        <form method="POST" action="{{ route('academy.database.entries.approve', [$course, $lesson, $item->id, $entry->id]) }}">
                                                            @csrf
                                                            <x-core::button type="submit" variant="primary" size="sm">Approuver</x-core::button>
                                                        </form>
                                                    @endif

                                                    {{-- Supprimer SA fiche (ou n'importe laquelle si gérant) : 2 temps, jamais de popup. --}}
                                                    @if($dbIsOwner || $dbCanModerate)
                                                        <details>
                                                            <summary style="cursor: pointer; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);">Supprimer cette fiche</summary>
                                                            <form method="POST" action="{{ route('academy.database.entries.delete', [$course, $lesson, $item->id, $entry->id]) }}" class="mt-1">
                                                                @csrf
                                                                <x-core::button type="submit" variant="ghost" size="sm">Confirmer la suppression</x-core::button>
                                                            </form>
                                                        </details>
                                                    @endif
                                                </div>

                                                {{-- Éditer SA fiche (collaboratif : seul l'auteur ou un gérant). --}}
                                                @if(($dbIsOwner || $dbCanModerate) && $dbFields->isNotEmpty())
                                                    <details>
                                                        <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600;">Modifier cette fiche</summary>
                                                        <form method="POST" action="{{ route('academy.database.entries.update', [$course, $lesson, $item->id, $entry->id]) }}" class="mt-1" style="display: flex; flex-direction: column; gap: 8px;">
                                                            @csrf
                                                            <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                                <label for="db-ehp-{{ $entry->id }}">Ne pas remplir</label>
                                                                <input type="text" id="db-ehp-{{ $entry->id }}" name="{{ \Modules\Academy\Services\DatabaseService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                            </div>
                                                            @foreach($dbFields as $field)
                                                                @include('academy::public.partials.database-field', ['field' => $field, 'value' => $dbVals[$field->id] ?? '', 'entryId' => $entry->id])
                                                            @endforeach
                                                            <div><x-core::button type="submit" variant="secondary" size="sm">Enregistrer la fiche</x-core::button></div>
                                                        </form>
                                                    </details>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-muted" style="font-size: 0.9rem;">Aucune fiche pour l'instant.@if($dbCanAdd) Ajoutez la première ci-dessous.@endif</p>
                                        @endforelse
                                    </div>

                                    @if($dbEntries && $dbEntries->hasPages())
                                        <div class="mt-2">{{ $dbEntries->withQueryString()->links() }}</div>
                                    @endif

                                    {{-- Ajouter une fiche (gaté allow_student_add ; le gérant toujours). --}}
                                    @if($dbCanAdd && $dbFields->isNotEmpty())
                                        <details class="mt-3">
                                            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 700;">+ Ajouter une fiche</summary>
                                            <form method="POST" action="{{ route('academy.database.entries.create', [$course, $lesson, $item->id]) }}" class="mt-2" style="display: flex; flex-direction: column; gap: 8px;">
                                                @csrf
                                                {{-- Honeypot MAISON : doit rester vide ; hors écran, non focusable. --}}
                                                <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                    <label for="db-hp-{{ $item->id }}">Ne pas remplir</label>
                                                    <input type="text" id="db-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\DatabaseService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                </div>
                                                @foreach($dbFields as $field)
                                                    @include('academy::public.partials.database-field', ['field' => $field, 'value' => '', 'entryId' => 'new-'.$item->id])
                                                @endforeach
                                                @if(\Modules\Academy\Services\DatabaseService::requiresApproval($item) && ! $dbCanModerate)
                                                    <p class="text-muted" style="font-size: 0.8rem;">Votre fiche sera visible après l'approbation du formateur.</p>
                                                @endif
                                                <div><x-core::button type="submit" variant="primary" size="sm">Ajouter la fiche</x-core::button></div>
                                            </form>
                                        </details>
                                    @elseif($dbCanAdd && $dbFields->isEmpty())
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">Cette base de données n'a pas encore de champ. Définissez le schéma dans l'éditeur de cours.</p>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien de sensible dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder à la base de données
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à la base de données
                                        @else
                                            Base de données en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour consulter et contribuer.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit : inscrivez-vous pour contribuer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant : achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">Se connecter</x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">Créer un compte</x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">S'inscrire gratuitement</x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">Acheter ce cours</x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE ATELIER (workshop : évaluation par les pairs, parité Moodle « Workshop ») ── --}}
                        @elseif($item->type === 'workshop')
                            @php
                                $wsIntro    = \Modules\Academy\Services\WorkshopService::intro($item);
                                $wsPhase    = \Modules\Academy\Services\WorkshopService::phase($item);
                                $wsAnon     = \Modules\Academy\Services\WorkshopService::isAnonymous($item);
                                // Gérant de CE cours (admin OU owner/instructor) : l'autorisation réelle
                                // est TOUJOURS re-vérifiée serveur (WorkshopController) ; ici, affichage.
                                $wsManage = auth()->check() && auth()->user()->can('manageEnrollments', $course);
                                $wsCriteria = ($hasAccess || $wsManage)
                                    ? \Modules\Academy\Services\WorkshopService::criteria($item)
                                    : collect();
                                $wsMine = ($hasAccess && auth()->check())
                                    ? \Modules\Academy\Services\WorkshopService::submissionFor($item, auth()->id())
                                    : null;
                                $wsAssignments = ($hasAccess && auth()->check() && $wsPhase === 'assessment')
                                    ? \Modules\Academy\Services\WorkshopService::assignmentsFor($item, (int) auth()->id())
                                    : collect();
                                $wsPhaseLabel = ['setup' => 'Préparation', 'submission' => 'Soumission', 'assessment' => 'Évaluation', 'closed' => 'Notes'][$wsPhase] ?? $wsPhase;
                            @endphp
                            @if($hasAccess || $wsManage)
                                <div class="academy-workshop">
                                    @if($wsIntro !== '')
                                        <p class="academy-db-intro">{{ $wsIntro }}</p>
                                    @endif

                                    <p class="text-muted p-2 rounded" style="background: #F3F4F6; font-size: 0.85rem;">
                                        Phase courante : <strong>{{ $wsPhaseLabel }}</strong>.
                                        @if($wsPhase === 'submission') Remettez votre travail ci-dessous.
                                        @elseif($wsPhase === 'assessment') Évaluez les travaux qui vous sont attribués.
                                        @elseif($wsPhase === 'closed') Consultez votre note finale.
                                        @else L'atelier est en préparation.
                                        @endif
                                    </p>

                                    {{-- ── TABLEAU DE BORD GÉRANT : phase, progression, actions ── --}}
                                    @if($wsManage)
                                        @php
                                            $wsSubs     = \Modules\Academy\Services\WorkshopService::submissions($item);
                                            $wsProgress = \Modules\Academy\Services\WorkshopService::assessmentProgress($item);
                                        @endphp
                                        <div class="academy-db-entry" style="background: #FAFAFA;">
                                            <p class="academy-db-meta"><strong>Pilotage de l'atelier (gérant)</strong></p>
                                            <p style="font-size: 0.85rem; margin: 0 0 6px;">
                                                Travaux remis : {{ $wsSubs->count() }} ·
                                                Évaluations attribuées : {{ $wsProgress['allocated'] }} ·
                                                rendues : {{ $wsProgress['submitted'] }}
                                            </p>

                                            <form method="POST" action="{{ route('academy.workshop.phase', [$course, $lesson, $item->id]) }}" style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 8px;">
                                                @csrf
                                                <span style="display: flex; flex-direction: column; gap: 4px;">
                                                    <label for="ws-phase-{{ $item->id }}" style="font-size: 0.78rem; font-weight: 600;">Changer la phase</label>
                                                    <select id="ws-phase-{{ $item->id }}" name="phase" style="padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        @foreach(['setup' => 'Préparation', 'submission' => 'Soumission', 'assessment' => 'Évaluation (attribue les évaluations)', 'closed' => 'Notes'] as $pv => $pl)
                                                            <option value="{{ $pv }}" @selected($wsPhase === $pv)>{{ $pl }}</option>
                                                        @endforeach
                                                    </select>
                                                </span>
                                                <x-core::button type="submit" variant="primary" size="sm">Appliquer la phase</x-core::button>
                                            </form>

                                            <form method="POST" action="{{ route('academy.workshop.allocate', [$course, $lesson, $item->id]) }}">
                                                @csrf
                                                <x-core::button type="submit" variant="secondary" size="sm">Attribuer les évaluations</x-core::button>
                                            </form>

                                            @if($wsSubs->isNotEmpty())
                                                <details class="mt-2">
                                                    <summary style="cursor: pointer; font-size: 0.85rem; font-weight: 600;">Voir les travaux et leurs notes</summary>
                                                    <ul style="list-style: none; padding: 0; margin: 8px 0 0;">
                                                        @php $wsScoreMap = \Modules\Academy\Services\WorkshopService::batchFinalScores($wsSubs); @endphp
                                                        @foreach($wsSubs as $sub)
                                                            @php $subScore = $wsScoreMap[$sub->id] ?? null; @endphp
                                                            <li style="border-top: 1px solid #E5E7EB; padding: 6px 0; font-size: 0.85rem;">
                                                                <strong>{{ $sub->title }}</strong> - {{ $sub->author?->name ?? '(inconnu)' }}
                                                                · Note : {{ $subScore === null ? 'en attente' : number_format($subScore, 1, ',', ' ').' %' }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- ── PHASE SOUMISSION : remettre / consulter SON travail ── --}}
                                    @if($wsPhase === 'submission' && $hasAccess && auth()->check())
                                        <details class="mt-3" @if($wsMine === null) open @endif>
                                            <summary style="cursor: pointer; font-size: 0.88rem; font-weight: 700;">{{ $wsMine ? 'Modifier mon travail' : '+ Remettre mon travail' }}</summary>
                                            <form method="POST" action="{{ route('academy.workshop.submit', [$course, $lesson, $item->id]) }}" class="mt-2" style="display: flex; flex-direction: column; gap: 8px;">
                                                @csrf
                                                <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                    <label for="ws-hp-{{ $item->id }}">Ne pas remplir</label>
                                                    <input type="text" id="ws-hp-{{ $item->id }}" name="{{ \Modules\Academy\Services\WorkshopService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                </div>
                                                <label for="ws-title-{{ $item->id }}" style="font-size: 0.78rem; font-weight: 600;">Titre de mon travail</label>
                                                <input id="ws-title-{{ $item->id }}" type="text" name="title" value="{{ $wsMine->title ?? '' }}" maxlength="{{ \Modules\Academy\Services\WorkshopService::TITLE_MAX }}" required
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                <label for="ws-body-{{ $item->id }}" style="font-size: 0.78rem; font-weight: 600;">Mon travail</label>
                                                <textarea id="ws-body-{{ $item->id }}" name="body" rows="6" maxlength="{{ \Modules\Academy\Services\WorkshopService::BODY_MAX }}"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ $wsMine->body ?? '' }}</textarea>
                                                <div><x-core::button type="submit" variant="primary" size="sm">{{ $wsMine ? 'Mettre à jour mon travail' : 'Remettre mon travail' }}</x-core::button></div>
                                            </form>
                                        </details>
                                        @if($wsMine)
                                            <div class="academy-db-entry mt-2">
                                                <p class="academy-db-meta">Mon travail remis - <strong>{{ $wsMine->title }}</strong></p>
                                                <div>{!! \Modules\Academy\Services\WorkshopService::renderText($wsMine->body) !!}</div>
                                            </div>
                                        @endif
                                    @endif

                                    {{-- ── PHASE ÉVALUATION : noter les travaux attribués (anonymisés) ── --}}
                                    @if($wsPhase === 'assessment' && $hasAccess && auth()->check())
                                        <h4 style="font-size: 0.95rem; font-weight: 700; margin: 14px 0 6px;">Travaux à évaluer</h4>
                                        @forelse($wsAssignments as $assessment)
                                            @php $wsScores = \Modules\Academy\Services\WorkshopService::scoresByCriterion($assessment); @endphp
                                            <div class="academy-db-entry" wire:key="ws-assess-{{ $assessment->id }}">
                                                <p class="academy-db-meta">
                                                    <strong>{{ $assessment->submission->title ?? 'Travail' }}</strong>
                                                    @unless($wsAnon) - {{ $assessment->submission->author?->name ?? '(inconnu)' }} @endunless
                                                    @if($assessment->submitted_at)<span class="academy-db-badge">Évaluation rendue</span>@endif
                                                </p>
                                                <div style="margin-bottom: 8px;">{!! \Modules\Academy\Services\WorkshopService::renderText($assessment->submission->body ?? '') !!}</div>

                                                <form method="POST" action="{{ route('academy.workshop.assess', [$course, $lesson, $item->id, $assessment->id]) }}" style="display: flex; flex-direction: column; gap: 8px;">
                                                    @csrf
                                                    <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px;">
                                                        <label for="ws-ahp-{{ $assessment->id }}">Ne pas remplir</label>
                                                        <input type="text" id="ws-ahp-{{ $assessment->id }}" name="{{ \Modules\Academy\Services\WorkshopService::HONEYPOT }}" tabindex="-1" autocomplete="off">
                                                    </div>
                                                    @foreach($wsCriteria as $criterion)
                                                        <span style="display: flex; flex-direction: column; gap: 4px;">
                                                            <label for="ws-score-{{ $assessment->id }}-{{ $criterion->id }}" style="font-size: 0.8rem; font-weight: 600;">
                                                                {{ $criterion->label }} <span class="text-muted">(0 à {{ $criterion->max_score }})</span>
                                                            </label>
                                                            @if($criterion->description)<span class="text-muted" style="font-size: 0.78rem;">{{ $criterion->description }}</span>@endif
                                                            <input id="ws-score-{{ $assessment->id }}-{{ $criterion->id }}" type="number" min="0" max="{{ $criterion->max_score }}"
                                                                   name="scores[{{ $criterion->id }}]" value="{{ $wsScores[$criterion->id] ?? '' }}"
                                                                   style="width: 110px; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                        </span>
                                                    @endforeach
                                                    <label for="ws-fb-{{ $assessment->id }}" style="font-size: 0.8rem; font-weight: 600;">Commentaire (facultatif)</label>
                                                    <textarea id="ws-fb-{{ $assessment->id }}" name="feedback" rows="3" maxlength="{{ \Modules\Academy\Services\WorkshopService::FEEDBACK_MAX }}"
                                                              style="width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;">{{ $assessment->feedback }}</textarea>
                                                    <div><x-core::button type="submit" variant="primary" size="sm">Enregistrer mon évaluation</x-core::button></div>
                                                </form>
                                            </div>
                                        @empty
                                            <p class="text-muted" style="font-size: 0.9rem;">Aucun travail ne vous est attribué pour l'instant. Le formateur attribuera les évaluations.</p>
                                        @endforelse
                                    @endif

                                    {{-- ── PHASE NOTES : ma note finale + retours reçus ── --}}
                                    @if($wsPhase === 'closed' && $hasAccess && auth()->check())
                                        @php
                                            $wsFinal = \Modules\Academy\Services\WorkshopService::finalGradeForStudent($item, auth()->id());
                                        @endphp
                                        <div class="academy-db-entry mt-2">
                                            <p class="academy-db-meta"><strong>Ma note finale</strong></p>
                                            @if($wsMine === null)
                                                <p class="text-muted" style="font-size: 0.9rem;">Vous n'avez pas remis de travail pour cet atelier.</p>
                                            @elseif($wsFinal === null)
                                                <p class="text-muted" style="font-size: 0.9rem;">Votre travail n'a pas encore reçu d'évaluation.</p>
                                            @else
                                                <p style="font-size: 1.1rem; font-weight: 700;">{{ number_format($wsFinal, 1, ',', ' ') }} %</p>
                                                @php $wsReceived = \Modules\Academy\Services\WorkshopService::receivedFeedbacks($wsMine)->filter(fn ($a) => filled($a->feedback)); @endphp
                                                @if($wsReceived->isNotEmpty())
                                                    <p class="academy-db-meta" style="margin-top: 8px;">Retours reçus</p>
                                                    @foreach($wsReceived as $rec)
                                                        <div style="border-top: 1px solid #E5E7EB; padding: 6px 0;">
                                                            <div>{!! \Modules\Academy\Services\WorkshopService::renderText($rec->feedback) !!}</div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </div>
                                    @endif

                                    @if($wsCriteria->isEmpty())
                                        <p class="text-muted mt-2" style="font-size: 0.85rem;">Cet atelier n'a pas encore de grille d'évaluation. Définissez les critères dans l'éditeur de cours.</p>
                                    @endif
                                </div>
                            @else
                                {{-- Accès refusé (même logique que les autres types : rien de sensible dans le DOM). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour accéder à l'atelier
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à l'atelier
                                        @else
                                            Atelier en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour remettre un travail et évaluer vos pairs.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit : inscrivez-vous pour participer.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant : achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">Se connecter</x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">Créer un compte</x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">S'inscrire gratuitement</x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">Acheter ce cours</x-core::button>
                                    @endif
                                </div>
                            @endif

                        {{-- ── TYPE H5P (contenu interactif, parité Moodle « H5P ») ── --}}
                        @elseif($item->type === 'h5p')
                            @php $h5pPath = $item->payload['h5p_path'] ?? null; @endphp
                            @if($hasAccess && !empty($h5pPath))
                                {{--
                                    ISOLATION (le contenu H5P exécute du JS tiers) :
                                    le contenu est rendu DANS UN IFRAME SANDBOX pointant vers une page
                                    dédiée (H5pPlayerController) qui charge le player h5p-standalone.
                                    sandbox="allow-scripts allow-same-origin" = le MINIMUM nécessaire
                                    (scripts du player + fetch same-origin du content.json). On NE donne
                                    PAS allow-forms / allow-popups / allow-top-navigation / allow-modals :
                                    le contenu tiers ne peut donc PAS naviguer la page hôte, ouvrir de
                                    popups ni poster vers la session. L'URL du dossier extrait n'est
                                    jamais injectée ici : elle vit dans la page player, elle-même gatée.

                                    RISQUE CONNU (dette v2) : « allow-same-origin » + « allow-scripts »
                                    laisse le JS H5P s'exécuter dans NOTRE origine ; il peut donc lire le
                                    DOM parent (p. ex. le jeton CSRF). On l'ACCEPTE car le téléversement
                                    d'un paquet .h5p est restreint aux ADMINS de confiance (permission
                                    « academy.manage », cf. CourseEditor::canUploadH5p) + audit manuel.
                                    Fix définitif : servir le contenu sur un SOUS-DOMAINE distinct (origine
                                    isolée) pour que le sandbox same-origin ne soit plus la nôtre.
                                --}}
                                <div class="academy-h5p-wrapper">
                                    <iframe
                                        src="{{ route('academy.h5p.play', [$course, $lesson, $item->id]) }}"
                                        title="{{ $item->title ?? $lesson->title }}"
                                        sandbox="allow-scripts allow-same-origin"
                                        loading="lazy"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        style="width: 100%; min-height: 480px; border: 1px solid #E5E7EB; border-radius: 8px; background: #fff;"
                                    ></iframe>
                                </div>
                            @else
                                {{-- Accès refusé : aucune URL de contenu dans le DOM (même logique que la vidéo). --}}
                                <div class="academy-gated-panel">
                                    <div class="gated-icon">🔐</div>
                                    <div class="gated-title">
                                        @if(!auth()->check())
                                            Connexion requise pour ce contenu interactif
                                        @elseif(!$isEnrolled)
                                            Inscrivez-vous pour accéder à ce contenu interactif
                                        @else
                                            Contenu interactif en cours de préparation
                                        @endif
                                    </div>
                                    <p class="gated-sub">
                                        @if(!auth()->check())
                                            Créez un compte gratuit ou connectez-vous pour accéder aux activités H5P.
                                        @elseif(!$isEnrolled && $isFree)
                                            Ce cours est gratuit - inscrivez-vous pour accéder au contenu interactif.
                                        @elseif(!$isEnrolled && !$isFree)
                                            Ce cours est payant - achetez-le pour accéder à l'ensemble du contenu.
                                        @else
                                            Votre inscription vous donne accès à l'ensemble du contenu.
                                        @endif
                                    </p>
                                    @if(!auth()->check())
                                        <span class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                            <x-core::button :href="Route::has('login') ? route('login') : '#'" variant="primary" size="sm">
                                                Se connecter
                                            </x-core::button>
                                            <x-core::button :href="Route::has('register') ? route('register') : '#'" variant="secondary" size="sm">
                                                Créer un compte
                                            </x-core::button>
                                        </span>
                                    @elseif(!$isEnrolled && $isFree)
                                        <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-core::button type="submit" variant="primary" size="sm">
                                                S'inscrire gratuitement
                                            </x-core::button>
                                        </form>
                                    @elseif(!$isEnrolled && !$isFree)
                                        <x-core::button :href="route('academy.courses.purchase', $course)" variant="primary" size="sm">
                                            Acheter ce cours
                                        </x-core::button>
                                    @endif
                                </div>
                            @endif

                        @else
                            {{-- Type inconnu : rendu défensif --}}
                            <div class="text-muted p-3 rounded" style="background: #F3F4F6; font-size: 0.9rem;">
                                <em>Type de contenu « {{ $item->type }} » non reconnu.</em>
                            </div>
                        @endif

