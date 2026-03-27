<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('roadmap::layouts.public')
@section('title', __('Propositions de la communauté') . ' - ' . config('app.name'))

@section('roadmap-content')
    <div style="margin-bottom:24px;">
        <h2 style="font-weight:700;color:var(--c-dark, #1A1D23);margin-bottom:8px;">{{ __('Propositions de la communauté') }}</h2>
        <p style="color:var(--c-text-muted, #6E7687);">{{ __('Proposez vos idées, votez pour vos priorités et contribuez à faire évoluer la plateforme.') }}</p>
    </div>

    @forelse($boards as $board)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin-bottom:16px;border-left:4px solid {{ $board->color ?? 'var(--c-primary)' }};box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div>
                    <h4 style="font-weight:700;color:var(--c-dark);margin:0 0 6px;">
                        <a href="{{ route('roadmap.boards.show', $board) }}" style="text-decoration:none;color:inherit;">{{ $board->name }}</a>
                    </h4>
                    @if($board->description)
                        <p style="color:var(--c-text-muted);margin:0;font-size:14px;">{{ Str::limit($board->description, 120) }}</p>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="background:var(--c-primary-badge, #DDF4F8);color:var(--c-primary, #0B7285);padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;">
                        {{ $board->ideas_count }} {{ __('propositions') }}
                    </span>
                    <a href="{{ route('roadmap.boards.show', $board) }}" style="background:var(--c-primary);color:#fff;padding:8px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">
                        {{ __('Voir') }}
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center;padding:60px 20px;background:#f8fafc;border-radius:16px;border:1px dashed #e2e8f0;">
            <div style="font-size:48px;margin-bottom:12px;">💡</div>
            <h4 style="font-weight:700;color:var(--c-dark);">{{ __('Aucune proposition pour le moment') }}</h4>
            <p style="color:var(--c-text-muted);">{{ __('Soyez le premier à proposer une idée !') }}</p>
        </div>
    @endforelse
@endsection
