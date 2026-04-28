<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Idées et votes'))

@section('content')
    @include('backoffice::themes.backend.components.breadcrumb', [
        'title' => __('Idées et votes'),
        'items' => [
            ['label' => 'Idées et votes'],
            ['label' => __('Idées')],
        ],
    ])

    <div class="card">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.roadmap.ideas.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">{{ __('Statut') }}</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">{{ __('Tous les statuts') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="board_id" class="form-label">{{ __('Tableau') }}</label>
                    <select name="board_id" id="board_id" class="form-select form-select-sm">
                        <option value="">{{ __('Tous les tableaux') }}</option>
                        @foreach($boards as $board)
                            <option value="{{ $board->id }}" {{ ($filters['board_id'] ?? '') == $board->id ? 'selected' : '' }}>
                                {{ $board->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i data-lucide="filter" class="me-1"></i> {{ __('Filtrer') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Tableau') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Votes') }}</th>
                            <th>{{ __('Commentaires') }}</th>
                            <th>{{ __('Auteur') }}</th>
                            <th width="120">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ideas as $idea)
                            <tr>
                                <td>{{ $idea->id }}</td>
                                <td>
                                    <a href="{{ route('admin.roadmap.ideas.show', $idea) }}" class="text-decoration-none">
                                        {{ Str::limit($idea->title, 50) }}
                                    </a>
                                </td>
                                <td>{{ $idea->board->name ?? '—' }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $idea->status->color() }};">
                                        {{ $idea->status->label() }}
                                    </span>
                                </td>
                                <td>{{ $idea->vote_count }}</td>
                                <td>{{ $idea->comment_count }}</td>
                                <td>{{ $idea->user->name ?? __('Anonyme') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.roadmap.ideas.show', $idea) }}" class="btn btn-outline-info" title="{{ __('Voir') }}">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <form action="{{ route('admin.roadmap.ideas.destroy', $idea) }}" method="POST" class="d-inline" data-confirm="{{ __('Supprimer cette idée ?') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="{{ __('Supprimer') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    {{ __('Aucune idée trouvée.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($ideas->hasPages())
            <div class="card-footer">
                {{ $ideas->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
