<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('roadmap::layouts.public')
@section('title', $board->name . ' - ' . __('Propositions') . ' - ' . config('app.name'))

@section('roadmap-content')
    <div style="margin-bottom:24px;">
        <a href="{{ route('roadmap.boards.index') }}" style="color:var(--c-primary);text-decoration:none;font-size:14px;">← {{ __('Toutes les propositions') }}</a>
        <h2 style="font-weight:700;color:var(--c-dark);margin:8px 0 6px;">{{ $board->name }}</h2>
        @if($board->description)
            <p style="color:var(--c-text-muted);margin:0;">{{ $board->description }}</p>
        @endif
    </div>

    {{-- Filtre statut --}}
    <form method="GET" action="{{ route('roadmap.boards.show', $board) }}" style="display:flex;gap:8px;margin-bottom:20px;align-items:center;">
        <select name="status" class="form-control" style="width:auto;border-radius:8px;height:36px;font-size:13px;">
            <option value="">{{ __('Tous les statuts') }}</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button type="submit" style="background:var(--c-primary);color:#fff;border:none;border-radius:8px;padding:6px 16px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('Filtrer') }}</button>
    </form>

    {{-- Liste des propositions --}}
    @forelse($ideas as $idea)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:14px;display:flex;gap:16px;align-items:flex-start;box-shadow:0 1px 3px rgba(0,0,0,0.04);" x-data="{ votes: {{ $idea->vote_count }}, voted: false }">
            {{-- Vote --}}
            <div style="text-align:center;min-width:56px;flex-shrink:0;">
                <div @click="fetch('/roadmap/ideas/{{ $idea->id }}/vote',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{votes=d.vote_count;voted=d.voted})"
                     style="background:var(--c-primary-light, #F0FAFB);border:1px solid var(--c-primary-badge, #DDF4F8);border-radius:12px;padding:10px 8px;cursor:pointer;transition:all .2s;"
                     :style="voted && 'background:var(--c-primary);border-color:var(--c-primary);color:#fff'"
                     role="button" tabindex="0" :aria-label="voted ? '{{ __('Retirer le vote') }}' : '{{ __('Soutenir cette proposition') }}'">
                    <div style="font-size:16px;line-height:1;">▲</div>
                    <div style="font-weight:700;font-size:16px;line-height:1.2;" x-text="votes"></div>
                </div>
            </div>

            {{-- Contenu --}}
            <div style="flex:1;min-width:0;">
                <h5 style="font-weight:700;color:var(--c-dark);margin:0 0 6px;font-size:16px;">{{ $idea->title }}</h5>
                <div style="display:flex;gap:6px;margin-bottom:8px;flex-wrap:wrap;">
                    @if($idea->category)
                        <span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">{{ $idea->category }}</span>
                    @endif
                    <span style="background:{{ $idea->status->color() }}20;color:{{ $idea->status->color() }};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">{{ $idea->status->label() }}</span>
                </div>
                <p style="color:var(--c-text-muted);margin:0 0 8px;font-size:14px;">{{ Str::limit($idea->description, 200) }}</p>
                <div style="font-size:12px;color:#6b7280;">
                    {{ __('Par') }} {{ $idea->user->name ?? __('Anonyme') }} — {{ $idea->comments_count ?? 0 }} {{ __('commentaire(s)') }} — {{ $idea->created_at->diffForHumans() }}
                </div>

                {{-- Modération inline --}}
                <div style="margin-top:10px;">
                    @include('core::components.admin-actions', ['item' => $idea, 'type' => 'ideas'])
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center;padding:60px 20px;background:#f8fafc;border-radius:16px;border:1px dashed #e2e8f0;">
            <div style="font-size:48px;margin-bottom:12px;">💡</div>
            <p style="color:var(--c-text-muted);">{{ __('Aucune proposition soumise pour le moment.') }}</p>
        </div>
    @endforelse

    @if($ideas->hasPages())
        <div style="margin-top:20px;">{{ $ideas->withQueryString()->links() }}</div>
    @endif

    {{-- Formulaire de soumission --}}
    @auth
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin-top:32px;">
        <h4 style="font-weight:700;color:var(--c-dark);margin:0 0 16px;">{{ __('Soumettre une proposition') }}</h4>
        <form method="POST" action="{{ route('roadmap.ideas.store', $board) }}">
            @csrf
            <div style="margin-bottom:12px;">
                <label style="font-size:13px;font-weight:600;">{{ __('Titre') }} *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" style="border-radius:8px;height:40px;" required>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:13px;font-weight:600;">{{ __('Description') }} *</label>
                <textarea name="description" class="form-control" rows="3" style="border-radius:8px;" required>{{ old('description') }}</textarea>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:13px;font-weight:600;">{{ __('Catégorie') }}</label>
                <select name="category" class="form-control" style="border-radius:8px;height:40px;">
                    <option value="">{{ __('Sélectionner') }}</option>
                    <option value="feature">{{ __('Fonctionnalité') }}</option>
                    <option value="improvement">{{ __('Amélioration') }}</option>
                    <option value="bug">{{ __('Bug') }}</option>
                    <option value="ux">{{ __('Expérience utilisateur') }}</option>
                </select>
            </div>
            <button type="submit" style="width:100%;background:var(--c-primary, #0B7285);color:#fff;border:none;border-radius:10px;padding:12px 24px;font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 4px 12px rgba(11,114,133,0.15);transition:transform .2s,background .2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.background='#096474'" onmouseout="this.style.transform='none';this.style.background='var(--c-primary, #0B7285)'">{{ __('Soumettre ma proposition') }}</button>
        </form>
    </div>
    @else
        <div style="text-align:center;padding:20px;margin-top:24px;">
            <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour soumettre une proposition.') }}' })"
                style="background:var(--c-primary);color:#fff;border:none;border-radius:8px;padding:10px 24px;font-weight:600;cursor:pointer;font-size:14px;">
                🔐 {{ __('Se connecter pour proposer') }}
            </button>
        </div>
    @endauth
@endsection
