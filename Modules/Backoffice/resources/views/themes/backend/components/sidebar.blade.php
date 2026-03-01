<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php
    $siteName = $branding['site_name'] ?? config('app.name');
    $initial  = mb_strtoupper(mb_substr($siteName, 0, 1));
    $color    = $branding['primary_color'] ?? '#6610f2';
    $svgBase  = '<circle cx="18" cy="18" r="15" fill="' . $color . '"/><text x="18" y="23" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text>';
    $svgLight = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#1f2937">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgDark  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#f1f5f9">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgIcon  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><circle cx="16" cy="16" r="14" fill="' . $color . '"/><text x="16" y="21" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text></svg>');
@endphp

<div class="dlabnav">
    <div class="dlabnav-scroll">

        {{-- Logo --}}
        <div class="nav-header">
            <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                <img src="{{ ! empty($branding['logo_light'] ?? '') ? asset('storage/' . $branding['logo_light']) : $svgLight }}"
                     alt="{{ $siteName }}"
                     class="logo-abbr light-logo">
                <img src="{{ ! empty($branding['logo_dark'] ?? '') ? asset('storage/' . $branding['logo_dark']) : $svgDark }}"
                     alt="{{ $siteName }}"
                     class="logo-abbr dark-logo">
                <span class="brand-title">{{ $siteName }}</span>
            </a>
            <a href="javascript:void(0);" class="nav-control">
                <div class="hamburger">
                    <span class="line"></span>
                    <span class="line"></span>
                    <span class="line"></span>
                </div>
            </a>
        </div>

        {{-- User profile --}}
        <div class="dropdown header-profile2 mb-4" x-data="{ open: false }">
            <a @click="open = !open"
               class="nav-link d-flex align-items-center gap-3" style="cursor:pointer;"
               role="button"
               aria-haspopup="true"
               :aria-expanded="open">
                <span class="rounded bg-primary text-white d-flex align-items-center justify-content-center fw-semibold small" style="width:2.8rem;height:2.8rem;">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                </span>
                <div class="header-info2">
                    <h6 class="text-sm font-medium">{{ auth()->user()->name }}</h6>
                    <p class="small text-muted mb-0">{{ auth()->user()->roles->first()?->name ?? 'Utilisateur' }}</p>
                </div>
            </a>
            <div x-show="open"
                 @click.outside="open = false"
                 x-transition
                 class="dropdown-menu dropdown-menu-end"
                 style="display:none;">
                <a href="{{ route('admin.profile') }}" class="dropdown-item ai-icon">
                    <i class="fa fa-user-circle text-primary me-2"></i>
                    <span class="ms-2">Profil</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item ai-icon">
                        <i class="fa fa-sign-out-alt text-danger me-2"></i>
                        <span class="ms-2">Déconnexion</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-menu-area" aria-label="{{ __('Menu administration') }}">
        <ul class="metismenu" id="menu">

            {{-- ===== TABLEAU DE BORD ===== --}}
            <li class="{{ request()->routeIs('admin.dashboard') ? 'mm-active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" aria-expanded="false">
                    <i class="flaticon-025-dashboard"></i>
                    <span class="nav-text">Tableau de bord</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.stats') ? 'mm-active' : '' }}">
                <a href="{{ route('admin.stats') }}" aria-expanded="false">
                    <i class="fa fa-chart-bar"></i>
                    <span class="nav-text">Statistiques</span>
                </a>
            </li>

            {{-- ===== CONTENU ===== --}}
            <li class="nav-label">Contenu</li>

            <li class="{{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                    <i class="fa fa-layer-group"></i>
                    <span class="nav-text">Contenu</span>
                </a>
                <ul aria-expanded="false">
                    <li class="{{ request()->routeIs('admin.blog.articles.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.blog.articles.index') }}">Articles</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blog.comments.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.blog.comments.index') }}">Commentaires</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blog.categories.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.blog.categories.index') }}">Catégories</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.pages.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.pages.index') }}">Pages</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.media.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.media.index') }}">Médias</a>
                    </li>
                </ul>
            </li>

            {{-- ===== UTILISATEURS ===== --}}
            <li class="nav-label">Utilisateurs</li>

            <li class="{{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.newsletter.*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                    <i class="fa fa-users"></i>
                    <span class="nav-text">Utilisateurs</span>
                </a>
                <ul aria-expanded="false">
                    <li class="{{ request()->routeIs('admin.users.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.users.index') }}">Membres</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.roles.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.roles.index') }}">Rôles</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.newsletter.index') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.newsletter.index') }}">Newsletter</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.newsletter.campaigns.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.newsletter.campaigns.index') }}">Campagnes</a>
                    </li>
                </ul>
            </li>

            {{-- ===== MONÉTISATION ===== --}}
            <li class="nav-label">Monétisation</li>

            <li class="{{ request()->routeIs('admin.plans.*') ? 'mm-active' : '' }}">
                <a href="{{ route('admin.plans.index') }}" aria-expanded="false">
                    <i class="fa fa-tags"></i>
                    <span class="nav-text">Plans</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.revenue') ? 'mm-active' : '' }}">
                <a href="{{ route('admin.revenue') }}" aria-expanded="false">
                    <i class="fa fa-dollar-sign"></i>
                    <span class="nav-text">Revenus</span>
                </a>
            </li>

            {{-- ===== CONFIGURATION ===== --}}
            <li class="nav-label">Configuration</li>

            <li class="{{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                    <i class="fa fa-cog"></i>
                    <span class="nav-text">Configuration</span>
                </a>
                <ul aria-expanded="false">
                    <li class="{{ request()->routeIs('admin.settings.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.settings.index') }}">Paramètres</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.branding.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.branding.edit') }}">Personnalisation</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.seo.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.seo.index') }}">SEO</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.feature-flags.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.feature-flags.index') }}">Feature Flags</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.translations.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.translations.index') }}">Traductions</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.plugins.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.plugins.index') }}">Plugins</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.email-templates.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.email-templates.index') }}">Emails templates</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.webhooks.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.webhooks.index') }}">Webhooks</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.shortcodes.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.shortcodes.index') }}">Shortcodes</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.cookie-categories.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.cookie-categories.index') }}">Cookies GDPR</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.onboarding-steps.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.onboarding-steps.index') }}">Onboarding</a>
                    </li>
                </ul>
            </li>

            {{-- ===== SÉCURITÉ ===== --}}
            <li class="nav-label">Sécurité</li>

            <li class="{{ request()->routeIs('admin.security', 'admin.blocked-ips.*', 'admin.login-history') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                    <i class="fa fa-shield-alt"></i>
                    <span class="nav-text">Sécurité</span>
                </a>
                <ul aria-expanded="false">
                    <li class="{{ request()->routeIs('admin.security') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.security') }}">Tableau de bord</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blocked-ips.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.blocked-ips.index') }}">IPs bloquées</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.login-history') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.login-history') }}">Connexions</a>
                    </li>
                </ul>
            </li>

            {{-- ===== OUTILS ===== --}}
            <li class="nav-label">Outils</li>

            <li class="{{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention', 'admin.notifications.*', 'admin.push-notifications.*') ? 'mm-active' : '' }}">
                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                    <i class="fa fa-tools"></i>
                    <span class="nav-text">Outils</span>
                </a>
                <ul aria-expanded="false">
                    <li class="{{ request()->routeIs('admin.backups.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.backups.index') }}">Sauvegardes</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.activity-logs.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.activity-logs.index') }}">Journaux d'activité</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.logs') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.logs') }}">Journaux app</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.failed-jobs.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.failed-jobs.index') }}">Jobs échoués</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.trash.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.trash.index') }}">Corbeille</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.health') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.health') }}">Santé système</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.scheduler') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.scheduler') }}">Scheduler</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.mail-log') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.mail-log') }}">Emails envoyés</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.cache') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.cache') }}">Cache</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.system-info') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.system-info') }}">Infos système</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.data-retention') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.data-retention') }}">Rétention données</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.notifications.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.notifications.index') }}">Notifications</a>
                    </li>
                    <li class="{{ request()->routeIs('admin.push-notifications.*') ? 'mm-active' : '' }}">
                        <a href="{{ route('admin.push-notifications.index') }}">Push notifications</a>
                    </li>
                    <li>
                        <a href="/horizon" target="_blank" rel="noopener noreferrer">Horizon ↗</a>
                    </li>
                    <li>
                        <a href="/telescope" target="_blank" rel="noopener noreferrer">Telescope ↗</a>
                    </li>
                </ul>
            </li>

        </ul>
        </nav>

    </div>
</div>
