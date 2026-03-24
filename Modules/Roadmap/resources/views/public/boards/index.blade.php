<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('roadmap::layouts.public')
@section('title', __('Idées et votes'))

@section('content')
    <div class="mb-4">
        <h2>{{ __('Idées et votes') }}</h2>
        <p class="text-muted">{{ __('Proposez vos idées et votez pour vos priorités') }}</p>
    </div>

    <div class="row">
        @forelse($boards as $board)
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm" style="border-top: 3px solid {{ $board->color ?? '#6c757d' }};">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="{{ route('roadmap.boards.show', $board) }}" class="text-decoration-none">
                                {{ $board->name }}
                            </a>
                        </h5>
                        <p class="card-text text-muted">{{ Str::limit($board->description, 100) }}</p>
                        <span class="badge bg-secondary">{{ $board->ideas_count }} {{ __('idées') }}</span>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between">
                        <a href="{{ route('roadmap.boards.show', $board) }}" class="btn btn-sm btn-outline-primary">
                            {{ __('Voir le tableau') }}
                        </a>
                        <a href="{{ route('roadmap.boards.kanban', $board) }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('Vue Kanban') }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">{{ __('Aucun tableau public disponible.') }}</div>
            </div>
        @endforelse
    </div>
@endsection
