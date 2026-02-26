<aside class="sidebar">
    <button type="button" class="sidebar-close-btn" aria-label="{{ __('Fermer le menu') }}">
        <iconify-icon icon="radix-icons:cross-2" aria-hidden="true"></iconify-icon>
    </button>
    <div>
        @php
            $siteName = $branding['site_name'] ?? config('app.name');
            $initial  = strtoupper(substr($siteName, 0, 1));
            $color    = $branding['primary_color'] ?? '#487FFF';
            $svgBase  = '<circle cx="18" cy="18" r="15" fill="' . $color . '"/><text x="18" y="23" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text>';
            $svgLight = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#1f2937">' . htmlspecialchars($siteName) . '</text></svg>');
            $svgDark  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#f1f5f9">' . htmlspecialchars($siteName) . '</text></svg>');
            $svgIcon  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><circle cx="16" cy="16" r="14" fill="' . $color . '"/><text x="16" y="21" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text></svg>');
        @endphp
        <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
            <img src="{{ ! empty($branding['logo_light'] ?? '') ? asset('storage/' . $branding['logo_light']) : $svgLight }}" alt="{{ $siteName }}" class="light-logo">
            <img src="{{ ! empty($branding['logo_dark'] ?? '')  ? asset('storage/' . $branding['logo_dark'])  : $svgDark }}"  alt="{{ $siteName }}" class="dark-logo">
            <img src="{{ ! empty($branding['logo_icon'] ?? '')  ? asset('storage/' . $branding['logo_icon'])  : $svgIcon }}"  alt="{{ $siteName }}" class="logo-icon">
        </a>
    </div>
    <nav class="sidebar-menu-area" aria-label="{{ __('Menu administration') }}">
        <ul class="sidebar-menu" id="sidebar-menu">

            {{-- Tableau de bord --}}
            <li class="{{ request()->routeIs('admin.dashboard') ? 'active-page' : '' }}">
                <a href="{{ route('admin.dashboard') }}">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Tableau de bord</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.stats') ? 'active-page' : '' }}">
                <a href="{{ route('admin.stats') }}">
                    <iconify-icon icon="solar:chart-square-outline" class="menu-icon"></iconify-icon>
                    <span>Statistiques</span>
                </a>
            </li>

            {{-- ===== CONTENU ===== --}}
            <li class="sidebar-menu-group-title">Contenu</li>

            <li class="dropdown {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:widget-2-outline" class="menu-icon"></iconify-icon>
                    <span>Contenu</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('admin.blog.articles.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.blog.articles.index') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Articles
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blog.comments.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.blog.comments.index') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Commentaires
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blog.categories.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.blog.categories.index') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Catégories
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.pages.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.pages.index') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Pages
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.media.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.media.index') }}">
                            <i class="ri-circle-fill circle-icon text-purple w-auto"></i> Médias
                        </a>
                    </li>
                </ul>
            </li>

            {{-- ===== UTILISATEURS ===== --}}
            <li class="sidebar-menu-group-title">Utilisateurs</li>

            <li class="dropdown {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.newsletter.*') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                    <span>Utilisateurs</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('admin.users.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.users.index') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Membres
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.roles.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.roles.index') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Rôles
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.newsletter.index') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.newsletter.index') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Newsletter
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.newsletter.campaigns.index') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Campagnes
                        </a>
                    </li>
                </ul>
            </li>

            {{-- ===== MONÉTISATION ===== --}}
            <li class="sidebar-menu-group-title">Monétisation</li>

            <li class="{{ request()->routeIs('admin.plans.*') ? 'active-page' : '' }}">
                <a href="{{ route('admin.plans.index') }}">
                    <iconify-icon icon="solar:tag-price-outline" class="menu-icon"></iconify-icon>
                    <span>Plans</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('admin.revenue') ? 'active-page' : '' }}">
                <a href="{{ route('admin.revenue') }}">
                    <iconify-icon icon="solar:chart-2-outline" class="menu-icon"></iconify-icon>
                    <span>Revenus</span>
                </a>
            </li>

            {{-- ===== CONFIGURATION ===== --}}
            <li class="sidebar-menu-group-title">Configuration</li>

            <li class="dropdown {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
                    <span>Configuration</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('admin.settings.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.settings.index') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Paramètres
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.branding.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.branding.edit') }}">
                            <i class="ri-circle-fill circle-icon text-purple w-auto"></i> Personnalisation
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.seo.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.seo.index') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> SEO
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.feature-flags.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.feature-flags.index') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Feature Flags
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.translations.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.translations.index') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Traductions
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.plugins.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.plugins.index') }}">
                            <i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Plugins
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.email-templates.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.email-templates.index') }}">
                            <i class="ri-circle-fill circle-icon text-purple w-auto"></i> Emails templates
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.webhooks.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.webhooks.index') }}">
                            <i class="ri-circle-fill circle-icon text-neutral-400 w-auto"></i> Webhooks
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.shortcodes.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.shortcodes.index') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Shortcodes
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.cookie-categories.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.cookie-categories.index') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Cookies GDPR
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.onboarding-steps.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.onboarding-steps.index') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Onboarding
                        </a>
                    </li>
                </ul>
            </li>

            {{-- ===== SÉCURITÉ ===== --}}
            <li class="sidebar-menu-group-title">Sécurité</li>

            <li class="dropdown {{ request()->routeIs('admin.security', 'admin.blocked-ips.*', 'admin.login-history') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:lock-outline" class="menu-icon"></iconify-icon>
                    <span>Sécurité</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('admin.security') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.security') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.blocked-ips.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.blocked-ips.index') }}">
                            <i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> IPs bloquées
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.login-history') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.login-history') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Connexions
                        </a>
                    </li>
                </ul>
            </li>

            {{-- ===== OUTILS ===== --}}
            <li class="sidebar-menu-group-title">Outils</li>

            <li class="dropdown {{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention', 'admin.notifications.*', 'admin.push-notifications.*') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:shield-check-outline" class="menu-icon"></iconify-icon>
                    <span>Outils</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('admin.backups.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.backups.index') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Sauvegardes
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.activity-logs.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.activity-logs.index') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Journaux d'activité
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.logs') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.logs') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Journaux app
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.failed-jobs.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.failed-jobs.index') }}">
                            <i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Jobs échoués
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.trash.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.trash.index') }}">
                            <i class="ri-circle-fill circle-icon text-neutral-400 w-auto"></i> Corbeille
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.health') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.health') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Santé système
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.scheduler') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.scheduler') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Scheduler
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.mail-log') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.mail-log') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Emails envoyés
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.cache') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.cache') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Cache
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.system-info') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.system-info') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Infos système
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.data-retention') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.data-retention') }}">
                            <i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Rétention données
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.notifications.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.notifications.index') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Notifications
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.push-notifications.*') ? 'active-page' : '' }}">
                        <a href="{{ route('admin.push-notifications.index') }}">
                            <i class="ri-circle-fill circle-icon text-purple w-auto"></i> Push notifications
                        </a>
                    </li>
                    <li>
                        <a href="/horizon" target="_blank">
                            <i class="ri-circle-fill circle-icon text-purple w-auto"></i> Horizon ↗
                        </a>
                    </li>
                    <li>
                        <a href="/telescope" target="_blank">
                            <i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Telescope ↗
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </nav>
</aside>
