<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('user-content')

{{-- Header --}}
<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 12px; margin-bottom: 24px;">
    <div>
        <h2 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark, #1A1D23); margin: 0 0 4px;">
            🔗 {{ __('Mes liens courts') }}
        </h2>
        <span style="font-size: 13px; color: var(--c-text-muted, #6E7687);">{{ $shortUrls->total() }} {{ __('lien(s) au total') }}</span>
    </div>
    <a href="{{ route('shorturl.create') }}"
        style="background: var(--c-primary, #0B7285); color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; transition: background .2s;"
        onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
        + {{ __('Nouveau lien') }}
    </a>
</div>

{{-- Message expiration --}}
<div style="background: #F0FAFB; border: 1px solid #D5EDF0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #475569; line-height: 1.5;">
    💡 {{ __('Vos liens raccourcis expirent automatiquement après 12 mois sans visite. Vous pouvez repousser la date d\'expiration de chaque lien à tout moment depuis cette page.') }}
</div>

@if($shortUrls->isEmpty())
    {{-- État vide --}}
    <div style="text-align: center; padding: 48px 24px; background: #F9FAFB; border-radius: 16px; border: 2px dashed #D1D5DB;">
        <div style="font-size: 48px; margin-bottom: 12px;">🔗</div>
        <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark, #1A1D23); margin-bottom: 8px;">{{ __('Aucun lien pour le moment') }}</h3>
        <p style="color: var(--c-text-muted, #6E7687); margin-bottom: 20px;">{{ __('Créez votre premier lien court pour commencer à suivre vos clics.') }}</p>
        <a href="{{ route('shorturl.create') }}"
            style="display: inline-block; background: var(--c-primary, #0B7285); color: #fff; padding: 12px 28px; border-radius: 10px; font-weight: 700; text-decoration: none;">
            + {{ __('Créer un lien') }}
        </a>
    </div>
@else
    {{-- Liste des liens --}}
    @foreach($shortUrls as $link)
    <div x-data="{ copied: false, copiedShort: false, copiedLong: false }" style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; transition: box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.06)'" onmouseout="this.style.boxShadow='none'">
        <div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; gap: 12px;">
            {{-- Info lien --}}
            <div style="flex: 1 !important; min-width: 200px;">
                <a href="#" x-on:click.prevent="navigator.clipboard.writeText('{{ $link->getShortUrl() }}'); copiedShort = true; setTimeout(() => copiedShort = false, 1500)"
                    title="{{ __('Cliquer pour copier') }}"
                    style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1.1rem; color: var(--c-primary, #0B7285); text-decoration: none; word-break: break-all; cursor: pointer;">
                    <span x-show="!copiedShort">{{ $link->getShortUrl() }}</span>
                    <span x-show="copiedShort" x-cloak style="color: #10B981;">✅ {{ __('Copié !') }}</span>
                </a>
                <div x-on:click="navigator.clipboard.writeText('{{ $link->original_url }}'); copiedLong = true; setTimeout(() => copiedLong = false, 1500)"
                    title="{{ __('Cliquer pour copier') }}"
                    style="font-size: 13px; color: var(--c-text-muted, #6E7687); margin-top: 4px; word-break: break-all; cursor: pointer;">
                    <span x-show="!copiedLong">🔗 {{ Str::limit($link->original_url, 60) }}</span>
                    <span x-show="copiedLong" x-cloak style="color: #10B981;">✅ {{ __('Copié !') }}</span>
                </div>
                @if($link->title)
                    <div style="font-size: 13px; color: var(--c-dark, #1A1D23); margin-top: 2px; font-weight: 600;">{{ $link->title }}</div>
                @endif
                <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px; margin-top: 8px; font-size: 12px;">
                    <span style="color: var(--c-text-muted, #6E7687);">👆 {{ number_format($link->clicks_count) }} {{ __('clics') }}</span>
                    <span style="color: var(--c-text-muted, #6E7687);">🕐 {{ $link->created_at->diffForHumans() }}</span>
                    @if($link->expires_at)
                        <span style="background: #FFFBEB; color: #92400E; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                            ⏰ {{ $link->expires_at->format('d/m/Y') }}
                        </span>
                    @endif
                    @if($link->password)
                        <span style="background: #FEF2F2; color: #DC2626; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                            🔒 {{ __('protégé') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div style="display: flex !important; flex-wrap: wrap !important; gap: 6px; align-items: center !important;">
                <a href="javascript:void(0)" @click="navigator.clipboard.writeText('{{ $link->getShortUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    :style="copied ? 'background:#10B981;color:#fff;border-color:#10B981;' : 'background:transparent;color:var(--c-dark, #1A1D23);border-color:#D1D5DB;'"
                    style="border: 1px solid #D1D5DB; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; line-height: 1.2; text-decoration: none; display: inline-block;"
                    :aria-label="copied ? '{{ __('Copié') }}' : '{{ __('Copier le lien') }}'">
                    <span x-show="!copied">{{ __('Copier') }}</span>
                    <span x-show="copied" x-cloak>{{ __('Copié!') }}</span>
                </a>
                <a href="{{ route('shorturl.qr', $link->slug) }}" target="_blank"
                    style="border: 1px solid #D1D5DB; color: var(--c-dark, #1A1D23); padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;"
                    aria-label="{{ __('Voir le QR code') }}">QR</a>
                <a href="{{ route('shorturl.stats', $link->slug) }}"
                    style="border: 1px solid #D1D5DB; color: var(--c-dark, #1A1D23); padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;"
                    aria-label="{{ __('Voir les statistiques') }}">{{ __('Stats') }}</a>
                <a href="{{ route('shorturl.user.edit', $link) }}"
                    style="background: var(--c-primary, #0B7285); color: #fff; padding: 5px 10px; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;"
                    aria-label="{{ __('Modifier ce lien') }}">{{ __('Modifier') }}</a>
                @if($link->expires_at)
                <form action="{{ route('shorturl.user.extend', $link) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit"
                        style="-webkit-appearance:none;background:#FFFBEB;color:#92400E;border:1px solid #FDE68A;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;line-height:1.2;"
                        aria-label="{{ __('Prolonger ce lien') }}">⏰ {{ __('Prolonger') }}</button>
                </form>
                @endif
                <form action="{{ route('shorturl.user.destroy', $link) }}" method="POST" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('{{ __('Supprimer ce lien ?') }}')"
                        style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background: transparent; color: #DC2626; border: 1px solid #FECACA; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; line-height: 1.2; outline: none; box-shadow: none;"
                        aria-label="{{ __('Supprimer ce lien') }}">{{ __('Supprimer') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Pagination --}}
    @if($shortUrls->hasPages())
        <div style="margin-top: 20px;">{{ $shortUrls->links() }}</div>
    @endif
@endif

@endsection
