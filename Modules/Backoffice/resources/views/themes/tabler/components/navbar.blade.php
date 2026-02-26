<header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-nav flex-row order-md-last">

            {{-- Dark mode toggle --}}
            <div class="nav-item me-2">
                <button class="nav-link px-0" id="dark-mode-toggle" title="Mode sombre">
                    <i class="ti ti-moon" id="dark-icon"></i>
                    <i class="ti ti-sun d-none" id="light-icon"></i>
                </button>
            </div>

            {{-- Search (Livewire) --}}
            <div class="nav-item me-2">
                @livewire('backoffice-global-search')
            </div>

            {{-- Notifications (Livewire) --}}
            <div class="nav-item me-2">
                @livewire('backoffice-notification-bell')
            </div>

            {{-- Profile dropdown --}}
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Profil">
                    @php $user = auth()->user(); @endphp
                    <span class="avatar avatar-sm rounded-circle" style="background-color: var(--tblr-primary, #206bc4); color: white;">
                        {{ strtoupper(substr($user->name ?? 'A', 0, 2)) }}
                    </span>
                    <div class="d-none d-xl-block ps-2">
                        <div>{{ $user->name ?? 'Admin' }}</div>
                        <div class="mt-1 small text-secondary">{{ $user->getRoleNames()->first() ?? 'User' }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('admin.profile') }}" class="dropdown-item">
                        <i class="ti ti-user me-2"></i> Mon profil
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="dropdown-item">
                        <i class="ti ti-settings me-2"></i> Paramètres
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="ti ti-logout me-2"></i> Déconnexion
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('dark-mode-toggle');
    const html = document.documentElement;
    const darkIcon = document.getElementById('dark-icon');
    const lightIcon = document.getElementById('light-icon');

    // Load saved preference
    const saved = localStorage.getItem('tablerTheme');
    if (saved === 'dark') {
        html.setAttribute('data-bs-theme', 'dark');
        darkIcon.classList.add('d-none');
        lightIcon.classList.remove('d-none');
    }

    if (toggle) {
        toggle.addEventListener('click', function() {
            const isDark = html.getAttribute('data-bs-theme') === 'dark';
            html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
            localStorage.setItem('tablerTheme', isDark ? 'light' : 'dark');
            darkIcon.classList.toggle('d-none');
            lightIcon.classList.toggle('d-none');
        });
    }
});
</script>
