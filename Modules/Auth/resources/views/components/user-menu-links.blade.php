{{-- Composant réutilisable — liens menu utilisateur, groupés par intention
     Usage: @include('auth::components.user-menu-links', ['variant' => 'sidebar']) ou 'dropdown'
     Source unique de vérité pour les liens du menu profil.

     Structure : $groups = ['clé' => ['label' => ..., 'links' => [...]]].
     Chaque lien : route, label, icon (fa-*), emoji, active_patterns (tableau de patterns
     pour request()->routeIs()), show (optionnel, bool), badge (optionnel, bool).
     - variant=sidebar : chaque groupe visible est un accordéon Alpine.js (x-show), toujours
       ouvert en desktop via CSS !important (voir public/css/charte.css). PAS de <details> natif :
       Chrome masque le contenu d'un <details> fermé via un mécanisme UA interne qu'une simple
       règle display:block!important ne parvient PAS à annuler de façon fiable (vérifié en direct :
       offsetHeight=0 malgré getComputedStyle().display='block') - bug constaté visuellement le
       2026-07-18 avant déploiement, corrigé en repassant par le pattern Alpine déjà utilisé
       ailleurs sur le site (ex. color-manager), où !important bat bien le style inline x-show.
     - variant=dropdown : liste compacte existante, avec un simple libellé de groupe non cliquable.
--}}
@php
    $variant = $variant ?? 'sidebar';
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);

    $groups = [
        'apercu' => [
            'label' => __('Vue d\'ensemble'),
            'links' => [
                ['route' => 'user.dashboard', 'label' => __('Tableau de bord'), 'icon' => 'fa-tachometer', 'emoji' => '📊', 'active_patterns' => ['user.dashboard']],
            ],
        ],
        'academie' => [
            'label' => __('Académie'),
            'links' => [
                // Académie : visible quand le module est public, OU pour les rôles Académie pendant le mode construction (sinon /academie renvoie 503).
                ['route' => 'academy.dashboard', 'label' => __('Académie - mon espace'), 'icon' => 'fa-graduation-cap', 'emoji' => '🎓', 'active_patterns' => ['academy.dashboard'], 'show' => (! config('academy.under_construction', true)) || (auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'admin', 'instructor', 'student']))],
                // Créer un cours : seulement pour qui peut gérer (admin) ou les formateurs.
                ['route' => 'academy.courses.create', 'label' => __('Créer un cours'), 'icon' => 'fa-plus', 'emoji' => '➕', 'active_patterns' => ['academy.courses.create'], 'show' => auth()->check() && (auth()->user()->can('academy.manage') || auth()->user()->hasRole('instructor'))],
            ],
        ],
        'contenu' => [
            'label' => __('Mon contenu'),
            'links' => [
                ['route' => 'user.contributions', 'label' => __('Mes contributions'), 'icon' => 'fa-handshake-o', 'emoji' => '📝', 'active_patterns' => ['user.contributions']],
                ['route' => 'user.saved', 'label' => __('Mes sauvegardes'), 'icon' => 'fa-floppy-o', 'emoji' => '💾', 'active_patterns' => ['user.saved']],
                // bookmarks.index = seule route du module (vérifié par grep) → nom exact, pas de wildcard.
                ['route' => 'bookmarks.index', 'label' => __('Mes favoris'), 'icon' => 'fa-bookmark', 'emoji' => '❤️', 'active_patterns' => ['bookmarks.index']],
                ['route' => 'journal.index', 'label' => __('Mes journaux'), 'icon' => 'fa-book', 'emoji' => '📔', 'active_patterns' => ['journal.*']],
                // collections.my UNIQUEMENT (pas de wildcard collections.*) : collections.index/show/list sont
                // les pages PUBLIQUES de l'annuaire (Directory\CollectionController), pas « mes collections ».
                ['route' => 'collections.my', 'label' => __('Mes collections'), 'icon' => 'fa-folder', 'emoji' => '📁', 'active_patterns' => ['collections.my']],
            ],
        ],
        'outils' => [
            'label' => __('Mes outils'),
            'links' => [
                ['route' => 'shorturl.user.index', 'label' => __('Mes liens courts'), 'icon' => 'fa-link', 'emoji' => '🔗', 'active_patterns' => ['shorturl.user.*']],
                // decido.* couvre aussi decido.vote.* (vote public sur un sondage) : même module/feature, pas de collision avec un autre outil.
                ['route' => 'decido.index', 'label' => __('Mes sondages'), 'icon' => 'fa-bar-chart', 'emoji' => '🗳️', 'active_patterns' => ['decido.*']],
                // Mes prompts (constructeur-prompts) : round 106 (2026-08-01) - toujours visible pour
                // un utilisateur connecté, même pendant la révision de l'outil. C'est la bibliothèque
                // de LECTURE des prompts déjà sauvegardés (droit d'accès à ses propres données), plus
                // jamais gatée par is_under_construction - seule la page de l'outil (wizard) l'est encore.
                ['route' => 'user.prompts.index', 'label' => __('Mes prompts'), 'icon' => 'fa-magic', 'emoji' => '✨', 'active_patterns' => ['user.prompts.index']],
            ],
        ],
        'compte' => [
            'label' => __('Mon compte'),
            'links' => [
                ['route' => 'user.profile', 'label' => __('Mon profil'), 'icon' => 'fa-user', 'emoji' => '👤', 'active_patterns' => ['user.profile']],
                ['route' => 'shop.my-orders', 'label' => __('Mes commandes'), 'icon' => 'fa-shopping-bag', 'emoji' => '🛒', 'active_patterns' => ['shop.my-orders'], 'show' => ! config('shop.maintenance', false)],
                ['route' => 'user.notifications', 'label' => __('Notifications'), 'icon' => 'fa-bell', 'emoji' => '🔔', 'active_patterns' => ['user.notifications'], 'badge' => true],
            ],
        ],
    ];

    // Filtre chaque groupe : Route::has() + gate 'show' ; un groupe sans lien visible est omis.
    $visibleGroups = [];
    foreach ($groups as $groupKey => $group) {
        $visibleLinks = array_values(array_filter($group['links'], fn ($l) => Route::has($l['route']) && ($l['show'] ?? true)));
        if (count($visibleLinks) > 0) {
            $visibleGroups[$groupKey] = ['label' => $group['label'], 'links' => $visibleLinks];
        }
    }

    // Détermine le groupe actif (premier lien dont un active_pattern matche la route courante).
    $activeGroupKey = null;
    foreach ($visibleGroups as $groupKey => $group) {
        foreach ($group['links'] as $link) {
            if (collect($link['active_patterns'])->contains(fn ($p) => request()->routeIs($p))) {
                $activeGroupKey = $groupKey;
                break 2;
            }
        }
    }
