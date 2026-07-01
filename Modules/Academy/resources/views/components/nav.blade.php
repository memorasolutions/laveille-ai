{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Barre de navigation Académie PERSISTANTE et RÔLE-AWARE (« façon Moodle »).
    Composant anonyme DRY : <x-academy::nav /> en tête du contenu de toutes les pages /academie/*.

    - Sticky sous le header global, charte teal (--sys-action-primary / --c-primary).
    - Responsive : scroll horizontal sur mobile (pas de menu qui casse).
    - A11y : <nav aria-label>, cibles tactiles ≥44px, état actif via request()->routeIs()
      avec aria-current="page", contraste AA.
    - Liens RÔLE-AWARE alignés EXACTEMENT sur auth::components.user-menu-links :
        * créer/gérer visible si can('academy.manage') OU hasRole('instructor').
    - Défensif : Route::has() partout → un lien dont la route n'existe pas ne casse rien.
    - SÉCURITÉ : l'affichage d'un lien ne donne JAMAIS l'accès. L'autorisation reste
      serveur (authorize() dans chaque route/composant Livewire). Aucun lien d'action
      n'est exposé à un invité.
--}}
@php
    $user = auth()->user();
    // Même gate que user-menu-links.blade.php (source unique de vérité d'autorisation).
    $canManage = $user && ($user->can('academy.manage') || $user->hasRole('instructor'));

    // Construction défensive des liens. 'show' = condition d'affichage (jamais une autorisation).
    $links = [
        [
            'route' => 'academy.index',
            'label' => '🎓 Formations',
            'show'  => true,
        ],
        [
            'route' => 'academy.dashboard',
            'label' => 'Mon espace',
            'show'  => auth()->check(),
        ],
        [
            'route' => 'academy.courses.create',
            'label' => '➕ Créer un cours',
            'show'  => $canManage,
        ],
        [
            'route' => 'academy.questions.bank',
            'label' => '🏛️ Banque de questions',
            'show'  => $canManage,
        ],
        [
            'route' => 'academy.diplomas.templates.editor',
            'label' => '🎓 Gabarits de diplômes',
            // Phase 1 : lien visible UNIQUEMENT si le drapeau est actif (OFF = système
            // de certificat existant inchangé, aucun bouton affiché — même convention
            // que le bouton « Badge vérifiable » gâté academy.open_badges_enabled).
            'show'  => $canManage && config('academy.diploma_editor_enabled', false),
        ],
    ];
@endphp

<nav aria-label="Navigation de l'Académie"
     style="position: sticky; top: 0; z-index: 1020;
            background: #fff; border-bottom: 1px solid #E5E7EB;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;">
    <div class="container">
        <div style="display: flex; align-items: center; gap: 6px;
                    overflow-x: auto; -webkit-overflow-scrolling: touch;
                    white-space: nowrap;">

            {{-- Accueil Académie (logo/marque) --}}
            @if(Route::has('academy.index'))
                <a href="{{ route('academy.index') }}"
                   @if(request()->routeIs('academy.index')) aria-current="page" @endif
                   style="display: inline-flex; align-items: center; min-height: 44px;
                          padding: 10px 14px; margin-right: 4px;
                          font-family: var(--f-heading, system-ui); font-weight: 700;
                          color: var(--sys-action-primary, var(--c-primary, #0f766e));
                          text-decoration: none !important; flex: 0 0 auto;">
                    Académie
                </a>
            @endif

            {{-- Liens role-aware --}}
            @foreach($links as $link)
                @if(($link['show'] ?? false) && Route::has($link['route']))
                    @php $isActive = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route']) }}"
                       @if($isActive) aria-current="page" @endif
                       style="display: inline-flex; align-items: center; min-height: 44px;
                              padding: 10px 14px; flex: 0 0 auto;
                              font-family: var(--f-body, system-ui); font-weight: 600;
                              font-size: 0.95rem; text-decoration: none !important;
                              border-bottom: 3px solid {{ $isActive ? 'var(--sys-action-primary, var(--c-primary, #0f766e))' : 'transparent' }};
                              color: {{ $isActive ? 'var(--sys-action-primary, var(--c-primary, #0f766e))' : 'var(--sys-text-default, #1A1D23)' }};">
                        {{ $link['label'] }}
                    </a>
                @endif
            @endforeach

            {{-- Invité : se connecter, poussé à droite --}}
            @guest
                @if(Route::has('login'))
                    <a href="{{ route('login') }}"
                       style="display: inline-flex; align-items: center; min-height: 44px;
                              padding: 10px 16px; margin-left: auto; flex: 0 0 auto;
                              font-family: var(--f-body, system-ui); font-weight: 600;
                              font-size: 0.95rem; text-decoration: none !important;
                              color: var(--sys-action-primary, var(--c-primary, #0f766e));">
                        Se connecter
                    </a>
                @endif
            @endguest

        </div>
    </div>
</nav>
