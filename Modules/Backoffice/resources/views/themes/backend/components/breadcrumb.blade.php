<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="breadcrumb-area py-3 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="fw-semibold fs-5 mb-0">{{ $title ?? '' }}</h1>
        <nav aria-label="Fil d'Ariane">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-primary">{{ __('Accueil') }}</a></li>
                @if(!empty($subtitle))
                <li class="breadcrumb-item active" aria-current="page">{{ $subtitle }}</li>
                @endif
            </ol>
        </nav>
    </div>
</div>
