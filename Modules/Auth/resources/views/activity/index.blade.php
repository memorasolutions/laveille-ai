@extends('auth::layouts.app')

@section('title', __('Journal d\'activité'))

@section('content')

<div class="d-flex align-items-center gap-12 mb-20">
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary radius-8">
        <iconify-icon icon="solar:arrow-left-outline"></iconify-icon>
    </a>
    <h1 class="fw-semibold mb-0">{{ __('Journal d\'activité') }}</h1>
</div>

<div class="d-flex flex-column gap-12">
    @forelse($activities as $activity)
    <div class="card">
        <div class="card-body d-flex align-items-start gap-12">
            <div class="w-36-px h-36-px bg-primary-100 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                <iconify-icon icon="solar:list-cross-outline" class="text-primary-600"></iconify-icon>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-8 flex-wrap mb-4">
                    <span class="fw-semibold text-sm">{{ $activity->description }}</span>
                    <span class="badge text-sm fw-semibold px-10 py-4 radius-4 bg-neutral-focus text-neutral-main">
                        {{ $activity->log_name }}
                    </span>
                </div>
                <p class="text-xs text-secondary-light mb-0">{{ $activity->created_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body py-48 text-center text-secondary-light">
            <iconify-icon icon="solar:history-outline" class="text-5xl mb-12 d-block"></iconify-icon>
            <p class="mb-4">{{ __('Aucune activité enregistrée.') }}</p>
            <p class="text-xs mb-0">{{ __('Vos actions sur le compte apparaîtront ici.') }}</p>
        </div>
    </div>
    @endforelse
</div>

@if($activities->hasPages())
<div class="mt-20">
    {{ $activities->links() }}
</div>
@endif

@endsection
