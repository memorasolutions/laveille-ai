@extends('auth::layouts.user-frontend')

@section('title', $board->name . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    {{ $board->slug === 'bugs' ? '🐛' : '💡' }} {{ $board->name }}
</h2>
@if($board->description)
    <p style="color: #6B7280; margin: 0 0 20px;">{{ $board->description }}</p>
@endif

{{-- Formulaire de soumission --}}
<div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
    <h4 style="font-weight: 700; margin: 0 0 12px; font-size: 15px; color: var(--c-dark);">
        {{ $board->slug === 'bugs' ? '🐛' : '✍️' }} {{ __('Soumettre une proposition') }}
    </h4>
    <form method="POST" action="{{ route('roadmap.ideas.store', $board) }}">
        @csrf
        <input type="text" name="title" placeholder="{{ __('Titre de votre proposition') }}" required
            style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px; margin-bottom: 10px;"
            value="{{ old('title') }}">
        @error('title')<div style="color: #DC2626; font-size: 12px; margin: -6px 0 8px;">{{ $message }}</div>@enderror

        <textarea name="description" placeholder="{{ __('Décrivez votre idée en détail (optionnel)') }}" rows="3"
            style="width: 100%; border: 1px solid #D1D5DB; border-radius: 8px; padding: 10px 12px; font-size: 14px; resize: vertical; margin-bottom: 10px;">{{ old('description') }}</textarea>

        <a href="javascript:void(0)" onclick="this.closest('form').submit()"
            style="-webkit-appearance:none;text-decoration:none;display:inline-block;background:var(--c-primary, #0B7285);color:#fff;padding:10px 24px;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;">
            {{ __('Soumettre') }}
        </a>
    </form>
</div>

{{-- Liste des propositions --}}
@forelse($board->ideas as $idea)
<div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; display: flex !important; gap: 14px; align-items: flex-start !important;"
     x-data="{ votes: {{ $idea->votes_count }}, voted: false }">
    {{-- Vote --}}
    <div style="text-align: center; min-width: 50px; flex-shrink: 0;">
        <div @click="fetch('/roadmap/ideas/{{ $idea->id }}/vote', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{votes=d.vote_count;voted=d.voted}).catch(()=>{})"
             style="background: #F0FAFB; border: 1px solid #DDF4F8; border-radius: 10px; padding: 8px 6px; cursor: pointer; transition: all .2s;"
             :style="voted && 'background:var(--c-primary);border-color:var(--c-primary);color:#fff'"
             role="button" tabindex="0">
            <div style="font-size: 14px; line-height: 1;">▲</div>
            <div style="font-weight: 700; font-size: 15px; line-height: 1.2;" x-text="votes"></div>
        </div>
    </div>

    {{-- Contenu --}}
    <div style="flex: 1 !important; min-width: 0;">
        <h4 style="font-weight: 700; color: var(--c-dark); margin: 0 0 6px; font-size: 15px;">{{ $idea->title }}</h4>
        <div style="display: flex !important; gap: 6px; margin-bottom: 6px; flex-wrap: wrap !important;">
            <span style="background: {{ $idea->status->color() }}20; color: {{ $idea->status->color() }}; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">{{ $idea->status->label() }}</span>
            @if($idea->category)
                <span style="background: #F1F5F9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">{{ $idea->category }}</span>
            @endif
        </div>
        @if($idea->description)
            <p style="color: #6B7280; margin: 0 0 6px; font-size: 13px;">{{ Str::limit($idea->description, 200) }}</p>
        @endif
        <div style="font-size: 12px; color: #6B7280;">
            {{ __('Par') }} {{ $idea->user->name ?? __('Anonyme') }} — {{ $idea->created_at->diffForHumans() }}
        </div>
    </div>
</div>
@empty
<div style="text-align: center; padding: 40px 20px; color: #6B7280;">
    <div style="font-size: 3rem; margin-bottom: 12px;">{{ $board->slug === 'bugs' ? '🐛' : '💡' }}</div>
    <h4 style="color: var(--c-dark); margin: 0 0 8px;">{{ __('Aucune proposition') }}</h4>
    <p style="font-size: 14px;">{{ __('Soyez le premier à soumettre une idée !') }}</p>
</div>
@endforelse

@endsection
