<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Bottom tab bar mobile - visible uniquement < 992px --}}
<nav class="admin-bottom-bar d-lg-none" id="bottomBar" aria-label="{{ __('Navigation mobile') }}">
    <div class="d-flex h-100 align-items-stretch">
        <a href="{{ route('admin.dashboard') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" aria-label="{{ __('Accueil') }}" {{ request()->routeIs('admin.dashboard') ? 'aria-current=page' : '' }}>
            <i data-lucide="home" class="mb-1" aria-hidden="true"></i>
            <span class="bottom-bar-label">{{ __('Accueil') }}</span>
        </a>
        <a href="{{ Route::has('admin.blog.articles.index') ? route('admin.blog.articles.index') : route('admin.pages.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.faqs.*') ? 'active' : '' }}" aria-label="{{ __('Contenu') }}" {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*', 'admin.faqs.*') ? 'aria-current=page' : '' }}>
            <i data-lucide="file-text" class="mb-1" aria-hidden="true"></i>
            <span class="bottom-bar-label">{{ __('Contenu') }}</span>
        </a>
        @can('view_users')
        <a href="{{ route('admin.users.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.users.*', 'admin.teams.*') ? 'active' : '' }}" aria-label="{{ __('Équipe') }}" {{ request()->routeIs('admin.users.*', 'admin.teams.*') ? 'aria-current=page' : '' }}>
            <i data-lucide="users" class="mb-1" aria-hidden="true"></i>
            <span class="bottom-bar-label">{{ __('Équipe') }}</span>
        </a>
        @else
        <a href="{{ route('admin.notifications.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" aria-label="{{ __('Notifications') }}" {{ request()->routeIs('admin.notifications.*') ? 'aria-current=page' : '' }}>
            <i data-lucide="bell" class="mb-1" aria-hidden="true"></i>
            <span class="bottom-bar-label">{{ __('Notifs') }}</span>
        </a>
        @endcan
        <a href="{{ Route::has('admin.branding.edit') ? route('admin.branding.edit') : route('admin.settings.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.branding.*', 'admin.settings.*', 'admin.seo.*', 'admin.translations.*') ? 'active' : '' }}" aria-label="{{ __('Config') }}" {{ request()->routeIs('admin.branding.*', 'admin.settings.*', 'admin.seo.*', 'admin.translations.*') ? 'aria-current=page' : '' }}>
            <i data-lucide="settings" class="mb-1" aria-hidden="true"></i>
            <span class="bottom-bar-label">{{ __('Config') }}</span>
        </a>
        <button type="button" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center border-0 bg-transparent sidebar-open-btn" aria-label="{{ __('Menu complet') }}">
            <i data-lucide="menu" class="mb-1" aria-hidden="true"></i>
            <span class="bottom-bar-label">{{ __('Plus') }}</span>
        </button>
    </div>
</nav>

<style>
.admin-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 56px;
    background: var(--bs-body-bg, #fff);
    border-top: 1px solid rgba(0,0,0,.1);
    z-index: 1050;
    padding-bottom: env(safe-area-inset-bottom);
    transition: transform .3s ease;
    box-shadow: 0 -2px 10px rgba(0,0,0,.05);
}
[data-bs-theme="dark"] .admin-bottom-bar {
    background: var(--bs-body-bg, #1a1d21);
    border-top-color: rgba(255,255,255,.1);
    box-shadow: 0 -2px 10px rgba(0,0,0,.3);
}
.admin-bottom-bar.bottom-bar-hidden { transform: translateY(100%); }
.bottom-bar-item {
    min-width: 48px;
    min-height: 48px;
    color: var(--bs-secondary-color, #6c757d);
    transition: color .2s;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}
.bottom-bar-item:hover, .bottom-bar-item:focus, .bottom-bar-item.active {
    color: var(--bs-primary, #6571ff);
}
.bottom-bar-item svg { width: 22px; height: 22px; stroke-width: 1.5; }
.bottom-bar-label { font-size: 10px; font-weight: 500; line-height: 1; }
@media (max-width: 991.98px) {
    body { padding-bottom: calc(56px + env(safe-area-inset-bottom)); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var bar = document.getElementById('bottomBar');
    if (!bar) return;
    var last = 0, threshold = 10;
    window.addEventListener('scroll', function() {
        var st = window.pageYOffset || document.documentElement.scrollTop;
        if (Math.abs(last - st) <= threshold) return;
        bar.classList.toggle('bottom-bar-hidden', st > last && st > 56);
        last = st <= 0 ? 0 : st;
    }, { passive: true });

    var openBtn = bar.querySelector('.sidebar-open-btn');
    if (openBtn) {
        openBtn.addEventListener('click', function() {
            document.body.classList.add('sidebar-open');
            var toggler = document.querySelector('.sidebar-toggler');
            if (toggler) toggler.click();
        });
    }
});
</script>
