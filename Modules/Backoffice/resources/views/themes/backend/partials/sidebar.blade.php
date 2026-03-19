<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<nav class="sidebar" aria-label="{{ __('Menu administration') }}" style="background:var(--sidebar-bg, #0c1427);">
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand" style="font-family:var(--topbar-font-family);font-size:var(--topbar-font-size);font-weight:var(--topbar-font-weight);letter-spacing:var(--topbar-letter-spacing);word-spacing:var(--topbar-word-spacing);text-transform:var(--topbar-text-transform);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $branding['site_name'] ?? config('app.name') }}
        </a>
        <button type="button" class="sidebar-toggler not-active" aria-label="{{ __('Basculer le menu') }}" aria-expanded="true" aria-controls="sidebarNav">
            <span></span><span></span><span></span>
        </button>
    </div>
    <button type="button" class="btn-close d-lg-none position-absolute top-0 end-0 m-3 sidebar-close" aria-label="{{ __('Fermer le menu') }}"></button>
    <div class="sidebar-body">
        <ul class="nav" id="sidebarNav">

            {{-- ===== 1. ACCUEIL ===== --}}
            <li class="nav-item nav-category">{{ __('Accueil') }}</li>
            @can('view_dashboard')
            <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="nav-link" {{ request()->routeIs('admin.dashboard') ? 'aria-current=page' : '' }}>
                    <i class="link-icon" data-lucide="home"></i>
                    <span class="link-title">{{ __('Tableau de bord') }}</span>
                </a>
            </li>
            @endcan
            @if(Route::has('admin.stats'))
            @can('view_dashboard')
            <li class="nav-item {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                <a href="{{ route('admin.stats') }}" class="nav-link" {{ request()->routeIs('admin.stats') ? 'aria-current=page' : '' }}>
                    <i class="link-icon" data-lucide="bar-chart-2"></i>
                    <span class="link-title">{{ __('Statistiques') }}</span>
                </a>
            </li>
            @endcan
            @endif

            {{-- ===== 2. CONTENU (Contenu + Marketing) ===== --}}
            @canany(['view_articles', 'view_pages', 'view_categories', 'view_media', 'view_faqs', 'view_menus', 'view_comments', 'view_testimonials', 'view_widgets', 'view_shortcodes', 'view_forms', 'view_newsletter', 'view_workflows'])
            <li class="nav-item nav-category">{{ __('Contenu') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.faqs.*', 'admin.menus.*', 'admin.testimonials.*', 'admin.widgets.*', 'admin.shortcodes.*', 'admin.custom-fields.*', 'admin.newsletter.*', 'admin.formbuilder.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#contentMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.faqs.*', 'admin.menus.*', 'admin.testimonials.*', 'admin.widgets.*', 'admin.shortcodes.*', 'admin.custom-fields.*', 'admin.newsletter.*', 'admin.formbuilder.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="file-text"></i>
                    <span class="link-title">{{ __('Contenu') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.faqs.*', 'admin.menus.*', 'admin.testimonials.*', 'admin.widgets.*', 'admin.shortcodes.*', 'admin.custom-fields.*', 'admin.newsletter.*', 'admin.formbuilder.*') ? 'show' : '' }}" id="contentMenu">
                    <ul class="nav sub-menu">
                        @if(Route::has('admin.blog.articles.index'))
                        @can('view_articles')
                        <li class="nav-item"><a href="{{ route('admin.blog.articles.index') }}" class="nav-link {{ request()->routeIs('admin.blog.articles.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.articles.*') ? 'aria-current=page' : '' }}>{{ __('Articles') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.pages.index'))
                        @can('view_pages')
                        <li class="nav-item"><a href="{{ route('admin.pages.index') }}" class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" {{ request()->routeIs('admin.pages.*') ? 'aria-current=page' : '' }}>{{ __('Pages') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.blog.categories.index'))
                        @can('view_categories')
                        <li class="nav-item"><a href="{{ route('admin.blog.categories.index') }}" class="nav-link {{ request()->routeIs('admin.blog.categories.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.categories.*') ? 'aria-current=page' : '' }}>{{ __('Catégories') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.faqs.index'))
                        @can('view_faqs')
                        <li class="nav-item"><a href="{{ route('admin.faqs.index') }}" class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}" {{ request()->routeIs('admin.faqs.*') ? 'aria-current=page' : '' }}>{{ __('FAQ') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.media.index'))
                        @can('view_media')
                        <li class="nav-item"><a href="{{ route('admin.media.index') }}" class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}" {{ request()->routeIs('admin.media.*') ? 'aria-current=page' : '' }}>{{ __('Médias') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.menus.index'))
                        @can('view_menus')
                        <li class="nav-item"><a href="{{ route('admin.menus.index') }}" class="nav-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}" {{ request()->routeIs('admin.menus.*') ? 'aria-current=page' : '' }}>{{ __('Menus') }}</a></li>
                        @endcan
                        @endif
                        {{-- Plus... (Contenu) --}}
                        <li class="nav-item">
                            <a class="nav-link text-muted" data-bs-toggle="collapse" href="#contentMore" role="button" aria-expanded="false">
                                <i data-lucide="more-horizontal" style="width:14px;height:14px;"></i> {{ __('Plus...') }}
                            </a>
                            <div class="collapse" id="contentMore">
                                <ul class="nav sub-menu">
                                    @if(Route::has('admin.blog.tags.index'))
                                    @can('view_articles')
                                    <li class="nav-item"><a href="{{ route('admin.blog.tags.index') }}" class="nav-link {{ request()->routeIs('admin.blog.tags.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.tags.*') ? 'aria-current=page' : '' }}>{{ __('Tags') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.blog.comments.index'))
                                    @can('view_comments')
                                    <li class="nav-item"><a href="{{ route('admin.blog.comments.index') }}" class="nav-link {{ request()->routeIs('admin.blog.comments.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blog.comments.*') ? 'aria-current=page' : '' }}>{{ __('Commentaires') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.testimonials.index'))
                                    @can('view_testimonials')
                                    <li class="nav-item"><a href="{{ route('admin.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}" {{ request()->routeIs('admin.testimonials.*') ? 'aria-current=page' : '' }}>{{ __('Témoignages') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.widgets.index'))
                                    @can('view_widgets')
                                    <li class="nav-item"><a href="{{ route('admin.widgets.index') }}" class="nav-link {{ request()->routeIs('admin.widgets.*') ? 'active' : '' }}" {{ request()->routeIs('admin.widgets.*') ? 'aria-current=page' : '' }}>{{ __('Widgets') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.shortcodes.index'))
                                    @can('view_shortcodes')
                                    <li class="nav-item"><a href="{{ route('admin.shortcodes.index') }}" class="nav-link {{ request()->routeIs('admin.shortcodes.*') ? 'active' : '' }}" {{ request()->routeIs('admin.shortcodes.*') ? 'aria-current=page' : '' }}>{{ __('Shortcodes') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.custom-fields.index'))
                                    @can('view_settings')
                                    <li class="nav-item"><a href="{{ route('admin.custom-fields.index') }}" class="nav-link {{ request()->routeIs('admin.custom-fields.*') ? 'active' : '' }}" {{ request()->routeIs('admin.custom-fields.*') ? 'aria-current=page' : '' }}>{{ __('Champs personnalisés') }}</a></li>
                                    @endcan
                                    @endif
                                    {{-- Marketing --}}
                                    @if(Route::has('admin.newsletter.index'))
                                    @can('view_newsletter')
                                    <li class="nav-item"><a href="{{ route('admin.newsletter.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.index') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.index') ? 'aria-current=page' : '' }}>{{ __('Newsletter') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.newsletter.campaigns.index'))
                                    @can('view_campaigns')
                                    <li class="nav-item"><a href="{{ route('admin.newsletter.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.campaigns.*') ? 'aria-current=page' : '' }}>{{ __('Campagnes') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.newsletter.workflows.index'))
                                    @can('view_workflows')
                                    <li class="nav-item"><a href="{{ route('admin.newsletter.workflows.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.workflows.*') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.workflows.*') ? 'aria-current=page' : '' }}>{{ __('Workflows') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.newsletter.templates.index'))
                                    @can('view_newsletter')
                                    <li class="nav-item"><a href="{{ route('admin.newsletter.templates.index') }}" class="nav-link {{ request()->routeIs('admin.newsletter.templates.*') ? 'active' : '' }}" {{ request()->routeIs('admin.newsletter.templates.*') ? 'aria-current=page' : '' }}>{{ __('Templates') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.newsletter.index'))
                                    @can('view_newsletter')
                                    <li class="nav-item"><a href="{{ route('admin.newsletter.index') }}?tab=subscribers" class="nav-link {{ request()->is('*/newsletter*') && request()->get('tab') === 'subscribers' ? 'active' : '' }}">{{ __('Abonnés') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.formbuilder.forms.index'))
                                    @can('view_forms')
                                    <li class="nav-item"><a href="{{ route('admin.formbuilder.forms.index') }}" class="nav-link {{ request()->routeIs('admin.formbuilder.*') ? 'active' : '' }}" {{ request()->routeIs('admin.formbuilder.*') ? 'aria-current=page' : '' }}>{{ __('Formulaires') }}</a></li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== 3. VENTES (Boutique + SaaS + Réservations) ===== --}}
            @php
                $salesModuleActive = class_exists(\Nwidart\Modules\Facades\Module::class) && (
                    (\Nwidart\Modules\Facades\Module::has('Ecommerce') && \Nwidart\Modules\Facades\Module::isEnabled('Ecommerce')) ||
                    (\Nwidart\Modules\Facades\Module::has('SaaS') && \Nwidart\Modules\Facades\Module::isEnabled('SaaS')) ||
                    (\Nwidart\Modules\Facades\Module::has('Booking') && \Nwidart\Modules\Facades\Module::isEnabled('Booking'))
                );
            @endphp
            @if($salesModuleActive)
            @canany(['view_ecommerce', 'view_products', 'view_ecommerce_orders', 'view_coupons', 'view_plans', 'view_tenants', 'view_onboarding', 'view_booking', 'manage_booking'])
            <li class="nav-item nav-category">{{ __('Ventes') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.ecommerce.*', 'admin.plans.*', 'admin.revenue', 'admin.tenants.*', 'admin.onboarding-steps.*', 'admin.booking.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#salesMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.ecommerce.*', 'admin.plans.*', 'admin.revenue', 'admin.tenants.*', 'admin.onboarding-steps.*', 'admin.booking.*') ? 'true' : 'false' }}" aria-controls="salesMenu">
                    <i class="link-icon" data-lucide="shopping-cart"></i>
                    <span class="link-title">{{ __('Ventes') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.ecommerce.*', 'admin.plans.*', 'admin.revenue', 'admin.tenants.*', 'admin.onboarding-steps.*', 'admin.booking.*') ? 'show' : '' }}" id="salesMenu">
                    <ul class="nav sub-menu">
                        {{-- Boutique --}}
                        @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::has('Ecommerce') && \Nwidart\Modules\Facades\Module::isEnabled('Ecommerce'))
                        @if(Route::has('admin.ecommerce.dashboard'))
                        @can('view_ecommerce')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.dashboard') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.dashboard') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.dashboard') ? 'aria-current=page' : '' }}>{{ __('Tableau de bord') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.products.index'))
                        @can('view_products')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.products.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.products.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.products.*') ? 'aria-current=page' : '' }}>{{ __('Produits') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.categories.index'))
                        @can('view_products')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.categories.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.categories.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.categories.*') ? 'aria-current=page' : '' }}>{{ __('Catégories') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.orders.index'))
                        @can('view_ecommerce_orders')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.orders.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.orders.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.orders.*') ? 'aria-current=page' : '' }}>{{ __('Commandes') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.coupons.index'))
                        @can('view_coupons')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.coupons.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.coupons.*') ? 'aria-current=page' : '' }}>{{ __('Coupons') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.promotions.index'))
                        @can('view_coupons')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.promotions.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.promotions.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.promotions.*') ? 'aria-current=page' : '' }}>{{ __('Promotions') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.reviews.index'))
                        @can('view_ecommerce')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.reviews.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.reviews.*') ? 'aria-current=page' : '' }}>{{ __('Avis') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.refunds.index'))
                        @can('view_ecommerce_orders')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.refunds.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.refunds.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.refunds.*') ? 'aria-current=page' : '' }}>{{ __('Remboursements') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.import-export.index'))
                        @can('view_products')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.import-export.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.import-export.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.import-export.*') ? 'aria-current=page' : '' }}>{{ __('Import/Export') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.shipping-zones.index'))
                        @can('manage_settings')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.shipping-zones.index') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.shipping-zones.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.shipping-zones.*') ? 'aria-current=page' : '' }}>{{ __('Livraison') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ecommerce.analytics'))
                        @can('view_ecommerce')
                        <li class="nav-item"><a href="{{ route('admin.ecommerce.analytics') }}" class="nav-link {{ request()->routeIs('admin.ecommerce.analytics') ? 'active' : '' }}" {{ request()->routeIs('admin.ecommerce.analytics') ? 'aria-current=page' : '' }}>{{ __('Analytique') }}</a></li>
                        @endcan
                        @endif
                        @endif
                        {{-- SaaS --}}
                        @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::has('SaaS') && \Nwidart\Modules\Facades\Module::isEnabled('SaaS'))
                        @if(Route::has('admin.plans.index'))
                        @can('view_plans')
                        <li class="nav-item"><a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" {{ request()->routeIs('admin.plans.*') ? 'aria-current=page' : '' }}>{{ __('Plans') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.revenue'))
                        @can('view_plans')
                        <li class="nav-item"><a href="{{ route('admin.revenue') }}" class="nav-link {{ request()->routeIs('admin.revenue') ? 'active' : '' }}" {{ request()->routeIs('admin.revenue') ? 'aria-current=page' : '' }}>{{ __('Revenus') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.tenants.index'))
                        @can('view_tenants')
                        <li class="nav-item"><a href="{{ route('admin.tenants.index') }}" class="nav-link {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}" {{ request()->routeIs('admin.tenants.*') ? 'aria-current=page' : '' }}>{{ __('Tenants') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.onboarding-steps.index'))
                        @can('view_onboarding')
                        <li class="nav-item"><a href="{{ route('admin.onboarding-steps.index') }}" class="nav-link {{ request()->routeIs('admin.onboarding-steps.*') ? 'active' : '' }}" {{ request()->routeIs('admin.onboarding-steps.*') ? 'aria-current=page' : '' }}>{{ __('Onboarding') }}</a></li>
                        @endcan
                        @endif
                        @endif
                        {{-- Réservations --}}
                        @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::has('Booking') && \Nwidart\Modules\Facades\Module::isEnabled('Booking'))
                        @if(Route::has('admin.booking.dashboard'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.dashboard') }}" class="nav-link {{ request()->routeIs('admin.booking.dashboard') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.dashboard') ? 'aria-current=page' : '' }}>{{ __('Réservations') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.appointments.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.appointments.index') }}" class="nav-link {{ request()->routeIs('admin.booking.appointments.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.appointments.*') ? 'aria-current=page' : '' }}>{{ __('Rendez-vous') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.calendar.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.calendar.index') }}" class="nav-link {{ request()->routeIs('admin.booking.calendar.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.calendar.*') ? 'aria-current=page' : '' }}>{{ __('Calendrier') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.services.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.services.index') }}" class="nav-link {{ request()->routeIs('admin.booking.services.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.services.*') ? 'aria-current=page' : '' }}>{{ __('Services') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.packages.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.packages.index') }}" class="nav-link {{ request()->routeIs('admin.booking.packages.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.packages.*') ? 'aria-current=page' : '' }}>{{ __('Forfaits') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.customers.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.customers.index') }}" class="nav-link {{ request()->routeIs('admin.booking.customers.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.customers.*') ? 'aria-current=page' : '' }}>{{ __('Clients') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.settings.edit'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.settings.edit') }}" class="nav-link {{ request()->routeIs('admin.booking.settings.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.settings.*') ? 'aria-current=page' : '' }}>{{ __('Paramètres réservations') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.coupons.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.booking.coupons.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.coupons.*') ? 'aria-current=page' : '' }}>{{ __('Coupons réservations') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.gift-cards.index'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.gift-cards.index') }}" class="nav-link {{ request()->routeIs('admin.booking.gift-cards.*') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.gift-cards.*') ? 'aria-current=page' : '' }}>{{ __('Cartes-cadeaux') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.booking.analytics'))
                        @can('manage_booking')
                        <li class="nav-item"><a href="{{ route('admin.booking.analytics') }}" class="nav-link {{ request()->routeIs('admin.booking.analytics') ? 'active' : '' }}" {{ request()->routeIs('admin.booking.analytics') ? 'aria-current=page' : '' }}>{{ __('Analytique réservations') }}</a></li>
                        @endcan
                        @endif
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany
            @endif

            {{-- ===== 4. UTILISATEURS ===== --}}
            @canany(['view_users', 'view_roles', 'view_teams', 'view_contacts'])
            <li class="nav-item nav-category">{{ __('Utilisateurs') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.teams.*', 'admin.contact-messages.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#usersMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.teams.*', 'admin.contact-messages.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="users"></i>
                    <span class="link-title">{{ __('Utilisateurs') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.teams.*', 'admin.contact-messages.*') ? 'show' : '' }}" id="usersMenu">
                    <ul class="nav sub-menu">
                        @can('view_users')
                        <li class="nav-item"><a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" {{ request()->routeIs('admin.users.*') ? 'aria-current=page' : '' }}>{{ __('Membres') }}</a></li>
                        @endcan
                        @can('view_roles')
                        <li class="nav-item"><a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" {{ request()->routeIs('admin.roles.*') ? 'aria-current=page' : '' }}>{{ __('Rôles') }}</a></li>
                        @endcan
                        @if(Route::has('admin.teams.index'))
                        @can('view_teams')
                        <li class="nav-item"><a href="{{ route('admin.teams.index') }}" class="nav-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}" {{ request()->routeIs('admin.teams.*') ? 'aria-current=page' : '' }}>{{ __('Équipes') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.contact-messages.index'))
                        @can('view_contacts')
                        <li class="nav-item"><a href="{{ route('admin.contact-messages.index') }}" class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}" {{ request()->routeIs('admin.contact-messages.*') ? 'aria-current=page' : '' }}>{{ __('Messages') }}</a></li>
                        @endcan
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== PUBLICITÉS ===== --}}
            @if(Route::has('admin.ads.index'))
            <li class="nav-item {{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                <a href="{{ route('admin.ads.index') }}" class="nav-link" {{ request()->routeIs('admin.ads.*') ? 'aria-current=page' : '' }}>
                    <i class="link-icon" data-lucide="megaphone"></i>
                    <span class="link-title">{{ __('Publicités') }}</span>
                </a>
            </li>
            @endif

            {{-- ===== 5. CONFIGURATION ===== --}}
            @canany(['view_branding', 'view_seo', 'view_translations', 'view_feature_flags', 'view_email_templates', 'view_settings', 'view_themes'])
            <li class="nav-item nav-category">{{ __('Configuration') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.branding.*', 'admin.seo.*', 'admin.translations.*', 'admin.feature-flags.*', 'admin.email-templates.*', 'admin.announcements.*', 'admin.themes.*', 'admin.cookie-categories.*', 'admin.redirects.*', 'admin.settings.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#configMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.branding.*', 'admin.seo.*', 'admin.translations.*', 'admin.feature-flags.*', 'admin.email-templates.*', 'admin.announcements.*', 'admin.themes.*', 'admin.cookie-categories.*', 'admin.redirects.*', 'admin.settings.*') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="settings"></i>
                    <span class="link-title">{{ __('Configuration') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.branding.*', 'admin.seo.*', 'admin.translations.*', 'admin.feature-flags.*', 'admin.email-templates.*', 'admin.announcements.*', 'admin.themes.*', 'admin.cookie-categories.*', 'admin.redirects.*', 'admin.settings.*') ? 'show' : '' }}" id="configMenu">
                    <ul class="nav sub-menu">
                        @if(Route::has('admin.branding.edit'))
                        @can('view_branding')
                        <li class="nav-item"><a href="{{ route('admin.branding.edit') }}" class="nav-link {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}" {{ request()->routeIs('admin.branding.*') ? 'aria-current=page' : '' }}>{{ __('Branding') }}</a></li>
                        @endcan
                        @endif
                        @can('view_seo')
                        <li class="nav-item"><a href="{{ route('admin.seo.index') }}" class="nav-link {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}" {{ request()->routeIs('admin.seo.*') ? 'aria-current=page' : '' }}>{{ __('SEO') }}</a></li>
                        @endcan
                        @can('view_translations')
                        <li class="nav-item"><a href="{{ route('admin.translations.index') }}" class="nav-link {{ request()->routeIs('admin.translations.*') ? 'active' : '' }}" {{ request()->routeIs('admin.translations.*') ? 'aria-current=page' : '' }}>{{ __('Traductions') }}</a></li>
                        @endcan
                        @if(Route::has('admin.feature-flags.index'))
                        @can('view_feature_flags')
                        <li class="nav-item"><a href="{{ route('admin.feature-flags.index') }}" class="nav-link {{ request()->routeIs('admin.feature-flags.*') ? 'active' : '' }}" {{ request()->routeIs('admin.feature-flags.*') ? 'aria-current=page' : '' }}>{{ __('Feature Flags') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.email-templates.index'))
                        @can('view_email_templates')
                        <li class="nav-item"><a href="{{ route('admin.email-templates.index') }}" class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}" {{ request()->routeIs('admin.email-templates.*') ? 'aria-current=page' : '' }}>{{ __('Templates email') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.announcements.index'))
                        @can('view_settings')
                        <li class="nav-item"><a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" {{ request()->routeIs('admin.announcements.*') ? 'aria-current=page' : '' }}>{{ __('Annonces') }}</a></li>
                        @endcan
                        @endif
                        {{-- Plus... --}}
                        <li class="nav-item">
                            <a class="nav-link text-muted" data-bs-toggle="collapse" href="#configMore" role="button" aria-expanded="false">
                                <i data-lucide="more-horizontal" style="width:14px;height:14px;"></i> {{ __('Plus...') }}
                            </a>
                            <div class="collapse" id="configMore">
                                <ul class="nav sub-menu">
                                    @if(Route::has('admin.themes.index'))
                                    @can('view_themes')
                                    <li class="nav-item"><a href="{{ route('admin.themes.index') }}" class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}" {{ request()->routeIs('admin.themes.*') ? 'aria-current=page' : '' }}>{{ __('Thèmes') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.cookie-categories.index'))
                                    @can('view_cookies')
                                    <li class="nav-item"><a href="{{ route('admin.cookie-categories.index') }}" class="nav-link {{ request()->routeIs('admin.cookie-categories.*') ? 'active' : '' }}" {{ request()->routeIs('admin.cookie-categories.*') ? 'aria-current=page' : '' }}>{{ __('Catégories cookies') }}</a></li>
                                    @endcan
                                    @endif
                                    @if(Route::has('admin.redirects.index'))
                                    @can('view_seo')
                                    <li class="nav-item"><a href="{{ route('admin.redirects.index') }}" class="nav-link {{ request()->routeIs('admin.redirects.*') ? 'active' : '' }}" {{ request()->routeIs('admin.redirects.*') ? 'aria-current=page' : '' }}>{{ __('Redirections URL') }}</a></li>
                                    @endcan
                                    @endif
                                    @can('view_settings')
                                    <li class="nav-item"><a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" {{ request()->routeIs('admin.settings.*') ? 'aria-current=page' : '' }}>{{ __('Paramètres') }}</a></li>
                                    @endcan
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== 6. SYSTÈME (+ Recherche + Documentation) ===== --}}
            @canany(['view_health', 'view_security', 'view_backups', 'view_logs', 'manage_system', 'view_activity_logs', 'view_notifications', 'view_trash'])
            <li class="nav-item nav-category">{{ __('Système') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.health', 'admin.health.*', 'admin.security', 'admin.backups.*', 'admin.logs', 'admin.cache', 'admin.push-notifications.*', 'admin.scheduler', 'admin.scheduler.*', 'admin.failed-jobs.*', 'admin.login-history', 'admin.mail-log', 'admin.blocked-ips.*', 'admin.activity-logs.*', 'admin.trash.*', 'admin.data-retention', 'admin.system-info', 'admin.search', 'admin.documentation') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#systemMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.health', 'admin.health.*', 'admin.security', 'admin.backups.*', 'admin.logs', 'admin.cache', 'admin.push-notifications.*', 'admin.scheduler', 'admin.scheduler.*', 'admin.failed-jobs.*', 'admin.login-history', 'admin.mail-log', 'admin.blocked-ips.*', 'admin.activity-logs.*', 'admin.trash.*', 'admin.data-retention', 'admin.system-info', 'admin.search', 'admin.documentation') ? 'true' : 'false' }}">
                    <i class="link-icon" data-lucide="shield"></i>
                    <span class="link-title">{{ __('Système') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.health', 'admin.health.*', 'admin.security', 'admin.backups.*', 'admin.logs', 'admin.cache', 'admin.push-notifications.*', 'admin.scheduler', 'admin.scheduler.*', 'admin.failed-jobs.*', 'admin.login-history', 'admin.mail-log', 'admin.blocked-ips.*', 'admin.activity-logs.*', 'admin.trash.*', 'admin.data-retention', 'admin.system-info', 'admin.search', 'admin.documentation') ? 'show' : '' }}" id="systemMenu">
                    <ul class="nav sub-menu">
                        @can('view_health')
                        <li class="nav-item"><a href="{{ route('admin.health') }}" class="nav-link {{ request()->routeIs('admin.health') ? 'active' : '' }}" {{ request()->routeIs('admin.health') ? 'aria-current=page' : '' }}>{{ __('Santé système') }}</a></li>
                        @endcan
                        @can('view_security')
                        <li class="nav-item"><a href="{{ route('admin.security') }}" class="nav-link {{ request()->routeIs('admin.security') ? 'active' : '' }}" {{ request()->routeIs('admin.security') ? 'aria-current=page' : '' }}>{{ __('Sécurité') }}</a></li>
                        @endcan
                        @can('view_backups')
                        <li class="nav-item"><a href="{{ route('admin.backups.index') }}" class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}" {{ request()->routeIs('admin.backups.*') ? 'aria-current=page' : '' }}>{{ __('Sauvegardes') }}</a></li>
                        @endcan
                        @can('view_logs')
                        <li class="nav-item"><a href="{{ route('admin.logs') }}" class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}" {{ request()->routeIs('admin.logs') ? 'aria-current=page' : '' }}>{{ __('Journaux') }}</a></li>
                        @endcan
                        @can('manage_system')
                        <li class="nav-item"><a href="{{ route('admin.cache') }}" class="nav-link {{ request()->routeIs('admin.cache') ? 'active' : '' }}" {{ request()->routeIs('admin.cache') ? 'aria-current=page' : '' }}>{{ __('Cache') }}</a></li>
                        @endcan
                        {{-- Plus... --}}
                        <li class="nav-item">
                            <a class="nav-link text-muted" data-bs-toggle="collapse" href="#systemMore" role="button" aria-expanded="false">
                                <i data-lucide="more-horizontal" style="width:14px;height:14px;"></i> {{ __('Plus...') }}
                            </a>
                            <div class="collapse" id="systemMore">
                                <ul class="nav sub-menu">
                                    @if(Route::has('admin.push-notifications.index'))
                                    @can('view_notifications')
                                    <li class="nav-item"><a href="{{ route('admin.push-notifications.index') }}" class="nav-link {{ request()->routeIs('admin.push-notifications.*') ? 'active' : '' }}" {{ request()->routeIs('admin.push-notifications.*') ? 'aria-current=page' : '' }}>{{ __('Notifications push') }}</a></li>
                                    @endcan
                                    @endif
                                    @can('manage_system')
                                    @if(Route::has('admin.scheduler'))
                                    <li class="nav-item"><a href="{{ route('admin.scheduler') }}" class="nav-link {{ request()->routeIs('admin.scheduler') ? 'active' : '' }}" {{ request()->routeIs('admin.scheduler') ? 'aria-current=page' : '' }}>{{ __('Tâches planifiées') }}</a></li>
                                    @endif
                                    @if(Route::has('admin.failed-jobs.index'))
                                    <li class="nav-item"><a href="{{ route('admin.failed-jobs.index') }}" class="nav-link {{ request()->routeIs('admin.failed-jobs.*') ? 'active' : '' }}" {{ request()->routeIs('admin.failed-jobs.*') ? 'aria-current=page' : '' }}>{{ __('Jobs échoués') }}</a></li>
                                    @endif
                                    @endcan
                                    @can('view_security')
                                    <li class="nav-item"><a href="{{ route('admin.login-history') }}" class="nav-link {{ request()->routeIs('admin.login-history') ? 'active' : '' }}" {{ request()->routeIs('admin.login-history') ? 'aria-current=page' : '' }}>{{ __('Historique connexions') }}</a></li>
                                    @endcan
                                    @can('manage_system')
                                    <li class="nav-item"><a href="{{ route('admin.mail-log') }}" class="nav-link {{ request()->routeIs('admin.mail-log') ? 'active' : '' }}" {{ request()->routeIs('admin.mail-log') ? 'aria-current=page' : '' }}>{{ __('Courriels envoyés') }}</a></li>
                                    @endcan
                                    @can('view_security')
                                    <li class="nav-item"><a href="{{ route('admin.blocked-ips.index') }}" class="nav-link {{ request()->routeIs('admin.blocked-ips.*') ? 'active' : '' }}" {{ request()->routeIs('admin.blocked-ips.*') ? 'aria-current=page' : '' }}>{{ __('IP bloquées') }}</a></li>
                                    @endcan
                                    @can('view_activity_logs')
                                    <li class="nav-item"><a href="{{ route('admin.activity-logs.index') }}" class="nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}" {{ request()->routeIs('admin.activity-logs.*') ? 'aria-current=page' : '' }}>{{ __('Journaux activité') }}</a></li>
                                    @endcan
                                    @can('view_trash')
                                    <li class="nav-item"><a href="{{ route('admin.trash.index') }}" class="nav-link {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}" {{ request()->routeIs('admin.trash.*') ? 'aria-current=page' : '' }}>{{ __('Corbeille') }}</a></li>
                                    @endcan
                                    @can('manage_system')
                                    @if(Route::has('admin.data-retention'))
                                    <li class="nav-item"><a href="{{ route('admin.data-retention') }}" class="nav-link {{ request()->routeIs('admin.data-retention') ? 'active' : '' }}" {{ request()->routeIs('admin.data-retention') ? 'aria-current=page' : '' }}>{{ __('Rétention données') }}</a></li>
                                    @endif
                                    <li class="nav-item"><a href="{{ route('admin.system-info') }}" class="nav-link {{ request()->routeIs('admin.system-info') ? 'active' : '' }}" {{ request()->routeIs('admin.system-info') ? 'aria-current=page' : '' }}>{{ __('Infos système') }}</a></li>
                                    @endcan
                                    @can('view_notifications')
                                    <li class="nav-item"><a href="{{ route('admin.notifications.index') }}" class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" {{ request()->routeIs('admin.notifications.*') ? 'aria-current=page' : '' }}>{{ __('Notifications') }}</a></li>
                                    @endcan
                                    @if(Route::has('admin.webhooks.index'))
                                    @can('manage_webhooks')
                                    <li class="nav-item"><a href="{{ route('admin.webhooks.index') }}" class="nav-link {{ request()->routeIs('admin.webhooks.*') ? 'active' : '' }}" {{ request()->routeIs('admin.webhooks.*') ? 'aria-current=page' : '' }}>{{ __('Webhooks') }}</a></li>
                                    @endcan
                                    @endif
                                    {{-- Recherche (déplacé des liens autonomes) --}}
                                    @if(Route::has('admin.search'))
                                    @can('manage_system')
                                    <li class="nav-item"><a href="{{ route('admin.search') }}" class="nav-link {{ request()->routeIs('admin.search') ? 'active' : '' }}" {{ request()->routeIs('admin.search') ? 'aria-current=page' : '' }}>{{ __('Recherche') }}</a></li>
                                    @endcan
                                    @endif
                                    {{-- Documentation (déplacé des liens autonomes) --}}
                                    @if(Route::has('admin.documentation'))
                                    @can('view_documentation')
                                    <li class="nav-item"><a href="{{ route('admin.documentation') }}" class="nav-link {{ request()->routeIs('admin.documentation') ? 'active' : '' }}" {{ request()->routeIs('admin.documentation') ? 'aria-current=page' : '' }}>{{ __('Documentation') }}</a></li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </li>
            @endcanany

            {{-- ===== 7. OUTILS (Support IA + Roadmap) ===== --}}
            @php
                $toolsModuleActive = class_exists(\Nwidart\Modules\Facades\Module::class) && (
                    (\Nwidart\Modules\Facades\Module::has('AI') && \Nwidart\Modules\Facades\Module::isEnabled('AI')) ||
                    (\Nwidart\Modules\Facades\Module::has('Roadmap') && \Nwidart\Modules\Facades\Module::isEnabled('Roadmap'))
                );
            @endphp
            @if($toolsModuleActive)
            @canany(['view_ai', 'manage_ai', 'view_roadmap', 'manage_roadmap'])
            <li class="nav-item nav-category">{{ __('Outils') }}</li>
            <li class="nav-item {{ request()->routeIs('admin.ai.*', 'admin.roadmap.*') ? 'active' : '' }}">
                <a class="nav-link" data-bs-toggle="collapse" href="#toolsMenu" role="button"
                   aria-expanded="{{ request()->routeIs('admin.ai.*', 'admin.roadmap.*') ? 'true' : 'false' }}" aria-controls="toolsMenu">
                    <i class="link-icon" data-lucide="sparkles"></i>
                    <span class="link-title">{{ __('Outils') }}</span>
                    <i class="link-arrow" data-lucide="chevron-down"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.ai.*', 'admin.roadmap.*') ? 'show' : '' }}" id="toolsMenu">
                    <ul class="nav sub-menu">
                        {{-- Support IA --}}
                        @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::has('AI') && \Nwidart\Modules\Facades\Module::isEnabled('AI'))
                        @if(Route::has('admin.ai.conversations.index'))
                        @can('view_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.conversations.index') }}" class="nav-link {{ request()->routeIs('admin.ai.conversations.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.conversations.*') ? 'aria-current=page' : '' }}>{{ __('Conversations') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.knowledge.index'))
                        @can('view_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.knowledge.index') }}" class="nav-link {{ request()->routeIs('admin.ai.knowledge.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.knowledge.*') ? 'aria-current=page' : '' }}>{{ __('Base de connaissances') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.agent.index'))
                        @can('view_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.agent.index') }}" class="nav-link {{ request()->routeIs('admin.ai.agent.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.agent.*') ? 'aria-current=page' : '' }}>{{ __('Agent') }}
                            @php $waitingCount = class_exists(\Modules\AI\Models\AiConversation::class) ? \Modules\AI\Models\AiConversation::where('status', 'waiting_human')->count() : 0; @endphp
                            @if($waitingCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $waitingCount }}</span>@endif
                        </a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.analytics'))
                        @can('view_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.analytics') }}" class="nav-link {{ request()->routeIs('admin.ai.analytics') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.analytics') ? 'aria-current=page' : '' }}>{{ __('Analytics IA') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.urls.index'))
                        @can('view_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.urls.index') }}" class="nav-link {{ request()->routeIs('admin.ai.urls.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.urls.*') ? 'aria-current=page' : '' }}>{{ __('URLs') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.tickets.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.ai.tickets.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.tickets.*') ? 'aria-current=page' : '' }}>{{ __('Tickets') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.inbox.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.inbox.index') }}" class="nav-link {{ request()->routeIs('admin.ai.inbox.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.inbox.*') ? 'aria-current=page' : '' }}>{{ __('Boîte de réception') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.channels.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.channels.index') }}" class="nav-link {{ request()->routeIs('admin.ai.channels.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.channels.*') ? 'aria-current=page' : '' }}>{{ __('Canaux') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.canned-replies.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.canned-replies.index') }}" class="nav-link {{ request()->routeIs('admin.ai.canned-replies.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.canned-replies.*') ? 'aria-current=page' : '' }}>{{ __('Réponses rapides') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.sla.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.sla.index') }}" class="nav-link {{ request()->routeIs('admin.ai.sla.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.sla.*') ? 'aria-current=page' : '' }}>{{ __('SLA') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.ai.csat.index'))
                        @can('manage_ai')
                        <li class="nav-item"><a href="{{ route('admin.ai.csat.index') }}" class="nav-link {{ request()->routeIs('admin.ai.csat.*') ? 'active' : '' }}" {{ request()->routeIs('admin.ai.csat.*') ? 'aria-current=page' : '' }}>{{ __('Satisfaction (CSAT)') }}</a></li>
                        @endcan
                        @endif
                        @endif
                        {{-- Roadmap --}}
                        @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::has('Roadmap') && \Nwidart\Modules\Facades\Module::isEnabled('Roadmap'))
                        @if(Route::has('admin.roadmap.boards.index'))
                        @can('view_roadmap')
                        <li class="nav-item"><a href="{{ route('admin.roadmap.boards.index') }}" class="nav-link {{ request()->routeIs('admin.roadmap.boards.*') ? 'active' : '' }}" {{ request()->routeIs('admin.roadmap.boards.*') ? 'aria-current=page' : '' }}>{{ __('Tableaux') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.roadmap.ideas.index'))
                        @can('view_roadmap')
                        <li class="nav-item"><a href="{{ route('admin.roadmap.ideas.index') }}" class="nav-link {{ request()->routeIs('admin.roadmap.ideas.*') ? 'active' : '' }}" {{ request()->routeIs('admin.roadmap.ideas.*') ? 'aria-current=page' : '' }}>{{ __('Idées') }}</a></li>
                        @endcan
                        @endif
                        @if(Route::has('admin.roadmap.analytics'))
                        @can('manage_roadmap')
                        <li class="nav-item"><a href="{{ route('admin.roadmap.analytics') }}" class="nav-link {{ request()->routeIs('admin.roadmap.analytics') ? 'active' : '' }}" {{ request()->routeIs('admin.roadmap.analytics') ? 'aria-current=page' : '' }}>{{ __('Statistiques') }}</a></li>
                        @endcan
                        @endif
                        @endif
                    </ul>
                </div>
            </li>
            @endcanany
            @endif

        </ul>
    </div>
</nav>
