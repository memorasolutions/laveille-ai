<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<nav class="sidebar" aria-label="Menu administration" style="background:var(--sidebar-bg, #0c1427);">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand" style="font-family:var(--topbar-font-family);font-size:var(--topbar-font-size);font-weight:var(--topbar-font-weight);letter-spacing:var(--topbar-letter-spacing);word-spacing:var(--topbar-word-spacing);text-transform:var(--topbar-text-transform);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
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
                <a href="{{ route('admin.dashboard') }}" class="nav-link" {{ request()->routeIs('admin.dashboard') ? 'aria-current=page' : '' }}>
                    <i class="link-icon" data-lucide="home"></i>
                    <span class="link-title">{{ __('Tableau de bord') }}</span>
                </a>
            </li>
            @endcan
            @if(Route::has('admin.stats'))
            @can('manage_system')
            <li class="nav-item {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                <a href="{{ route('admin.stats') }}" class="nav-link" {{ request()->routeIs('admin.stats') ? 'aria-current=page' : '' }}>
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
                            <a href="{{ route('admin.blog.articles.index') }}" class="nav-link {{ request()->routeIs('admin.blog.articles.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.articles.*') ? 'aria-current=page' : '' }}>{{ __('Articles') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.comments.index'))
                        @can('manage_comments')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.comments.index') }}" class="nav-link {{ request()->routeIs('admin.blog.comments.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.comments.*') ? 'aria-current=page' : '' }}>{{ __('Commentaires') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.categories.index'))
                        @can('manage_categories')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.categories.index') }}" class="nav-link {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.categories.*') ? 'aria-current=page' : '' }}>{{ __('Catégories') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.tags.index'))
                        @can('manage_articles')
                        <li class="nav-item">
                            <a href="{{ route('admin.blog.tags.index') }}" class="nav-link {{ request()->routeIs('admin.blog.tags.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.tags.*') ? 'aria-current=page' : '' }}>{{ __('Tags') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.pages.index'))
                        @can('manage_pages')
                        <li class="nav-item">
                            <a href="{{ route('admin.pages.index') }}" class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" {{ request()->routeIs('admin.pages.*') ? 'aria-current=page' : '' }}>{{ __('Pages') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.media.index'))
                        @can('manage_media')
                        <li class="nav-item">
                            <a href="{{ route('admin.media.index') }}" class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}" {{ request()->routeIs('admin.media.*') ? 'aria-current=page' : '' }}>{{ __('Médias') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.menus.index'))
                        @can('manage_menus')
                        <li class="nav-item">
                            <a href="{{ route('admin.menus.index') }}" class="nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}" {{ request()->routeIs('admin.menus.*') ? 'aria-current=page' : '' }}>{{ __('Menus') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.faqs.index'))
                        @can('manage_faqs')
                        <li class="nav-item">
                            <a href="{{ route('admin.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}" {{ request()->routeIs('admin.faqs.*') ? 'aria-current=page' : '' }}>{{ __('FAQ') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.testimonials.index'))
                        @can('manage_testimonials')
                        <li class="nav-item">
                            <a href="{{ route('admin.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}" {{ request()->routeIs('admin.testimonials.*') ? 'aria-current=page' : '' }}>{{ __('Témoignages') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.formbuilder.forms.index'))
                        @can('manage_forms')
                        <li class="nav-item">
                            <a href="{{ route('admin.formbuilder.forms.index') }}" class="nav-link {{ request()->routeIs('admin.formbuilder.*') ? 'active' : '' }}" {{ request()->routeIs('admin.formbuilder.*') ? 'aria-current=page' : '' }}>{{ __('Formulaires') }}</a>
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
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" {{ request()->routeIs('admin.users.*') ? 'aria-current=page' : '' }}>{{ __('Membres') }}</a>
                        </li>
                        @endcan
                        @can('manage_roles')
                        <li class="nav-item">
                            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" {{ request()->routeIs('admin.roles.*') ? 'aria-current=page' : '' }}>{{ __('Rôles') }}</a>
                        </li>
                        @endcan
                        @if(Route::has('admin.teams.index'))
                        @can('manage_teams')
                        <li class="nav-item">
                            <a href="{{ route('admin.teams.index') }}" class="nav-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}" {{ request()->routeIs('admin.teams.*') ? 'aria-current=page' : '' }}>{{ __('Équipes') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.index'))
                        @can('manage_newsletter')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.index') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.index') ? 'aria-current=page' : '' }}>{{ __('Newsletter') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.campaigns.index'))
                        @can('manage_campaigns')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'aria-current=page' : '' }}>{{ __('Campagnes') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.templates.index'))
                        @can('manage_newsletter')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.templates.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.templates.*') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.templates.*') ? 'aria-current=page' : '' }}>{{ __('Templates marketing') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.newsletter.workflows.index'))
                        @can('manage_workflows')
                        <li class="nav-item">
                            <a href="{{ route('admin.newsletter.workflows.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.workflows.*') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.workflows.*') ? 'aria-current=page' : '' }}>{{ __('Workflows') }}</a>
                        </li>
                        @endcan
                        @endif
                        @if(Route::has('admin.contact-messages.index'))
                        @can('manage_contacts')
                        <li class="nav-item">
                            <a href="{{ route('admin.contact-messages.index') }}" class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}" {{ request()->routeIs('admin.contact-messages.*') ? 'aria-current=page' : '' }}>{{ __('Messages') }}</a>
                        </li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== BUSINESS ===== --}}
            @canany(['manage_plans', 'manage_ai'])
            <li class="nav-item nav-category">{{ __('Business') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.plans.*', 'admin.revenue', 'admin.ai.conversations.*', 'admin.ai.agent.*', 'admin.ai.analytics') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#businessMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.plans.*', 'admin.revenue', 'admin.ai.conversations.*', 'admin.ai.agent.*', 'admin.ai.analytics') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="briefcase"></i>
                    <span class="link-title">{{ __('Business') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.plans.*', 'admin.revenue', 'admin.ai.conversations.*', 'admin.ai.agent.*', 'admin.ai.analytics') ? 'show' : '' }}" id="businessMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @if(Route::has('admin.plans.index'))
                        @can('manage_plans')
                        <li class="nav-item"><a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" {{ request()->routeIs('admin.plans.*') ? 'aria-current=page' : '' }}>{{ __('Plans') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.revenue'))
                        @can('manage_plans')
                        <li class="nav-item"><a href="{{ route('admin.revenue') }}" class="nav-link {{ request()->routeIs('admin.revenue') ? 'active' : '' }}" {{ request()->routeIs('admin.revenue') ? 'aria-current=page' : '' }}>{{ __('Revenus') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.conversations.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.conversations.index') }}" class="nav-link {{ request()->routeIs('admin.ai.conversations.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.conversations.*') ? 'aria-current=page' : '' }}>{{ __('Conversations IA') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.agent.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.agent.index') }}" class="nav-link {{ request()->routeIs('admin.ai.agent.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.agent.*') ? 'aria-current=page' : '' }}>{{ __('Agent dashboard') }}
                            @php $waitingCount = class_exists(\Modules\AI\Models\AiConversation::class) ? \Modules\AI\Models\AiConversation::where('status', 'waiting_human')->count() : 0; @endphp
                            @if($waitingCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $waitingCount }}</span>@endif
                        </a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.analytics'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.analytics') }}" class="nav-link {{ request()->routeIs('admin.ai.analytics') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.analytics') ? 'aria-current=page' : '' }}>{{ __('Analytics IA') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== CONFIGURATION ===== --}}
            @canany(['manage_settings', 'manage_branding', 'manage_seo', 'manage_translations', 'manage_email_templates', 'manage_onboarding', 'manage_widgets'])
            <li class="nav-item nav-category">{{ __('Configuration') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.translations.*', 'admin.email-templates.*', 'admin.onboarding-steps.*', 'admin.widgets.*', 'admin.announcements.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#configMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.translations.*', 'admin.email-templates.*', 'admin.onboarding-steps.*', 'admin.widgets.*', 'admin.announcements.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="settings"></i>
                    <span class="link-title">{{ __('Configuration') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.settings.*', 'admin.branding.*', 'admin.seo.*', 'admin.translations.*', 'admin.email-templates.*', 'admin.onboarding-steps.*', 'admin.widgets.*', 'admin.announcements.*') ? 'show' : '' }}" id="configMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        {{-- Paramètres masqué : tous les onglets redirigent vers Personnalisation --}}
                        {{-- Réactiver quand de vrais onglets seront ajoutés (mail, push, etc.) --}}
                        @if(Route::has('admin.branding.edit'))
                        @can('manage_branding')
                        <li class="nav-item"><a href="{{ route('admin.branding.edit') }}" class="nav-link {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}" {{ request()->routeIs('admin.branding.*') ? 'aria-current=page' : '' }}>{{ __('Personnalisation') }}</a></li>
                        @endcan
                        @endif
                        @can('manage_seo')
                        <li class="nav-item"><a href="{{ route('admin.seo.index') }}" class="nav-link {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}" {{ request()->routeIs('admin.seo.*') ? 'aria-current=page' : '' }}>{{ __('SEO') }}</a></li>
                        @endcan
                        @can('manage_translations')
                        <li class="nav-item"><a href="{{ route('admin.translations.index') }}" class="nav-link {{ request()->routeIs('admin.translations.*') ? 'active' : '' }}" {{ request()->routeIs('admin.translations.*') ? 'aria-current=page' : '' }}>{{ __('Traductions') }}</a></li>
                        @endcan
                        @if(Route::has('admin.email-templates.index'))
                        @can('manage_email_templates')
                        <li class="nav-item"><a href="{{ route('admin.email-templates.index') }}" class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}" {{ request()->routeIs('admin.email-templates.*') ? 'aria-current=page' : '' }}>{{ __('Emails templates') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.onboarding-steps.index'))
                        @can('manage_onboarding')
                        <li class="nav-item"><a href="{{ route('admin.onboarding-steps.index') }}" class="nav-link {{ request()->routeIs('admin.onboarding-steps.*') ? 'active' : '' }}" {{ request()->routeIs('admin.onboarding-steps.*') ? 'aria-current=page' : '' }}>{{ __('Onboarding') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.widgets.index'))
                        @can('manage_widgets')
                        <li class="nav-item"><a href="{{ route('admin.widgets.index') }}" class="nav-link {{ request()->routeIs('admin.widgets.*') ? 'active' : '' }}" {{ request()->routeIs('admin.widgets.*') ? 'aria-current=page' : '' }}>{{ __('Widgets') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.announcements.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" {{ request()->routeIs('admin.announcements.*') ? 'aria-current=page' : '' }}>{{ __('Annonces') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== AVANCÉ ===== --}}
            @canany(['manage_feature_flags', 'manage_system', 'manage_webhooks', 'manage_shortcodes', 'manage_short_urls', 'manage_cookies', 'manage_settings'])
            <li class="nav-item nav-category">{{ __('Avancé') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.feature-flags.*', 'admin.experiments.*', 'admin.plugins.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.short-urls.*', 'admin.cookie-categories.*', 'admin.custom-fields.*', 'admin.import.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#advancedMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.feature-flags.*', 'admin.experiments.*', 'admin.plugins.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.short-urls.*', 'admin.cookie-categories.*', 'admin.custom-fields.*', 'admin.import.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="code"></i>
                    <span class="link-title">{{ __('Avancé') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.feature-flags.*', 'admin.experiments.*', 'admin.plugins.*', 'admin.webhooks.*', 'admin.shortcodes.*', 'admin.short-urls.*', 'admin.cookie-categories.*', 'admin.custom-fields.*', 'admin.import.*') ? 'show' : '' }}" id="advancedMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @can('manage_feature_flags')
                        <li class="nav-item"><a href="{{ route('admin.feature-flags.index') }}" class="nav-link {{ request()->routeIs('admin.feature-flags.*') ? 'active' : '' }}" {{ request()->routeIs('admin.feature-flags.*') ? 'aria-current=page' : '' }}>{{ __('Feature Flags') }}</a></li>
                        @endcan
                        @can('manage_feature_flags')
                        <li class="nav-item"><a href="{{ route('admin.experiments.index') }}" class="nav-link {{ request()->routeIs('admin.experiments.*') ? 'active' : '' }}" {{ request()->routeIs('admin.experiments.*') ? 'aria-current=page' : '' }}>{{ __('Tests A/B') }}</a></li>
                        @endcan
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.plugins.index') }}" class="nav-link {{ request()->routeIs('admin.plugins.*') ? 'active' : '' }}" {{ request()->routeIs('admin.plugins.*') ? 'aria-current=page' : '' }}>{{ __('Plugins') }}</a></li>
                        @endcan
                        @if(Route::has('admin.webhooks.index'))
                        @can('manage_webhooks')
                        <li class="nav-item"><a href="{{ route('admin.webhooks.index') }}" class="nav-link {{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}" {{ request()->routeIs('admin.webhooks.*') ? 'aria-current=page' : '' }}>{{ __('Webhooks') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.shortcodes.index'))
                        @can('manage_shortcodes')
                        <li class="nav-item"><a href="{{ route('admin.shortcodes.index') }}" class="nav-link {{ request()->routeIs('admin.shortcodes.*') ? 'active' : '' }}" {{ request()->routeIs('admin.shortcodes.*') ? 'aria-current=page' : '' }}>{{ __('Shortcodes') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.short-urls.index'))
                        @can('manage_short_urls')
                        <li class="nav-item"><a href="{{ route('admin.short-urls.index') }}" class="nav-link {{ request()->routeIs('admin.short-urls.*') ? 'active' : '' }}" {{ request()->routeIs('admin.short-urls.*') ? 'aria-current=page' : '' }}>{{ __('Short URLs') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.cookie-categories.index'))
                        @can('manage_cookies')
                        <li class="nav-item"><a href="{{ route('admin.cookie-categories.index') }}" class="nav-link {{ request()->routeIs('admin.cookie-categories.*') ? 'active' : '' }}" {{ request()->routeIs('admin.cookie-categories.*') ? 'aria-current=page' : '' }}>{{ __('Cookies GDPR') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.custom-fields.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.custom-fields.index') }}" class="nav-link {{ request()->routeIs('admin.custom-fields.*') ? 'active' : '' }}" {{ request()->routeIs('admin.custom-fields.*') ? 'aria-current=page' : '' }}>{{ __('Champs perso.') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.import.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.import.index') }}" class="nav-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }}" {{ request()->routeIs('admin.import.*') ? 'aria-current=page' : '' }}>{{ __('Import') }}</a></li>
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
                        <li class="nav-item"><a href="{{ route('admin.security') }}" class="nav-link {{ request()->routeIs('admin.security') ? 'active' : '' }}" {{ request()->routeIs('admin.security') ? 'aria-current=page' : '' }}>{{ __('Tableau de bord') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.blocked-ips.index') }}" class="nav-link {{ request()->routeIs('admin.blocked-ips.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blocked-ips.*') ? 'aria-current=page' : '' }}>{{ __('IPs bloquées') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.login-history') }}" class="nav-link {{ request()->routeIs('admin.login-history') ? 'active' : '' }}" {{ request()->routeIs('admin.login-history') ? 'aria-current=page' : '' }}>{{ __('Connexions') }}</a></li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- ===== OUTILS ===== --}}
            @canany(['manage_backups', 'manage_activity_logs', 'view_logs', 'manage_trash', 'manage_notifications'])
            <li class="nav-item nav-category">{{ __('Outils') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.notifications.*', 'admin.push-notifications.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#toolsMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.notifications.*', 'admin.push-notifications.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="wrench"></i>
                    <span class="link-title">{{ __('Outils') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.backups.*', 'admin.activity-logs.*', 'admin.logs', 'admin.failed-jobs.*', 'admin.trash.*', 'admin.notifications.*', 'admin.push-notifications.*') ? 'show' : '' }}" id="toolsMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @can('manage_backups')
                        <li class="nav-item"><a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}" {{ request()->routeIs('admin.backups.*') ? 'aria-current=page' : '' }}>{{ __('Sauvegardes') }}</a></li>
                        @endcan
                        @can('manage_activity_logs')
                        <li class="nav-item"><a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}" {{ request()->routeIs('admin.activity-logs.*') ? 'aria-current=page' : '' }}>{{ __('Journaux d\'activité') }}</a></li>
                        @endcan
                        @can('view_logs')
                        <li class="nav-item"><a href="{{ route('admin.logs') }}" class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}" {{ request()->routeIs('admin.logs') ? 'aria-current=page' : '' }}>{{ __('Journaux app') }}</a></li>
                        @endcan
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.failed-jobs.index') }}" class="nav-link {{ request()->routeIs('admin.failed-jobs.*') ? 'active' : '' }}" {{ request()->routeIs('admin.failed-jobs.*') ? 'aria-current=page' : '' }}>{{ __('Jobs échoués') }}</a></li>
                        @endcan
                        @can('manage_trash')
                        <li class="nav-item"><a href="{{ route('admin.trash.index') }}" class="nav-link {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}" {{ request()->routeIs('admin.trash.*') ? 'aria-current=page' : '' }}>{{ __('Corbeille') }}</a></li>
                        @endcan
                        @can('manage_notifications')
                        <li class="nav-item"><a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" {{ request()->routeIs('admin.notifications.*') ? 'aria-current=page' : '' }}>{{ __('Notifications') }}</a></li>
                        @endcan
                        @if(Route::has('admin.push-notifications.index'))
                        @can('manage_notifications')
                        <li class="nav-item"><a href="{{ route('admin.push-notifications.index') }}" class="nav-link {{ request()->routeIs('admin.push-notifications.*') ? 'active' : '' }}" {{ request()->routeIs('admin.push-notifications.*') ? 'aria-current=page' : '' }}>{{ __('Push notifications') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== SYSTÈME ===== --}}
            @canany(['view_health', 'manage_system'])
            <li class="nav-item nav-category">{{ __('Système') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#systemMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="monitor"></i>
                    <span class="link-title">{{ __('Système') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.health', 'admin.scheduler', 'admin.mail-log', 'admin.cache', 'admin.system-info', 'admin.data-retention') ? 'show' : '' }}" id="systemMenu" data-bs-parent="#sidebarNav">
                    <ul class="nav sub-menu">
                        @can('view_health')
                        <li class="nav-item"><a href="{{ route('admin.health') }}" class="nav-link {{ request()->routeIs('admin.health') ? 'active' : '' }}" {{ request()->routeIs('admin.health') ? 'aria-current=page' : '' }}>{{ __('Santé système') }}</a></li>
                        @endcan
                        @if(Route::has('admin.scheduler'))
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.scheduler') }}" class="nav-link {{ request()->routeIs('admin.scheduler') ? 'active' : '' }}" {{ request()->routeIs('admin.scheduler') ? 'aria-current=page' : '' }}>{{ __('Scheduler') }}</a></li>
                        @endcan
                        @endif
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.mail-log') }}" class="nav-link {{ request()->routeIs('admin.mail-log') ? 'active' : '' }}" {{ request()->routeIs('admin.mail-log') ? 'aria-current=page' : '' }}>{{ __('Emails envoyés') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.cache') }}" class="nav-link {{ request()->routeIs('admin.cache') ? 'active' : '' }}" {{ request()->routeIs('admin.cache') ? 'aria-current=page' : '' }}>{{ __('Cache') }}</a></li>
                        <li class="nav-item"><a href="{{ route('admin.system-info') }}" class="nav-link {{ request()->routeIs('admin.system-info') ? 'active' : '' }}" {{ request()->routeIs('admin.system-info') ? 'aria-current=page' : '' }}>{{ __('Infos système') }}</a></li>
                        @endcan
                        @if(Route::has('admin.data-retention'))
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.data-retention') }}" class="nav-link {{ request()->routeIs('admin.data-retention') ? 'active' : '' }}" {{ request()->routeIs('admin.data-retention') ? 'aria-current=page' : '' }}>{{ __('Rétention données') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

        </ul>
    </div>
</nav>
