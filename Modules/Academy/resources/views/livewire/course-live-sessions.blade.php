{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
     Séances en direct - VUE APPRENANT (inscrit actif). Bouton « Rejoindre » (ouvre le lien
     en nouvel onglet, rel=noopener, + enregistre la présence) + « Ajouter à mon calendrier » (.ics).
     Heures affichées Québec d'abord (UTC entre parenthèses). Charte tokens var(--sys-*). --}}
<div x-data="{
        open(url) {
            if (url) { window.open(url, '_blank', 'noopener,noreferrer'); }
        }
     }"
     @open-live-session.window="open($event.detail.url)">

    @php
        $renderSession = function ($session, bool $upcoming) {
            $qc = $session->starts_at->copy()->setTimezone('America/Toronto');
            $utc = $session->starts_at->copy()->utc();
            return [$qc, $utc];
        };
    @endphp

    {{-- Séances à venir --}}
    <div style="margin-bottom:16px;">
        <h3 style="font-family: var(--f-heading); font-size:1.05rem; margin:0 0 10px;">Séances en direct à venir</h3>

        @if ($this->upcoming->isEmpty())
            <p style="color: var(--sys-text-muted, #6B7280); margin:0;">Aucune séance en direct planifiée pour l'instant.</p>
        @else
            <ul class="list-unstyled" style="margin:0;">
                @foreach ($this->upcoming as $session)
                    @php [$qc, $utc] = $renderSession($session, true); @endphp
                    <li style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:14px 16px; margin-bottom:10px;">
                        <p style="margin:0 0 4px; font-weight:700;">{{ $session->title }}</p>
                        <p style="margin:0 0 8px; font-size:0.88rem; color: var(--sys-text-muted, #6B7280);">
                            {{ $qc->translatedFormat('l d F Y \à H\hi') }} Québec ({{ $utc->format('H:i') }} UTC)
                            · {{ $session->providerLabel() }}
                        </p>
                        @if ($session->description)
                            <p style="margin:0 0 10px; font-size:0.9rem;">{{ $session->description }}</p>
                        @endif
                        <div class="d-flex flex-wrap gap-2">
                            <x-core::button type="button" variant="primary" size="sm" wire:click="join({{ $session->id }})">
                                <span aria-hidden="true">🎥</span> Rejoindre
                            </x-core::button>
                            <x-core::button
                                :href="route('academy.courses.live.ics', [$course->slug, $session->id])"
                                variant="ghost" size="sm">
                                <span aria-hidden="true">📅</span> Ajouter à mon calendrier
                            </x-core::button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Séances passées --}}
    @if ($this->past->isNotEmpty())
        <div>
            <h3 style="font-family: var(--f-heading); font-size:1.05rem; margin:0 0 10px;">Séances passées</h3>
            <ul class="list-unstyled" style="margin:0;">
                @foreach ($this->past as $session)
                    @php [$qc, $utc] = $renderSession($session, false); @endphp
                    <li style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:12px 14px; margin-bottom:8px; opacity:0.85;">
                        <p style="margin:0 0 2px; font-weight:600;">{{ $session->title }}</p>
                        <p style="margin:0; font-size:0.85rem; color: var(--sys-text-muted, #6B7280);">
                            {{ $qc->translatedFormat('l d F Y \à H\hi') }} Québec ({{ $utc->format('H:i') }} UTC)
                            · {{ $session->providerLabel() }}
                        </p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
