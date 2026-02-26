<div class="navbar-header">
    <div class="row align-items-center justify-content-between">
        <div class="col-auto">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <button type="button" class="sidebar-toggle" aria-label="{{ __('Basculer le menu') }}">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon text-2xl non-active" aria-hidden="true"></iconify-icon>
                    <iconify-icon icon="iconoir:arrow-right" class="icon text-2xl active" aria-hidden="true"></iconify-icon>
                </button>
                <button type="button" class="sidebar-mobile-toggle" aria-label="{{ __('Ouvrir le menu') }}">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon" aria-hidden="true"></iconify-icon>
                </button>
                {{-- Recherche globale --}}
                @livewire('backoffice-global-search')
            </div>
        </div>
        <div class="col-auto">
            <div class="d-flex flex-wrap align-items-center gap-3">

                {{-- Dark mode toggle --}}
                <button type="button" data-theme-toggle class="w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"></button>

                {{-- Language dropdown --}}
                <div class="dropdown d-none d-sm-inline-block">
                    <button class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center" type="button" data-bs-toggle="dropdown">
                        <img src="{{ asset('assets/backoffice/wowdash/images/lang-flag.png') }}" alt="image" class="w-24 h-24 object-fit-cover rounded-circle">
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-sm">
                        <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-0">Choisir la langue</h6>
                            </div>
                        </div>
                        <div class="max-h-400-px overflow-y-auto scroll-sm pe-8">
                            <div class="form-check style-check d-flex align-items-center justify-content-between mb-16">
                                <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="lang-english">
                                    <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag1.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle text-success-main rounded-circle flex-shrink-0">
                                        <span class="text-md fw-semibold mb-0">English</span>
                                    </span>
                                </label>
                                <input class="form-check-input" type="radio" name="lang" id="lang-english">
                            </div>
                            <div class="form-check style-check d-flex align-items-center justify-content-between mb-16">
                                <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="lang-japan">
                                    <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag2.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle text-success-main rounded-circle flex-shrink-0">
                                        <span class="text-md fw-semibold mb-0">Japan</span>
                                    </span>
                                </label>
                                <input class="form-check-input" type="radio" name="lang" id="lang-japan">
                            </div>
                            <div class="form-check style-check d-flex align-items-center justify-content-between mb-16">
                                <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="lang-france">
                                    <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag3.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle text-success-main rounded-circle flex-shrink-0">
                                        <span class="text-md fw-semibold mb-0">France</span>
                                    </span>
                                </label>
                                <input class="form-check-input" type="radio" name="lang" id="lang-france">
                            </div>
                            <div class="form-check style-check d-flex align-items-center justify-content-between mb-16">
                                <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="lang-germany">
                                    <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag4.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle text-success-main rounded-circle flex-shrink-0">
                                        <span class="text-md fw-semibold mb-0">Germany</span>
                                    </span>
                                </label>
                                <input class="form-check-input" type="radio" name="lang" id="lang-germany">
                            </div>
                            <div class="form-check style-check d-flex align-items-center justify-content-between mb-16">
                                <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="lang-korea">
                                    <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag5.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle text-success-main rounded-circle flex-shrink-0">
                                        <span class="text-md fw-semibold mb-0">South Korea</span>
                                    </span>
                                </label>
                                <input class="form-check-input" type="radio" name="lang" id="lang-korea">
                            </div>
                            <div class="form-check style-check d-flex align-items-center justify-content-between">
                                <label class="form-check-label line-height-1 fw-medium text-secondary-light" for="lang-bangladesh">
                                    <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag6.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle text-success-main rounded-circle flex-shrink-0">
                                        <span class="text-md fw-semibold mb-0">Bangladesh</span>
                                    </span>
                                </label>
                                <input class="form-check-input" type="radio" name="lang" id="lang-bangladesh">
                            </div>
                        </div>
                    </div>
                </div><!-- Language dropdown end -->

                {{-- Email / Messages dropdown --}}
                <div class="dropdown">
                    <button class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center" type="button" data-bs-toggle="dropdown">
                        <iconify-icon icon="mage:email" class="text-primary-light text-xl"></iconify-icon>
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-lg p-0">
                        <div class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-0">Messages</h6>
                            </div>
                            <span class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center">03</span>
                        </div>
                        <div class="max-h-400-px overflow-y-auto scroll-sm pe-4">
                            <a href="javascript:void(0)" class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">
                                <div class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                    <span class="w-40-px h-40-px rounded-circle flex-shrink-0 position-relative">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/notification/profile-3.png') }}" alt="">
                                        <span class="w-8-px h-8-px bg-success-main rounded-circle position-absolute end-0 bottom-0"></span>
                                    </span>
                                    <div>
                                        <h6 class="text-md fw-semibold mb-4">Kathryn Murphy</h6>
                                        <p class="mb-0 text-sm text-secondary-light text-w-100-px">hey! there i'm...</p>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-sm text-secondary-light flex-shrink-0">12:30 PM</span>
                                    <span class="mt-4 text-xs text-base w-16-px h-16-px d-flex justify-content-center align-items-center bg-warning-main rounded-circle">8</span>
                                </div>
                            </a>
                            <a href="javascript:void(0)" class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between">
                                <div class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                    <span class="w-40-px h-40-px rounded-circle flex-shrink-0 position-relative">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/notification/profile-4.png') }}" alt="">
                                        <span class="w-8-px h-8-px bg-neutral-300 rounded-circle position-absolute end-0 bottom-0"></span>
                                    </span>
                                    <div>
                                        <h6 class="text-md fw-semibold mb-4">Annette Black</h6>
                                        <p class="mb-0 text-sm text-secondary-light text-w-100-px">hey! there i'm...</p>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-sm text-secondary-light flex-shrink-0">12:30 PM</span>
                                    <span class="mt-4 text-xs text-base w-16-px h-16-px d-flex justify-content-center align-items-center bg-warning-main rounded-circle">2</span>
                                </div>
                            </a>
                            <a href="javascript:void(0)" class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between bg-neutral-50">
                                <div class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                    <span class="w-40-px h-40-px rounded-circle flex-shrink-0 position-relative">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/notification/profile-5.png') }}" alt="">
                                        <span class="w-8-px h-8-px bg-success-main rounded-circle position-absolute end-0 bottom-0"></span>
                                    </span>
                                    <div>
                                        <h6 class="text-md fw-semibold mb-4">Ronald Richards</h6>
                                        <p class="mb-0 text-sm text-secondary-light text-w-100-px">hey! there i'm...</p>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-sm text-secondary-light flex-shrink-0">12:30 PM</span>
                                    <span class="mt-4 text-xs text-base w-16-px h-16-px d-flex justify-content-center align-items-center bg-neutral-400 rounded-circle">0</span>
                                </div>
                            </a>
                        </div>
                        <div class="text-center py-12 px-16">
                            <a href="javascript:void(0)" class="text-primary-600 fw-semibold text-md">Voir tous les messages</a>
                        </div>
                    </div>
                </div><!-- Message dropdown end -->

                {{-- Cloche notifications Livewire --}}
                @livewire('backoffice-notification-bell')

                {{-- Profile dropdown --}}
                <div class="dropdown">
                    <button class="d-flex justify-content-center align-items-center rounded-circle" type="button" data-bs-toggle="dropdown">
                        <span class="w-40-px h-40-px bg-primary-600 text-white rounded-circle d-flex justify-content-center align-items-center fw-semibold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </span>
                    </button>
                    <div class="dropdown-menu to-top dropdown-menu-sm">
                        <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="text-lg text-primary-light fw-semibold mb-2">{{ auth()->user()->name }}</h6>
                                <span class="text-secondary-light fw-medium text-sm">{{ auth()->user()->roles->first()?->name ?? 'Utilisateur' }}</span>
                            </div>
                            <button type="button" class="hover-text-danger" data-bs-dismiss="dropdown">
                                <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
                            </button>
                        </div>
                        <ul class="to-top-list">
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3" href="{{ route('admin.profile') }}">
                                    <iconify-icon icon="solar:user-linear" class="icon text-xl"></iconify-icon> Mon profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3" href="{{ route('admin.settings.index') }}">
                                    <iconify-icon icon="icon-park-outline:setting-two" class="icon text-xl"></iconify-icon> Paramètres
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-danger d-flex align-items-center gap-3 w-100 border-0 bg-transparent">
                                        <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div><!-- Profile dropdown end -->

            </div>
        </div>
    </div>
</div>
