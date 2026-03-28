<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('roadmap::layouts.public')
@section('title', __('Propositions de la communauté') . ' - ' . config('app.name'))

@section('roadmap-content')
    <div style="text-align:center;margin-bottom:32px;">
        <h1 style="font-family:var(--f-heading);font-weight:800;font-size:2rem;color:var(--c-dark);margin-bottom:8px;">💡 {{ __('Propositions de la communauté') }}</h1>
        <p style="color:#6b7280;font-size:1.1rem;">{{ __('Proposez vos idées, votez pour vos priorités et contribuez à faire évoluer la plateforme.') }}</p>
    </div>

    {{-- CTA soumettre --}}
    <div style="text-align:center;margin-bottom:28px;">
        @auth
        <a href="{{ route('roadmap.boards.show', $boards->first()) }}"
            style="display:inline-block;background:var(--c-primary, #0B7285);color:#fff;padding:12px 28px;border-radius:10px;font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-weight:700;font-size:15px;text-decoration:none;transition:background .2s;"
            onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
            ✍️ {{ __('Soumettre ma proposition') }}
        </a>
        @else
        <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour soumettre une proposition.') }}' })"
            style="background:var(--c-primary, #0B7285);color:#fff;border:none;padding:12px 28px;border-radius:10px;font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-weight:700;font-size:15px;cursor:pointer;transition:background .2s;"
            onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
            🔐 {{ __('Se connecter pour proposer') }}
        </button>
        @endauth
    </div>

    <div class="row">
    @forelse($boards as $board)
        <div class="{{ $boards->count() === 1 ? 'col-md-8 col-md-offset-2' : 'col-md-6' }} col-sm-12" style="margin-bottom:20px;">
            <a href="{{ route('roadmap.boards.show', $board) }}" style="display:block!important;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;text-decoration:none!important;color:inherit;transition:transform .2s,box-shadow .2s;box-shadow:0 2px 8px rgba(0,0,0,0.04);height:100%;"
               onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'">
                <div style="display:flex!important;align-items:flex-start!important;gap:16px;">
                    <div style="width:48px;height:48px;border-radius:12px;background:{{ $board->color ?? 'var(--c-primary)' }}15;display:flex!important;align-items:center!important;justify-content:center!important;font-size:24px;flex-shrink:0;">💡</div>
                    <div style="flex:1;">
                        <h3 style="font-family:var(--f-heading);font-weight:700;font-size:1.15rem;color:var(--c-dark);margin:0 0 8px;">{{ $board->name }}</h3>
                        @if($board->description)
                            <p style="color:#6b7280;margin:0 0 12px;font-size:14px;line-height:1.5;">{{ Str::limit($board->description, 150) }}</p>
                        @endif
                        <div style="display:flex!important;align-items:center!important;gap:10px;">
                            <span style="background:var(--c-primary-badge, #ddf4f8);color:var(--c-primary);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;">{{ $board->ideas_count }} {{ __('propositions') }}</span>
                            <span style="color:var(--c-primary);font-weight:600;font-size:13px;">{{ __('Voir') }} →</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @empty
        <div style="text-align:center;padding:60px 20px;background:#f8fafc;border-radius:16px;border:1px dashed #e2e8f0;">
            <div style="font-size:48px;margin-bottom:12px;">💡</div>
            <h4 style="font-weight:700;color:var(--c-dark);">{{ __('Aucune proposition pour le moment') }}</h4>
            <p style="color:var(--c-text-muted);">{{ __('Soyez le premier à proposer une idée !') }}</p>
        </div>
    @endforelse
    </div>

    {{-- Lien board bugs (auth only) --}}
    @auth
    @if(Route::has('roadmap.boards.show'))
    @php $bugBoard = \Modules\Roadmap\Models\Board::where('slug', 'bugs')->first(); @endphp
    @if($bugBoard)
    <div style="text-align:center;margin-top:24px;padding:20px;background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;">
        <a href="{{ route('roadmap.boards.show', $bugBoard) }}" style="color:#DC2626;font-weight:700;font-size:14px;text-decoration:none;">
            🐛 {{ __('Signaler un bug') }} →
        </a>
    </div>
    @endif
    @endif
    @endauth
@endsection
