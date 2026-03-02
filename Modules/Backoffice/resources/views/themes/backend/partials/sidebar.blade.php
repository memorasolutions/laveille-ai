<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<nav class="sidebar" aria-label="Menu administration">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
            {{ $branding['site_name'] ?? config('app.name') }}
        </a>
        <button type="button" class="sidebar-toggler not-active" aria-label="Basculer le menu" aria-expanded="true" aria-controls="sidebarNav">
            <span></span><span></span><span></span>
        </button>
    </div>
    <button type="button" class="btn-close d-lg-none position-absolute top-0 end-0 m-3 sidebar-close" aria-label="Fermer le menu"></button>
    <div class="sidebar-body">
        <ul class="nav" id="sidebarNav">

            {{-- ===== PRINCIPAL ===== --}}
            <li class="nav-item nav-category">{{ __('Principal') }}</li>
            @can('view_dashboard')
            <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i class="link-icon" data-lucide="home"></i>
                    <span class="link-title">{{ __('Tableau de bord') }}</span>
                </a>
            </li>
            @endcan
            @if(Route::has('admin.stats'))
            @can('manage_system')
            <li class="nav-item {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                <a href="{{ route('admin.stats') }}" class="nav-link">
                    <i class="link-icon" data-lucide="bar-chart-2"></i>
                    <span class="link-title">{{ __('Statistiques') }}</span>
                </a>
            </li>
            @endcan
            @endif

            {{-- ===== CONTENU ===== --}}
            @canany(['manage_articles', 'manage_comments', 'manage_categories', 'manage_pages', 'manage_media', 'manage_menus', 'manage_faqs', 'manage_testimonials', 'manage_forms'])
            <li class="nav-item nav-category">{{ __('Contenu') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.menus.*', 'admin.faqs.*', 'admin.testimonials.*', 'admin.formbuilder.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#contentMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.menus.*', 'admin.faqs.*', 'admin.testimonials.*', 'admin.formbuilder.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="file-text"></i>
                    <span class="link-title">{{ __('Contenu') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.menus.*', 'admin.faqs.*', 'admin.testimonials.*', 'admin.formbuilder.*') ? 'show' : '' }}" id="contentMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @if(Route::has('admin.blog.articles.index'))
                        @can('manage_articles')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.articles.index') }}" class="nav-link {{ request()->routeIs('admin.blog.articles.*') ? 'active' : '' }}">{{ __('Articles') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.comments.index'))
                        @can('manage_comments')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.comments.index') }}" class="nav-link {{ request()->routeIs('admin.blog.comments.*') ? 'active' : '' }}">{{ __('Commentaires') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.categories.index'))
                        @can('manage_categories')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.categories.index') }}" class="nav-link {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}">{{ __('Catégories') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.tags.index'))
                        @can('manage_articles')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.tags.index') }}" class="nav-link {{ request()->routeIs('admin.blog.tags.*') ? 'active' : '' }}">{{ __('Tags') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.pages.index'))
                        @can('manage_pages')
                        <li class="nav-item">
                            <a href="{{ route('admin.pages.index') }}" class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">{{ __('Pages') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.media.index'))
                        @can('manage_media')
                        <li class="nav-item">
                            <a href="{{ route('admin.media.index') }}" class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">{{ __('Médias') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.menus.index'))
                        @can('manage_menus')
                        <li class="nav-item">
                            <a href="{{ route('admin.menus.index') }}" class="nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}">{{ __('Menus') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.faqs.index'))
                        @can('manage_faqs')
                        <li class="nav-item">
                            <a href="{{ route('admin.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">{{ __('FAQ') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.testimonials.index'))
                        @can('manage_testimonials')
                        <li class="nav-item">
                            <a href="{{ route('admin.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">{{ __('Témoignages') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.formbuilder.forms.index'))
                        @can('manage_forms')
                        <li class="nav-item">
                            <a href="{{ route('admin.formbuilder.forms.index') }}" class="nav-link {{ request()->routeIs('admin.formbuilder.*') ? 'active' : '' }}">{{ __('Formulaires') }}</a>
                        </li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== UTILISATEURS ===== --}}
            @canany(['manage_users', 'manage_roles', 'manage_teams', 'manage_newsletter', 'manage_campaigns', 'manage_workflows', 'manage_contacts'])
            <li class="nav-item nav-category">{{ __('Utilisateurs') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.teams.*', 'admin.newsletter.*', 'admin.contact-messages.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#usersMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.teams.*', 'admin.newsletter.*', 'admin.contact-messages.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="users"></i>
                    <span class="link-title">{{ __('Utilisateurs') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.teams.*', 'admin.newsletter.*', 'admin.contact-messages.*') ? 'show' : '' }}" id="usersMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @can('manage_users')
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">{{ __('Membres') }}</a>
                        </li>
                        @endcan
                        @can('manage_roles')
                        <li class="nav-item">
                            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">{{ __('Rôles') }}</a>
                        </li>
                        @endcan
                        @if(Route::has('admin.teams.index'))
                        @can('manage_teams')
                        <li class="nav-item">
                            <a href="{{ route('admin.teams.index') }}" class="nav-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">{{ __('Équipes') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.index'))
                        @can('manage_newsletter')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.index') ? 'active' : '' }}">{{ __('Newsletter') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.campaigns.index'))
                        @can('manage_campaigns')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active' : '' }}">{{ __('Campagnes') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.templates.index'))
                        @can('manage_newsletter')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.templates.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.templates.*') ? 'active' : '' }}">{{ __('Templates marketing') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.workflows.index'))
                        @can('manage_workflows')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.workflows.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.workflows.*') ? 'active' : '' }}">{{ __('Workflows') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.contact-messages.index'))
                        @can('manage_contacts')
                        <li class="nav-item">
                            <a href="{{ route('admin.contact-messages.index') }}" class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}">{{ __('Messages') }}</a>
                        </li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== MONÉTISATION ===== --}}
            @can('manage_plans')
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
            @endcan

            {{-- ===== INTELLIGENCE ARTIFICIELLE ===== --}}
            @can('manage_ai')
            <li class="nav-item nav-category">{{ __('Intelligence artificielle') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#aiMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.ai.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="brain"></i>
                    <span class="link-title">{{ __('IA') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.ai.*') ? 'show' : '' }}" id="aiMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @if(Route::has('admin.ai.conversations.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.ai.conversations.index') }}" class="nav-link {{ request()->routeIs('admin.ai.conversations.*') ? 'active' : '' }}">{{ __('Conversations') }}</a>
                        </li>
                        @endif
                        @if(Route::has('admin.ai.agent.index'))
                        <li class="nav-item">
                            <a href="{{ route('admin.ai.agent.index') }}" class="nav-link {{ request()->routeIs('admin.ai.agent.*') ? 'active' : '' }}">
                                {{ __('Agent dashboard') }}
                                @php $waitingCount = class_exists(\Modules\AI\Models\AiConversation::class) ? \Modules\AI\Models\AiConversation::where('status', 'waiting_human')->count() : 0; @endphp
                                @if($waitingCount > 0)
                                <span class="badge bg-warning text-dark ms-1">{{ $waitingCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @if(Route::has('admin.ai.analytics'))
                        <li class="nav-item">
                            <a href="{{ route('admin.ai.analytics') }}" class="nav-link {{ request()->routeIs('admin.ai.analytics') ? 'active' : '' }}">{{ __('Analytics') }}</a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endcan

            {{-- ===== CONFIGURATION ===== --}}
            @canany(['manage_settings', 'manage_branding', 'manage_seo', 'manage_feature_flags', 'manage_translations', 'manage_system', 'manage_email_templates', 'manage_webhooks', 'manage_shortcodes', 'manage_cookies', 'manage_onboarding', 'manage_widgets', 'manage_settings'])
            <li class="nav-item nav-category">{{ __('Configuration') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*', 'admin.widgets.*', 'admin.custom-fields.*', 'admin.import.*', 'admin.experiments.*', 'admin.announcements.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#configMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*', 'admin.widgets.*', 'admin.custom-fields.*', 'admin.import.*', 'admin.experiments.*', 'admin.announcements.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="settings"></i>
                    <span class="link-title">{{ __('Configuration') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.feature-flags.*', 'admin.translations.*', 'admin.plugins.*', 'admin.email-templates.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.cookie-categories.*', 'admin.onboarding-steps.*', 'admin.widgets.*', 'admin.custom-fields.*', 'admin.import.*', 'admin.experiments.*', 'admin.announcements.*') ? 'show' : '' }}" id="configMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">{{ __('Paramètres') }}</a></li>
                        @endcan
                        @if(Route::has('admin.branding.edit'))
                        @can('manage_branding')
                        <li class="nav-item"><a href="{{ route('admin.branding.edit') }}" class="nav-link {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}">{{ __('Personnalisation') }}</a></li>
                        @endcan
                        @endif
                        @can('manage_seo')
                        <li class="nav-item"><a href="{{ route('admin.seo.index') }}" class="nav-link {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}">{{ __('SEO') }}</a></li>
                        @endcan
                        @can('manage_feature_flags')
                        <li class="nav-item"><a href="{{ route('admin.feature-flags.index') }}" class="nav-link {{ request()->routeIs('admin.feature-flags.*') ? 'active' : '' }}">{{ __('Feature Flags') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.experiments.index') }}" class="nav-link {{ request()->routeIs('admin.experiments.*') ? 'active' : '' }}">{{ __('Tests A/B') }}</a></li>
                        @endcan
                        @can('manage_translations')
                        <li class="nav-item"><a href="{{ route('admin.translations.index') }}" class="nav-link {{ request()->routeIs('admin.translations.*') ? 'active' : '' }}">{{ __('Traductions') }}</a></li>
                        @endcan
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.plugins.index') }}" class="nav-link {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}">{{ __('Plugins') }}</a></li>
                        @endcan
                        @if(Route::has('admin.email-templates.index'))
                        @can('manage_email_templates')
                        <li class="nav-item"><a href="{{ route('admin.email-templates.index') }}" class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">{{ __('Emails templates') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.webhooks.index'))
                        @can('manage_webhooks')
                        <li class="nav-item"><a href="{{ route('admin.webhooks.index') }}" class="nav-link {{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}">{{ __('Webhooks') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.shortcodes.index'))
                        @can('manage_shortcodes')
                        <li class="nav-item"><a href="{{ route('admin.shortcodes.index') }}" class="nav-link {{ request()->routeIs('admin.shortcodes.*') ? 'active' : '' }}">{{ __('Shortcodes') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.cookie-categories.index'))
                        @can('manage_cookies')
                        <li class="nav-item"><a href="{{ route('admin.cookie-categories.index') }}" class="nav-link {{ request()->routeIs('admin.cookie-categories.*') ? 'active' : '' }}">{{ __('Cookies GDPR') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.onboarding-steps.index'))
                        @can('manage_onboarding')
                        <li class="nav-item"><a href="{{ route('admin.onboarding-steps.index') }}" class="nav-link {{ request()->routeIs('admin.onboarding-steps.*') ? 'active' : '' }}">{{ __('Onboarding') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.widgets.index'))
                        @can('manage_widgets')
                        <li class="nav-item"><a href="{{ route('admin.widgets.index') }}" class="nav-link {{ request()->routeIs('admin.widgets.*') ? 'active' : '' }}">{{ __('Widgets') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.custom-fields.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.custom-fields.index') }}" class="nav-link {{ request()->routeIs('admin.custom-fields.*') ? 'active' : '' }}">{{ __('Champs perso.') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.import.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.import.index') }}" class="nav-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }}">{{ __('Import') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.announcements.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">{{ __('Annonces') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== SÉCURITÉ ===== --}}
            @can('manage_security')
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
            @endcan

            {{-- ===== OUTILS ===== --}}
            @canany(['manage_backups', 'manage_activity_logs', 'view_logs', 'view_health', 'manage_trash', 'manage_system', 'manage_notifications'])
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
                        @can('manage_backups')
                        <li class="nav-item"><a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">{{ __('Sauvegardes') }}</a></li>
                        @endcan
                        @can('manage_activity_logs')
                        <li class="nav-item"><a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">{{ __('Journaux d\'activité') }}</a></li>
                        @endcan
                        @can('view_logs')
                        <li class="nav-item"><a href="{{ route('admin.logs') }}" class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">{{ __('Journaux app') }}</a></li>
                        @endcan
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.failed-jobs.index') }}" class="nav-link {{ request()->routeIs('admin.failed-jobs.*') ? 'active' : '' }}">{{ __('Jobs échoués') }}</a></li>
                        @endcan
                        @can('manage_trash')
                        <li class="nav-item"><a href="{{ route('admin.trash.index') }}" class="nav-link {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}">{{ __('Corbeille') }}</a></li>
                        @endcan
                        @can('view_health')
                        <li class="nav-item"><a href="{{ route('admin.health') }}" class="nav-link {{ request()->routeIs('admin.health') ? 'active' : '' }}">{{ __('Santé système') }}</a></li>
                        @endcan
                        @if(Route::has('admin.scheduler'))
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.scheduler') }}" class="nav-link {{ request()->routeIs('admin.scheduler') ? 'active' : '' }}">{{ __('Scheduler') }}</a></li>
                        @endcan
                        @endif
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.mail-log') }}" class="nav-link {{ request()->routeIs('admin.mail-log') ? 'active' : '' }}">{{ __('Emails envoyés') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.cache') }}" class="nav-link {{ request()->routeIs('admin.cache') ? 'active' : '' }}">{{ __('Cache') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.system-info') }}" class="nav-link {{ request()->routeIs('admin.system-info') ? 'active' : '' }}">{{ __('Infos système') }}</a></li>
                        @endcan
                        @if(Route::has('admin.data-retention'))
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.data-retention') }}" class="nav-link {{ request()->routeIs('admin.data-retention') ? 'active' : '' }}">{{ __('Rétention données') }}</a></li>
                        @endcan
                        @endif
                        @can('manage_notifications')
                        <li class="nav-item"><a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">{{ __('Notifications') }}</a></li>
                        @endcan
                        @if(Route::has('admin.push-notifications.index'))
                        @can('manage_notifications')
                        <li class="nav-item"><a href="{{ route('admin.push-notifications.index') }}" class="nav-link {{ request()->routeIs('admin.push-notifications.*') ? 'active' : '' }}">{{ __('Push notifications') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

        </ul>
    </div>
</nav>
