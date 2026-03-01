<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Journal d\'activité'))

@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary rounded-2">
        <i data-lucide="arrow-left"></i>
    </a>
    <h1 class="fw-semibold mb-0">{{ __('Journal d\'activité') }}</h1>
</div>

<div class="d-flex flex-column gap-2">
    @forelse($activities as $activity)
    <div class="card">
        <div class="card-body d-flex align-items-start gap-2">
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                <i data-lucide="list" class="text-primary"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="fw-semibold text-sm">{{ $activity->description }}</span>
                    <span class="badge fw-semibold bg-secondary bg-opacity-10 text-secondary rounded-1">
                        {{ $activity->log_name }}
                    </span>
                </div>
                <p class="small text-muted mb-0">{{ $activity->created_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body py-5 text-center text-muted">
            <i data-lucide="history" class="d-block mx-auto mb-2" style="width:48px;height:48px;"></i>
            <p class="mb-1">{{ __('Aucune activité enregistrée.') }}</p>
            <p class="small mb-0">{{ __('Vos actions sur le compte apparaîtront ici.') }}</p>
        </div>
    </div>
    @endforelse
</div>

@if($activities->hasPages())
<div class="mt-3">
    {{ $activities->links() }}
</div>
@endif

@endsection
