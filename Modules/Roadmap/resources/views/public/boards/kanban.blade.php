<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('roadmap::layouts.public')
@section('title', $board->name . ' — Kanban')

@section('roadmap-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ $board->name }} — Kanban</h2>
        <a href="{{ route('roadmap.boards.show', $board) }}" class="btn btn-outline-primary btn-sm">
            {{ __('Vue liste') }}
        </a>
    </div>

    <div class="d-flex overflow-auto gap-3 pb-3" style="min-height: 60vh;">
        @foreach($statuses as $status)
            @php $ideas = $columns[$status->value] ?? collect(); @endphp
            <div class="flex-shrink-0" style="min-width: 280px; max-width: 300px;">
                <div class="card h-100">
                    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: {{ $status->color() }};">
                        <span>{{ $status->label() }}</span>
                        <span class="badge bg-light text-dark">{{ count($ideas) }}</span>
                    </div>
                    <div class="card-body p-2" style="max-height: 70vh; overflow-y: auto;">
                        @forelse($ideas as $idea)
                            <div class="card mb-2 shadow-sm">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="small">{{ $idea->title }}</strong>
                                        <span class="badge bg-primary">{{ $idea->vote_count }}</span>
                                    </div>
                                    <p class="small text-muted mb-1">{{ Str::limit($idea->description, 80) }}</p>
                                    <small class="text-muted">{{ $idea->user->name ?? __('Anonyme') }}</small>
                                </div>
                            </div>
                        @empty
                            <p class="small text-muted text-center mb-0">{{ __('Aucune idée') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
