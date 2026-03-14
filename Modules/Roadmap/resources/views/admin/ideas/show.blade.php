@php use Modules\Roadmap\Enums\IdeaStatus; @endphp

@extends('backoffice::themes.backend.layouts.admin')

@section('title', $idea->title)

@section('content')
    @include('backoffice::themes.backend.components.breadcrumb', [
        'title' => $idea->title,
        'items' => [
            ['label' => 'Roadmap'],
            ['label' => __('Idées'), 'url' => route('admin.roadmap.ideas.index')],
            ['label' => __('Détail')],
        ],
    ])

    <div class="row">
        {{-- Main column --}}
        <div class="col-md-8">
            {{-- Idea details --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Détail de l\'idée') }}</h5>
                </div>
                <div class="card-body">
                    <h4>{{ $idea->title }}</h4>
                    <div class="mb-3">{!! nl2br(e($idea->description)) !!}</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            {{ __('Soumise par') }} <strong>{{ $idea->user->name ?? __('Anonyme') }}</strong>
                            {{ __('le') }} {{ $idea->created_at->format('d/m/Y') }}
                        </small>
                        <span class="badge bg-secondary">{{ $idea->board->name }}</span>
                    </div>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Commentaires') }} ({{ $idea->comment_count }})</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($idea->comments as $comment)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $comment->user->name ?? __('Anonyme') }}</strong>
                                        @if($comment->is_official)
                                            <span class="badge bg-primary ms-2">{{ __('Officiel') }}</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $comment->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                                <p class="mb-0">{{ $comment->content }}</p>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">
                                {{ __('Aucun commentaire.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Changelog --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Historique') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($idea->changelogs()->with('user')->latest()->get() as $log)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <small>
                                        <strong>{{ $log->user->name ?? __('Système') }}</strong> —
                                        {{ $log->field === 'status' ? __('Statut') : $log->field }}:
                                        {{ $log->old_value ?? '—' }} → {{ $log->new_value }}
                                        @if($log->note) <em class="text-muted">{{ $log->note }}</em> @endif
                                    </small>
                                    <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-3">
                                {{ __('Aucun historique.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Official comment form --}}
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.roadmap.ideas.official-comment', $idea) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="content" class="form-control" rows="3" placeholder="{{ __('Votre réponse officielle...') }}" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="message-square" class="me-1"></i> {{ __('Réponse officielle') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-md-4">
            {{-- Status --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Statut') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge fs-6" style="background-color: {{ $idea->status->color() }};">
                            {{ $idea->status->label() }}
                        </span>
                    </div>
                    <form action="{{ route('admin.roadmap.ideas.update-status', $idea) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <select name="status" class="form-select" required>
                                @foreach(IdeaStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ $idea->status === $status ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i data-lucide="refresh-cw" class="me-1"></i> {{ __('Changer') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Votes --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Votes') }}</h5>
                </div>
                <div class="card-body text-center">
                    <div class="display-4 fw-bold">{{ $idea->vote_count }}</div>
                    <div class="text-muted">{{ __('votes') }}</div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Actions') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roadmap.ideas.merge', $idea) }}" method="POST" class="mb-3">
                        @csrf
                        <label class="form-label">{{ __('Fusionner avec') }}</label>
                        <select name="target_id" class="form-select mb-2" required>
                            <option value="">{{ __('Sélectionner une idée') }}</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i data-lucide="git-merge" class="me-1"></i> {{ __('Fusionner') }}
                        </button>
                    </form>

                    <form action="{{ route('admin.roadmap.ideas.destroy', $idea) }}" method="POST" onsubmit="return confirm('{{ __('Supprimer cette idée ?') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i data-lucide="trash-2" class="me-1"></i> {{ __('Supprimer') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
