{{-- Bottom tab bar mobile - visible uniquement < 992px --}}
<div class="admin-bottom-bar d-lg-none" id="bottomBar">
    <div class="d-flex h-100 align-items-stretch">
        <a href="{{ route('admin.dashboard') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" aria-label="{{ __('Tableau de bord') }}">
            <i data-lucide="home" class="mb-1"></i>
            <span class="bottom-bar-label">{{ __('Accueil') }}</span>
        </a>
        <a href="{{ Route::has('admin.blog.articles.index') ? route('admin.blog.articles.index') : '#' }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.blog.*', 'admin.pages.*', 'admin.media.*') ? 'active' : '' }}" aria-label="{{ __('Contenu') }}">
            <i data-lucide="file-text" class="mb-1"></i>
            <span class="bottom-bar-label">{{ __('Contenu') }}</span>
        </a>
        @if(Route::has('admin.ecommerce.dashboard') || Route::has('admin.plans.index'))
        <a href="{{ Route::has('admin.ecommerce.dashboard') ? route('admin.ecommerce.dashboard') : route('admin.plans.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.ecommerce.*', 'admin.plans.*') ? 'active' : '' }}" aria-label="{{ __('Ventes') }}">
            <i data-lucide="shopping-cart" class="mb-1"></i>
            <span class="bottom-bar-label">{{ __('Ventes') }}</span>
        </a>
        @else
        <a href="{{ route('admin.users.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" aria-label="{{ __('Utilisateurs') }}">
            <i data-lucide="users" class="mb-1"></i>
            <span class="bottom-bar-label">{{ __('Utilisateurs') }}</span>
        </a>
        @endif
        <a href="{{ route('admin.notifications.index') }}" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center text-decoration-none {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" aria-label="{{ __('Notifications') }}">
            <i data-lucide="bell" class="mb-1"></i>
            <span class="bottom-bar-label">{{ __('Notifs') }}</span>
        </a>
        <button type="button" class="bottom-bar-item flex-fill d-flex flex-column justify-content-center align-items-center border-0 bg-transparent sidebar-open-btn" aria-label="{{ __('Menu complet') }}">
            <i data-lucide="menu" class="mb-1"></i>
            <span class="bottom-bar-label">{{ __('Plus') }}</span>
        </button>
    </div>
</div>

<style>
.admin-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 64px;
    background: var(--bs-body-bg, #fff);
    border-top: 1px solid rgba(0,0,0,.1);
    z-index: 1050;
    padding-bottom: env(safe-area-inset-bottom);
    transition: transform .3s ease;
    box-shadow: 0 -2px 10px rgba(0,0,0,.05);
}
.admin-bottom-bar.bottom-bar-hidden { transform: translateY(100%); }
.bottom-bar-item {
    min-height: 48px;
    color: #6c757d;
    transition: color .2s;
    cursor: pointer;
}
.bottom-bar-item:hover, .bottom-bar-item:focus, .bottom-bar-item.active {
    color: var(--bs-primary, #5570f1);
}
.bottom-bar-item svg { width: 22px; height: 22px; stroke-width: 1.5; }
.bottom-bar-label { font-size: 10px; font-weight: 500; line-height: 1; }
@media (max-width: 991.98px) {
    body { padding-bottom: calc(64px + env(safe-area-inset-bottom)); }
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
        bar.classList.toggle('bottom-bar-hidden', st > last && st > 64);
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
