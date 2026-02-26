@php
    $logo = \Modules\Settings\Models\Setting::get('branding.logo');
    $siteName = \Modules\Settings\Models\Setting::get('branding.site_name', config('app.name'));
    $primaryColor = \Modules\Settings\Models\Setting::get('branding.primary_color', '#206bc4');
    $initial = strtoupper(substr($siteName, 0, 1));

    // Active group detection
    $isContenu = request()->routeIs('admin.blog.articles.*')
        || request()->routeIs('admin.blog.comments.*')
        || request()->routeIs('admin.blog.categories.*')
        || request()->routeIs('admin.pages.*')
        || request()->routeIs('admin.media.*');

    $isUtilisateurs = request()->routeIs('admin.users.*')
        || request()->routeIs('admin.roles.*')
        || request()->routeIs('admin.newsletter.*')
        || request()->routeIs('admin.newsletter.campaigns.*');

    $isMonetisation = request()->routeIs('admin.plans.*')
        || request()->routeIs('admin.revenue.*');

    $isConfiguration = request()->routeIs('admin.settings.*')
        || request()->routeIs('admin.branding.*')
        || request()->routeIs('admin.seo.*')
        || request()->routeIs('admin.feature-flags.*')
        || request()->routeIs('admin.translations.*')
        || request()->routeIs('admin.plugins.*')
        || request()->routeIs('admin.email-templates.*')
        || request()->routeIs('admin.webhooks.*')
        || request()->routeIs('admin.shortcodes.*')
        || request()->routeIs('admin.cookie-categories.*')
        || request()->routeIs('admin.onboarding-steps.*');

    $isSecurite = request()->routeIs('admin.security.*')
        || request()->routeIs('admin.blocked-ips.*')
        || request()->routeIs('admin.login-history.*');

    $isOutils = request()->routeIs('admin.backups.*')
        || request()->routeIs('admin.activity-logs.*')
        || request()->routeIs('admin.logs.*')
        || request()->routeIs('admin.failed-jobs.*')
        || request()->routeIs('admin.trash.*')
        || request()->routeIs('admin.health.*')
        || request()->routeIs('admin.scheduler.*')
        || request()->routeIs('admin.mail-logs.*')
        || request()->routeIs('admin.cache.*')
        || request()->routeIs('admin.system-info.*')
        || request()->routeIs('admin.data-retention.*')
        || request()->routeIs('admin.notifications.*')
        || request()->routeIs('admin.push-notifications.*');
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">

        {{-- Logo --}}
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('admin.dashboard') }}">
                @if($logo)
                    <img src="{{ asset('storage/' . $logo) }}"
                         alt="{{ $siteName }}"
                         height="32"
                         class="navbar-brand-image">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" class="navbar-brand-image">
                        <rect width="32" height="32" rx="6" fill="{{ $primaryColor }}"/>
                        <text x="16" y="22" text-anchor="middle" fill="white" font-size="18" font-weight="bold" font-family="Arial">{{ $initial }}</text>
                    </svg>
                    <span class="ms-2">{{ $siteName }}</span>
                @endif
            </a>
        </h1>

        {{-- Mobile toggler --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">

                {{-- Tableau de bord --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-dashboard"></i>
                        </span>
                        <span class="nav-link-title">Tableau de bord</span>
                    </a>
                </li>

                {{-- Statistiques --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.stats') ? 'active' : '' }}"
                       href="{{ route('admin.stats') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-chart-bar"></i>
                        </span>
                        <span class="nav-link-title">Statistiques</span>
                    </a>
                </li>

                {{-- Contenu --}}
                <li class="nav-item dropdown {{ $isContenu ? 'show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isContenu ? 'show' : '' }}"
                       href="#navbar-contenu"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="false"
                       role="button"
                       aria-expanded="{{ $isContenu ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-layout-list"></i>
                        </span>
                        <span class="nav-link-title">Contenu</span>
                    </a>
                    <div class="dropdown-menu {{ $isContenu ? 'show' : '' }}" id="navbar-contenu">
                        <a class="dropdown-item {{ request()->routeIs('admin.blog.articles.*') ? 'active' : '' }}"
                           href="{{ route('admin.blog.articles.index') }}">
                            <i class="ti ti-article me-2"></i>Articles
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.blog.comments.*') ? 'active' : '' }}"
                           href="{{ route('admin.blog.comments.index') }}">
                            <i class="ti ti-message-circle me-2"></i>Commentaires
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}"
                           href="{{ route('admin.blog.categories.index') }}">
                            <i class="ti ti-category me-2"></i>Catégories
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}"
                           href="{{ route('admin.pages.index') }}">
                            <i class="ti ti-file-text me-2"></i>Pages
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.media.*') ? 'active' : '' }}"
                           href="{{ route('admin.media.index') }}">
                            <i class="ti ti-photo me-2"></i>Médias
                        </a>
                    </div>
                </li>

                {{-- Utilisateurs --}}
                <li class="nav-item dropdown {{ $isUtilisateurs ? 'show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isUtilisateurs ? 'show' : '' }}"
                       href="#navbar-utilisateurs"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="false"
                       role="button"
                       aria-expanded="{{ $isUtilisateurs ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-users"></i>
                        </span>
                        <span class="nav-link-title">Utilisateurs</span>
                    </a>
                    <div class="dropdown-menu {{ $isUtilisateurs ? 'show' : '' }}" id="navbar-utilisateurs">
                        <a class="dropdown-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           href="{{ route('admin.users.index') }}">
                            <i class="ti ti-users me-2"></i>Membres
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                           href="{{ route('admin.roles.index') }}">
                            <i class="ti ti-shield me-2"></i>Rôles
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.newsletter.*') ? 'active' : '' }}"
                           href="{{ route('admin.newsletter.index') }}">
                            <i class="ti ti-mail me-2"></i>Newsletter
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active' : '' }}"
                           href="{{ route('admin.newsletter.campaigns.index') }}">
                            <i class="ti ti-speakerphone me-2"></i>Campagnes
                        </a>
                    </div>
                </li>

                {{-- Monétisation --}}
                <li class="nav-item dropdown {{ $isMonetisation ? 'show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isMonetisation ? 'show' : '' }}"
                       href="#navbar-monetisation"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="false"
                       role="button"
                       aria-expanded="{{ $isMonetisation ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-credit-card"></i>
                        </span>
                        <span class="nav-link-title">Monétisation</span>
                    </a>
                    <div class="dropdown-menu {{ $isMonetisation ? 'show' : '' }}" id="navbar-monetisation">
                        <a class="dropdown-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"
                           href="{{ route('admin.plans.index') }}">
                            <i class="ti ti-credit-card me-2"></i>Plans
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.revenue.*') ? 'active' : '' }}"
                           href="{{ route('admin.revenue') }}">
                            <i class="ti ti-currency-dollar me-2"></i>Revenus
                        </a>
                    </div>
                </li>

                {{-- Configuration --}}
                <li class="nav-item dropdown {{ $isConfiguration ? 'show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isConfiguration ? 'show' : '' }}"
                       href="#navbar-configuration"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="false"
                       role="button"
                       aria-expanded="{{ $isConfiguration ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-settings"></i>
                        </span>
                        <span class="nav-link-title">Configuration</span>
                    </a>
                    <div class="dropdown-menu {{ $isConfiguration ? 'show' : '' }}" id="navbar-configuration">
                        <a class="dropdown-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                           href="{{ route('admin.settings.index') }}">
                            <i class="ti ti-settings me-2"></i>Paramètres
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}"
                           href="{{ route('admin.branding.edit') }}">
                            <i class="ti ti-palette me-2"></i>Personnalisation
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}"
                           href="{{ route('admin.seo.index') }}">
                            <i class="ti ti-search me-2"></i>SEO
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.feature-flags.*') ? 'active' : '' }}"
                           href="{{ route('admin.feature-flags.index') }}">
                            <i class="ti ti-flag me-2"></i>Feature Flags
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.translations.*') ? 'active' : '' }}"
                           href="{{ route('admin.translations.index') }}">
                            <i class="ti ti-language me-2"></i>Traductions
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}"
                           href="{{ route('admin.plugins.index') }}">
                            <i class="ti ti-puzzle me-2"></i>Plugins
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}"
                           href="{{ route('admin.email-templates.index') }}">
                            <i class="ti ti-mail-forward me-2"></i>Emails templates
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}"
                           href="{{ route('admin.webhooks.index') }}">
                            <i class="ti ti-webhook me-2"></i>Webhooks
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.shortcodes.*') ? 'active' : '' }}"
                           href="{{ route('admin.shortcodes.index') }}">
                            <i class="ti ti-code me-2"></i>Shortcodes
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.cookie-categories.*') ? 'active' : '' }}"
                           href="{{ route('admin.cookie-categories.index') }}">
                            <i class="ti ti-cookie me-2"></i>Cookies GDPR
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.onboarding-steps.*') ? 'active' : '' }}"
                           href="{{ route('admin.onboarding-steps.index') }}">
                            <i class="ti ti-stairs me-2"></i>Onboarding
                        </a>
                    </div>
                </li>

                {{-- Sécurité --}}
                <li class="nav-item dropdown {{ $isSecurite ? 'show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isSecurite ? 'show' : '' }}"
                       href="#navbar-securite"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="false"
                       role="button"
                       aria-expanded="{{ $isSecurite ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-shield-lock"></i>
                        </span>
                        <span class="nav-link-title">Sécurité</span>
                    </a>
                    <div class="dropdown-menu {{ $isSecurite ? 'show' : '' }}" id="navbar-securite">
                        <a class="dropdown-item {{ request()->routeIs('admin.security.*') ? 'active' : '' }}"
                           href="{{ route('admin.security') }}">
                            <i class="ti ti-shield-lock me-2"></i>Tableau de bord
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.blocked-ips.*') ? 'active' : '' }}"
                           href="{{ route('admin.blocked-ips.index') }}">
                            <i class="ti ti-ban me-2"></i>IPs bloquées
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.login-history.*') ? 'active' : '' }}"
                           href="{{ route('admin.login-history') }}">
                            <i class="ti ti-login me-2"></i>Connexions
                        </a>
                    </div>
                </li>

                {{-- Outils --}}
                <li class="nav-item dropdown {{ $isOutils ? 'show' : '' }}">
                    <a class="nav-link dropdown-toggle {{ $isOutils ? 'show' : '' }}"
                       href="#navbar-outils"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="false"
                       role="button"
                       aria-expanded="{{ $isOutils ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-tool"></i>
                        </span>
                        <span class="nav-link-title">Outils</span>
                    </a>
                    <div class="dropdown-menu {{ $isOutils ? 'show' : '' }}" id="navbar-outils">
                        <a class="dropdown-item {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}"
                           href="{{ route('admin.backups.index') }}">
                            <i class="ti ti-database-export me-2"></i>Sauvegardes
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}"
                           href="{{ route('admin.activity-logs.index') }}">
                            <i class="ti ti-activity me-2"></i>Journaux d'activité
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"
                           href="{{ route('admin.logs') }}">
                            <i class="ti ti-file-analytics me-2"></i>Journaux app
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.failed-jobs.*') ? 'active' : '' }}"
                           href="{{ route('admin.failed-jobs.index') }}">
                            <i class="ti ti-alert-triangle me-2"></i>Jobs échoués
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}"
                           href="{{ route('admin.trash.index') }}">
                            <i class="ti ti-trash me-2"></i>Corbeille
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.health.*') ? 'active' : '' }}"
                           href="{{ route('admin.health') }}">
                            <i class="ti ti-heartbeat me-2"></i>Santé système
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.scheduler.*') ? 'active' : '' }}"
                           href="{{ route('admin.scheduler') }}">
                            <i class="ti ti-clock me-2"></i>Scheduler
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.mail-logs.*') ? 'active' : '' }}"
                           href="{{ route('admin.mail-log') }}">
                            <i class="ti ti-mail-check me-2"></i>Emails envoyés
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.cache.*') ? 'active' : '' }}"
                           href="{{ route('admin.cache') }}">
                            <i class="ti ti-database me-2"></i>Cache
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.system-info.*') ? 'active' : '' }}"
                           href="{{ route('admin.system-info') }}">
                            <i class="ti ti-info-circle me-2"></i>Infos système
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.data-retention.*') ? 'active' : '' }}"
                           href="{{ route('admin.data-retention') }}">
                            <i class="ti ti-hourglass me-2"></i>Rétention données
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
                           href="{{ route('admin.notifications.index') }}">
                            <i class="ti ti-bell me-2"></i>Notifications
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.push-notifications.*') ? 'active' : '' }}"
                           href="{{ route('admin.push-notifications.index') }}">
                            <i class="ti ti-device-mobile me-2"></i>Push notifications
                        </a>

                        <div class="dropdown-divider"></div>

                        <a class="dropdown-item"
                           href="/horizon"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="ti ti-brand-docker me-2"></i>Horizon
                            <i class="ti ti-external-link ms-auto" style="font-size: 0.75rem; opacity: 0.6;"></i>
                        </a>
                        <a class="dropdown-item"
                           href="/telescope"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="ti ti-telescope me-2"></i>Telescope
                            <i class="ti ti-external-link ms-auto" style="font-size: 0.75rem; opacity: 0.6;"></i>
                        </a>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</aside>
