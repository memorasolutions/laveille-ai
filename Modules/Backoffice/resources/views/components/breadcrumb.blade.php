<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <h1 class="fw-semibold mb-0" style="font-size: 1rem;">{{ $title }}</h1>
    <nav aria-label="{{ __('Fil d\'Ariane') }}">
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg" aria-hidden="true"></iconify-icon>
                    {{ __('Tableau de bord') }}
                </a>
            </li>
            @if(!empty($subtitle))
                <li aria-hidden="true">-</li>
                <li class="fw-medium" aria-current="page">{{ $subtitle }}</li>
            @endif
        </ul>
    </nav>
</div>
