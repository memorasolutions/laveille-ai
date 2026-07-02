{{--
    Author: MEMORA solutions, https://memora.solutions

    Gamification moderne (LMS 2026) : « Ma progression » (XP/niveau/palier) +
    classement PAR COHORTE (jamais global). Rendu UNIQUEMENT si le drapeau
    academy.gamification_enabled est activé (double garde : vue + composant)
    ET si le palier d'abonnement de l'utilisateur donne droit à la feature
    (TierGateService::hasFeature, voir Gamification::enabled()).

    ACTION: fix régression Scénario F — Livewire exige toujours une racine
    HTML unique ; @if(...) seul autour de TOUT le template produit une sortie
    vide (et une RootTagMissingFromViewException) dès que enabled()=false pour
    un utilisateur connecté sans que le composant soit démonté (cas nouveau
    depuis le tier-gating : le drapeau global reste ON, seul le palier refuse).
    SELF: 2 lignes (div racine toujours présente) — RAISON: refus propre sans
    casser la contrainte "un seul élément racine" de Livewire.
--}}
<div>
@if($this->enabled)
<section aria-labelledby="academy-gamification" class="mb-5">
    <h2 id="academy-gamification"
        style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 16px;">
        <span aria-hidden="true">⭐</span> Ma progression
    </h2>

    @php($progress = $this->progress)

    {{-- ─────────────────────── Bloc XP / niveau ─────────────────────── --}}
    <div class="mb-4 p-3" style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: var(--sys-radius-md, 0.75rem);">
        <div class="d-flex justify-content-between align-items-center mb-1" style="flex-wrap: wrap; gap: 6px;">
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--c-primary, #064E5A);">
                Niveau {{ $progress['level'] }}
            </span>
            <span style="font-size: 0.85rem; color: var(--sys-text-default, #374151);">
                {{ $progress['total'] }} XP au total
            </span>
        </div>

        <div class="progress mb-2"
             style="height: 10px; border-radius: 6px; background: #D1FAE5;">
            <div class="progress-bar"
                 role="progressbar"
                 style="width: {{ $progress['percent'] }}%; background: var(--c-primary, #064E5A); border-radius: 6px;"
                 aria-valuenow="{{ $progress['percent'] }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-label="Progression vers le niveau {{ $progress['level'] + 1 }} : {{ $progress['percent'] }} %">
            </div>
        </div>

        <span style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">
            @if($progress['points_to_next'] > 0)
                Encore {{ $progress['points_to_next'] }} XP avant le niveau {{ $progress['level'] + 1 }}
            @else
                Niveau maximal atteint pour l'instant — continuez à apprendre !
            @endif
        </span>
    </div>

    {{-- ─────────────────────── Opt-out du classement (Loi 25 QC) ─────────────────────── --}}
    <div class="mb-3">
        <button type="button"
                wire:click="toggleLeaderboardVisibility"
                aria-pressed="{{ $this->leaderboardVisible ? 'true' : 'false' }}"
                class="btn btn-sm"
                style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); background: #FFFFFF; color: var(--c-primary, #064E5A); font-size: 0.82rem; padding: 6px 12px;">
            @if($this->leaderboardVisible)
                <span aria-hidden="true">🙈</span> Me retirer du classement
            @else
                <span aria-hidden="true">👁️</span> Réapparaître dans le classement
            @endif
        </button>
        <p style="font-size: 0.76rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
            @if($this->leaderboardVisible)
                Votre prénom et vos points sont visibles par les autres membres de votre/vos cohorte(s).
            @else
                Vous êtes actuellement retiré(e) de tous les classements : les autres apprenants ne vous voient pas.
            @endif
        </p>
    </div>

    @if(session('academy_gamification_status'))
        <div role="status" style="font-size: 0.82rem; color: var(--c-primary, #064E5A); margin-bottom: 12px;">
            {{ session('academy_gamification_status') }}
        </div>
    @endif

    {{-- ─────────────────────── Classement PAR COHORTE (jamais global) ─────────────────────── --}}
    @forelse($this->leaderboards as $group)
        <div class="mb-3" style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 14px 16px;">
            <h3 style="font-family: var(--f-heading); font-size: 0.95rem; color: var(--c-primary, #064E5A); margin: 0 0 10px;">
                Classement — {{ $group['label'] }}
            </h3>

            @if($group['entries']->isNotEmpty())
                <ol style="list-style: none; margin: 0; padding: 0;">
                    @foreach($group['entries']->take(10) as $entry)
                        @php($isSelf = $entry['user']->id === auth()->id())
                        <li wire:key="gami-rank-{{ $group['label'] }}-{{ $entry['user']->id }}"
                            class="d-flex justify-content-between align-items-center"
                            style="padding: 6px 0; border-bottom: 1px solid #F3F4F6; {{ $isSelf ? 'font-weight: 700;' : '' }}">
                            <span style="font-size: 0.85rem; color: var(--sys-text-default, #1A1D23);">
                                {{ $entry['rank'] }}{{ $entry['rank'] === 1 ? 'er' : 'e' }} —
                                {{ \Illuminate\Support\Str::before($entry['user']->name, ' ') }}
                                @if($isSelf)
                                    <span aria-label="c'est vous">(vous)</span>
                                @endif
                            </span>
                            <span style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                                {{ $entry['points'] }} XP · niv. {{ $entry['level'] }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            @else
                <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                    Personne au classement pour l'instant — soyez le/la premier(ère) !
                </p>
            @endif
        </div>
    @empty
        <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
            Aucun classement disponible pour l'instant (inscrivez-vous à une formation pour en débloquer un).
        </p>
    @endforelse
</section>
@endif
</div>
