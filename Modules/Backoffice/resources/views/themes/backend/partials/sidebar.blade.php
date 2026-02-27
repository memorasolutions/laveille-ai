<nav class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
            {{ $branding['site_name'] ?? config('app.name') }}
        </a>
        <div class="sidebar-toggler not-active">
            <span></span><span></span><span></span>
        </div>
    </div>
    <div class="sidebar-body">
        <ul class="nav" id="sidebarNav">

            {{-- ===== PRINCIPAL ===== --}}
            <li class="nav-item nav-category">{{ __('Principal') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i class="link-icon" data-lucide="home"></i>
                    <span class="link-title">{{ __('Tableau de bord') }}</span>
                </a>
            </li>
            @if(Route::has('admin.stats'))
            <li class="nav-item {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                <a href="{{ route('admin.stats') }}" class="nav-link">
                    <i class="link-icon" data-lucide="bar-chart-2"></i>
                    <span class="link-title">{{ __('Statistiques') }}</span>
                </a>
            </li>
            @endif

            {{-- ===== CONTENU ===== --}}
            <li class="nav-item nav-category">{{ __('Contenu') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.menus.*', 'admin.faqs.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#contentMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.menus.*', 'admin.faqs.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="file-text"></i>
                    <span class="link-title">{{ __('Contenu') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.menus.*', 'admin.faqs.*') ? 'show' : '' }}" id="contentMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @if(Route::has('admin.blog.articles.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.articles.index') }}" class="nav-link {{ request()->routeIs('admin.blog.articles.*') ? 'active' : '' }}">{{ __('Articles') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.blog.comments.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.comments.index') }}" class="nav-link {{ request()->routeIs('admin.blog.comments.*') ? 'active' : '' }}">{{ __('Commentaires') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.blog.categories.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.categories.index') }}" class="nav-link {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}">{{ __('Catégories') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.pages.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.pages.index') }}" class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">{{ __('Pages') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.media.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.media.index') }}" class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">{{ __('Médias') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.menus.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.menus.index') }}" class="nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">{{ __('Menus') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.faqs.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">{{ __('FAQ') }}</a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>

            {{-- ===== UTILISATEURS ===== --}}
            <li class="nav-item nav-category">{{ __('Utilisateurs') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.newsletter.*', 'admin.contact-messages.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#usersMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.newsletter.*', 'admin.contact-messages.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="users"></i>
                    <span class="link-title">{{ __('Utilisateurs') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.newsletter.*', 'admin.contact-messages.*') ? 'show' : '' }}" id="usersMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">{{ __('Membres') }}</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">{{ __('Rôles') }}</a>
                        </li>
                        @if(Route::has('admin.newsletter.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.index') ? 'active' : '' }}">{{ __('Newsletter') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.newsletter.campaigns.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active' : '' }}">{{ __('Campagnes') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.contact-messages.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.contact-messages.index') }}" class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}">{{ __('Messages') }}</a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>

            {{-- ===== MONÉTISATION ===== --}}
            <li class="nav-item nav-category">{{ __('Monétisation') }}</li>
            @if(Route::has('admin.plans.index'))
            <li class="nav-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                <a href="{{ route('admin.plans.index') }}" class="nav-link">
                    <i class="link-icon" data-lucide="credit-card"></i>
                    <span class="link-title">{{ __('Plans') }}</span>
                </a>
            </li>
            @endif
            @if(Route::has('admin.revenue'))
            <li class="nav-item {{ request()->routeIs('admin.revenue') ? 'active' : '' }}">
                <a href="{{ route('admin.revenue') }}" class="nav-link">
                    <i class="link-icon" data-lucide="dollar-sign"></i>
                    <span class="link-title">{{ __('Revenus') }}</span>
                </a>
            </li>
            @endif

            {{-- ===== CONFIGURATION ===== --}}
            <li class="nav-item nav-category">{{ __('Configuration') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#configMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="settings"></i>
                    <span class="link-title">{{ __('Configuration') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*') ? 'show' : '' }}" id="configMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        <li class="nav-item"><a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">{{ __('Paramètres') }}</a></li>
                        @if(Route::has('admin.branding.edit'))
                        <li class="nav-item"><a href="{{ route('admin.branding.edit') }}" class="nav-link {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}">{{ __('Personnalisation') }}</a></li>
                        @endif
                        <li class="nav-item"><a href="{{ route('admin.seo.index') }}" class="nav-link {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}">{{ __('SEO') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.feature-flags.index') }}" class="nav-link {{ request()->routeIs('admin.feature-flags.*') ? 'active' : '' }}">{{ __('Feature Flags') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.translations.index') }}" class="nav-link {{ request()->routeIs('admin.translations.*') ? 'active' : '' }}">{{ __('Traductions') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.plugins.index') }}" class="nav-link {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}">{{ __('Plugins') }}</a></li>
                        @if(Route::has('admin.email-templates.index'))
                        <li class="nav-item"><a href="{{ route('admin.email-templates.index') }}" class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">{{ __('Emails templates') }}</a></li>
                        @endif
                        @if(Route::has('admin.webhooks.index'))
                        <li class="nav-item"><a href="{{ route('admin.webhooks.index') }}" class="nav-link {{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}">{{ __('Webhooks') }}</a></li>
                        @endif
                        @if(Route::has('admin.shortcodes.index'))
                        <li class="nav-item"><a href="{{ route('admin.shortcodes.index') }}" class="nav-link {{ request()->routeIs('admin.shortcodes.*') ? 'active' : '' }}">{{ __('Shortcodes') }}</a></li>
                        @endif
                        @if(Route::has('admin.cookie-categories.index'))
                        <li class="nav-item"><a href="{{ route('admin.cookie-categories.index') }}" class="nav-link {{ request()->routeIs('admin.cookie-categories.*') ? 'active' : '' }}">{{ __('Cookies GDPR') }}</a></li>
                        @endif
                        @if(Route::has('admin.onboarding-steps.index'))
                        <li class="nav-item"><a href="{{ route('admin.onboarding-steps.index') }}" class="nav-link {{ request()->routeIs('admin.onboarding-steps.*') ? 'active' : '' }}">{{ __('Onboarding') }}</a></li>
                        @endif
                    </ul>
                </div>
            </li>

            {{-- ===== SÉCURITÉ ===== --}}
            <li class="nav-item nav-category">{{ __('Sécurité') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.security', 'admin.blocked-ips.*', 'admin.login-history') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#securityMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.security', 'admin.blocked-ips.*', 'admin.login-history') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="shield"></i>
                    <span class="link-title">{{ __('Sécurité') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.security', 'admin.blocked-ips.*', 'admin.login-history') ? 'show' : '' }}" id="securityMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        <li class="nav-item"><a href="{{ route('admin.security') }}" class="nav-link {{ request()->routeIs('admin.security') ? 'active' : '' }}">{{ __('Tableau de bord') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.blocked-ips.index') }}" class="nav-link {{ request()->routeIs('admin.blocked-ips.*') ? 'active' : '' }}">{{ __('IPs bloquées') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.login-history') }}" class="nav-link {{ request()->routeIs('admin.login-history') ? 'active' : '' }}">{{ __('Connexions') }}</a></li>
                    </ul>
                </div>
            </li>

            {{-- ===== OUTILS ===== --}}
            <li class="nav-item nav-category">{{ __('Outils') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention', 'admin.notifications.*', 'admin.push-notifications.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#toolsMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention', 'admin.notifications.*', 'admin.push-notifications.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="wrench"></i>
                    <span class="link-title">{{ __('Outils') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention', 'admin.notifications.*', 'admin.push-notifications.*') ? 'show' : '' }}" id="toolsMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        <li class="nav-item"><a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">{{ __('Sauvegardes') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">{{ __('Journaux d\'activité') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.logs') }}" class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">{{ __('Journaux app') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.failed-jobs.index') }}" class="nav-link {{ request()->routeIs('admin.failed-jobs.*') ? 'active' : '' }}">{{ __('Jobs échoués') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.trash.index') }}" class="nav-link {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}">{{ __('Corbeille') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.health') }}" class="nav-link {{ request()->routeIs('admin.health') ? 'active' : '' }}">{{ __('Santé système') }}</a></li>
                        @if(Route::has('admin.scheduler'))
                        <li class="nav-item"><a href="{{ route('admin.scheduler') }}" class="nav-link {{ request()->routeIs('admin.scheduler') ? 'active' : '' }}">{{ __('Scheduler') }}</a></li>
                        @endif
                        <li class="nav-item"><a href="{{ route('admin.mail-log') }}" class="nav-link {{ request()->routeIs('admin.mail-log') ? 'active' : '' }}">{{ __('Emails envoyés') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.cache') }}" class="nav-link {{ request()->routeIs('admin.cache') ? 'active' : '' }}">{{ __('Cache') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.system-info') }}" class="nav-link {{ request()->routeIs('admin.system-info') ? 'active' : '' }}">{{ __('Infos système') }}</a></li>
                        @if(Route::has('admin.data-retention'))
                        <li class="nav-item"><a href="{{ route('admin.data-retention') }}" class="nav-link {{ request()->routeIs('admin.data-retention') ? 'active' : '' }}">{{ __('Rétention données') }}</a></li>
                        @endif
                        <li class="nav-item"><a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">{{ __('Notifications') }}</a></li>
                        @if(Route::has('admin.push-notifications.index'))
                        <li class="nav-item"><a href="{{ route('admin.push-notifications.index') }}" class="nav-link {{ request()->routeIs('admin.push-notifications.*') ? 'active' : '' }}">{{ __('Push notifications') }}</a></li>
                        @endif
                    </ul>
                </div>
            </li>

        </ul>
    </div>
</nav>