@endphp

@if($variant === 'dropdown')
    @foreach($visibleGroups as $groupKey => $group)
        <div style="padding:6px 16px;font-size:10px;font-weight:700;text-transform:uppercase;color:#9CA3AF;">{{ $group['label'] }}</div>
        @foreach($group['links'] as $link)
            <a href="{{ route($link['route']) }}" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">{{ $link['emoji'] }} {{ $link['label'] }}@if(($link['badge'] ?? false) && $unreadNotifications > 0) <span style="background:#ef4444;color:#fff;padding:1px 6px;border-radius:10px;font-size:10px;font-weight:700;margin-left:4px;">{{ $unreadNotifications }}</span>@endif</a>
        @endforeach
    @endforeach
@else
    @foreach($visibleGroups as $groupKey => $group)
        <div class="msp-group" x-data="{ open: {{ $groupKey === $activeGroupKey ? 'true' : 'false' }} }">
            <button type="button" class="msp-group-summary" x-on:click="open = !open" :aria-expanded="open.toString()">
                {{ $group['label'] }}
            </button>
            <div class="msp-group-body" x-show="open" x-transition x-cloak>
                @foreach($group['links'] as $link)
                    @php $isActive = collect($link['active_patterns'])->contains(fn ($p) => request()->routeIs($p)); @endphp
                    <a href="{{ route($link['route']) }}" class="list-group-item {{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="page" @endif>
                        <i class="fa {{ $link['icon'] }}" style="width: 20px;"></i> {{ $link['label'] }}
                        @if(($link['badge'] ?? false) && $unreadNotifications > 0)
                            <span class="badge" style="background: #d9534f;">{{ $unreadNotifications }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
@endif
