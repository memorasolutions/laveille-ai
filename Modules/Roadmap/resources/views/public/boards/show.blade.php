@extends('roadmap::layouts.public')
@section('title', $board->name)

@section('content')
    <div class="mb-4">
        <h2>{{ $board->name }}</h2>
        @if($board->description)
            <p class="text-muted">{{ $board->description }}</p>
        @endif
    </div>

    <div class="row">
        {{-- Ideas list --}}
        <div class="col-md-8">
            <form method="GET" action="{{ route('roadmap.boards.show', $board) }}" class="d-flex gap-2 mb-4 align-items-center">
                <select name="status" class="form-select form-select-sm" style="width: auto;">
                    <option value="">{{ __('Tous les statuts') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Filtrer') }}</button>
            </form>

            @forelse($ideas as $idea)
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <div class="text-center">
                                <button class="btn btn-outline-primary vote-btn d-flex flex-column align-items-center px-3 py-2"
                                        data-idea-id="{{ $idea->id }}">
                                    <small>&#9650;</small>
                                    <span class="vote-count fw-bold">{{ $idea->vote_count }}</span>
                                </button>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $idea->title }}</h5>
                                <div class="mb-2">
                                    @if($idea->category)
                                        <span class="badge bg-secondary">{{ $idea->category }}</span>
                                    @endif
                                    <span class="badge" style="background-color: {{ $idea->status->color() }};">
                                        {{ $idea->status->label() }}
                                    </span>
                                </div>
                                <p class="card-text text-muted mb-2">{{ Str::limit($idea->description, 150) }}</p>
                                <small class="text-muted">
                                    {{ __('Par') }} {{ $idea->user->name ?? __('Anonyme') }} —
                                    {{ $idea->comments_count ?? 0 }} {{ __('commentaire(s)') }} —
                                    {{ $idea->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">{{ __('Aucune idée soumise pour le moment.') }}</div>
            @endforelse

            @if($ideas->hasPages())
                <div class="mt-4">{{ $ideas->withQueryString()->links() }}</div>
            @endif
        </div>

        {{-- Submit form --}}
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Soumettre une idée') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('roadmap.ideas.store', $board) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label">{{ __('Titre') }} *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }} *</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">{{ __('Catégorie') }}</label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category">
                                <option value="">{{ __('Sélectionner') }}</option>
                                <option value="feature" {{ old('category') == 'feature' ? 'selected' : '' }}>{{ __('Fonctionnalité') }}</option>
                                <option value="bug" {{ old('category') == 'bug' ? 'selected' : '' }}>{{ __('Bug') }}</option>
                                <option value="improvement" {{ old('category') == 'improvement' ? 'selected' : '' }}>{{ __('Amélioration') }}</option>
                                <option value="ux" {{ old('category') == 'ux' ? 'selected' : '' }}>{{ __('Expérience utilisateur') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('Soumettre') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.vote-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const ideaId = this.dataset.ideaId;
            const countEl = this.querySelector('.vote-count');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch('/roadmap/ideas/' + ideaId + '/vote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                countEl.textContent = data.vote_count;
                btn.classList.toggle('btn-primary', data.voted);
                btn.classList.toggle('btn-outline-primary', !data.voted);
            });
        });
    });
});
</script>
@endpush
