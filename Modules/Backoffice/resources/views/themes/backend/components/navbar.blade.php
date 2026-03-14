<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="header">
    <div class="header-content">
        <nav class="navbar navbar-expand">
            <div class="navbar-collapse d-flex justify-content-between">
                <div class="header-left">
                    <div class="dashboard_bar">{{ $title ?? __('Tableau de bord') }}</div>
                    <!-- Livewire search -->
                    @livewire('backoffice-global-search')
                </div>
                <ul class="navbar-nav header-right">
                    <!-- Dark mode toggle -->
                    <li class="notification_dropdown">
                        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-center rounded-circle bg-light" style="width:2.8rem;height:2.8rem;" data-theme-toggle>
                            <i data-lucide="sun" class="text-primary icon-sm"></i>
                        </a>
                    </li>

                    <!-- Notifications bell (Livewire) -->
                    <li class="notification_dropdown">
                        @livewire('backoffice-notification-bell')
                    </li>

                    <!-- Profile dropdown (Bootstrap 5) -->
                    <li class="header-profile dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold small" style="width:40px;height:40px;">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                            </span>
                            <span class="small fw-medium d-none d-md-inline">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.profile') }}">
                                    <i data-lucide="user" class="icon-sm"></i> {{ __('Mon profil') }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.settings.index') }}">
                                    <i data-lucide="settings" class="icon-sm"></i> {{ __('Paramètres') }}
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                        <i data-lucide="power" class="icon-sm"></i> {{ __('Déconnexion') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>
