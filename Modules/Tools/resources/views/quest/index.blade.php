@extends(fronttheme_layout())

@section('title', $meta['title'] . ' — Quête narrative · La veille')
@section('meta_description', 'Apprenez l\'intelligence artificielle en aventure interactive aux côtés de Loop, votre compagnon IA québécois.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Les Sentiers de l\'IA')])
@endsection

@push('robots')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<style>
[x-cloak] { display: none !important; }
.quest-hub { --c-primary: #064E5A; --c-accent: #9A2A06; --c-bg: #F0F4F8; --c-surface: #fff; --c-dark: #1a1d23; --c-muted: #52586a; padding: 2rem 0 4rem; }
.quest-hub *, .quest-hub *::before, .quest-hub *::after { box-sizing: border-box; }
.quest-intro { display: flex; align-items: center; gap: 1.25rem; background: linear-gradient(135deg, #ffffff 0%, var(--c-bg) 100%); border: 1px solid #E5E7EB; border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
.quest-intro__mascot { width: 72px; height: 72px; flex-shrink: 0; animation: bobLoop 3.2s ease-in-out infinite; }
@keyframes bobLoop { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.quest-intro__text { color: var(--c-dark); font-size: 1.0625rem; line-height: 1.5; margin: 0; }
.quest-intro__text strong { color: var(--c-primary); }
@media (max-width: 540px) { .quest-intro { flex-direction: column; text-align: center; padding: 1rem; } }
.quest-status { background: var(--c-surface); border-radius: 16px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 16px rgba(6,78,90,.08); }
.quest-status__connected { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
.quest-status__email { color: var(--c-primary); font-weight: 600; }
.quest-login-form { display: flex; flex-wrap: wrap; gap: .5rem; align-items: stretch; }
.quest-login-form input { flex: 1 1 200px; padding: .65rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; min-width: 0; }
.quest-login-form input:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
.quest-login-form button { background: var(--c-accent); color: #fff; padding: .65rem 1.25rem; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; min-height: 44px; }
.quest-login-form button:hover { background: #7c2105; }
.quest-login-form button:disabled { opacity: .5; cursor: wait; }
.quest-login-msg { margin-top: .65rem; font-size: .9rem; }
.quest-login-msg.ok { color: #047857; }
.quest-login-msg.err { color: #b91c1c; }
.quest-login-help { color: var(--c-muted); font-size: .85rem; margin: .35rem 0 .85rem; }
.quest-section-title { font-size: 1.5rem; margin: 2rem 0 1rem; color: var(--c-dark); display: flex; align-items: center; gap: .5rem; }
.quest-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 640px) { .quest-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 980px) { .quest-grid { grid-template-columns: repeat(3, 1fr); } }
.quest-card { background: var(--c-surface); border-radius: 14px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(6,78,90,.06); border: 2px solid transparent; transition: all .2s ease; display: flex; flex-direction: column; }
.quest-card:hover { transform: translateY(-3px); box-shadow: 0 8px 22px rgba(6,78,90,.12); }
.quest-card--locked { opacity: .55; }
.quest-card--current { border-color: var(--c-accent); }
.quest-card__head { display: flex; gap: .85rem; align-items: flex-start; margin-bottom: .85rem; }
.quest-card__icon { font-size: 2rem; line-height: 1; flex-shrink: 0; }
.quest-card__act { font-size: .7rem; text-transform: uppercase; letter-spacing: 1.2px; color: var(--c-muted); font-weight: 700; }
.quest-card__title { font-size: 1.1rem; color: var(--c-dark); margin: .15rem 0 .15rem; line-height: 1.2; }
.quest-card__subtitle { font-size: .9rem; color: var(--c-muted); line-height: 1.4; margin: 0; }
.quest-card__meta { display: flex; flex-wrap: wrap; gap: .5rem; margin: .5rem 0 1rem; }
.quest-chip { display: inline-flex; align-items: center; gap: .25rem; font-size: .75rem; padding: .25rem .65rem; border-radius: 999px; font-weight: 600; }
.quest-chip--time { background: #ecfdf5; color: #047857; }
.quest-chip--concept { background: #eff6ff; color: var(--c-primary); }
.quest-chip--completed { background: #d1fae5; color: #065f46; }
.quest-chip--available { background: #fef3e8; color: #c2410c; }
.quest-chip--locked { background: #f3f4f6; color: var(--c-muted); }
.quest-card__cta { margin-top: auto; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .65rem 1rem; background: var(--c-primary); color: #fff; text-decoration: none; border-radius: 10px; font-weight: 700; transition: background .15s ease; min-height: 44px; }
.quest-card__cta:hover { background: #053640; }
.quest-card__cta--replay { background: transparent; color: var(--c-primary); border: 2px solid var(--c-primary); }
.quest-card__cta--replay:hover { background: var(--c-primary); color: #fff; }
.quest-badges { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 1rem; padding: 1.25rem; background: var(--c-surface); border-radius: 14px; box-shadow: 0 2px 8px rgba(6,78,90,.06); }
.quest-badge { text-align: center; padding: 1rem .5rem; border-radius: 10px; transition: transform .15s ease; }
.quest-badge--unlocked { background: linear-gradient(135deg, #fef3e8 0%, #fff 100%); border: 2px solid #fdba74; }
.quest-badge--locked { background: #f9fafb; opacity: .4; filter: grayscale(60%); }
.quest-badge__icon { font-size: 2.5rem; line-height: 1; margin-bottom: .5rem; }
.quest-badge__name { font-size: .85rem; font-weight: 700; color: var(--c-dark); }
.quest-footer { text-align: center; padding: 2rem 1rem; color: var(--c-muted); font-size: .9rem; margin-top: 2rem; }
.quest-footer a { color: var(--c-primary); font-weight: 700; }
.flash { padding: .75rem 1rem; border-radius: 10px; margin-bottom: 1rem; }
.flash--success { background: #d1fae5; color: #065f46; }
.flash--error { background: #fee2e2; color: #991b1b; }
@media (prefers-reduced-motion: reduce) { .quest-hero__mascot { animation: none; } .quest-card { transition: none; } }
</style>

<div class="quest-hub" x-data="{ email: @js($email), submitting: false, msg: '', msgType: '' }" x-cloak>
    <div class="container">
    @if(session('quest_success'))
        <div class="flash flash--success" role="status">{{ session('quest_success') }}</div>
    @endif
    @if(session('quest_error'))
        <div class="flash flash--error" role="alert">{{ session('quest_error') }}</div>
    @endif

    <aside class="quest-intro" aria-label="Présentation de la quête">
        <svg class="quest-intro__mascot" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Loop, le robot mascotte">
            <circle cx="50" cy="55" r="38" fill="#dde6ef"/>
            <circle cx="50" cy="55" r="32" fill="#fff"/>
            <circle cx="42" cy="50" r="4.5" fill="#1a1d23"/>
            <circle cx="58" cy="50" r="4.5" fill="#1a1d23"/>
            <circle cx="43" cy="49" r="1.5" fill="#fff"/>
            <circle cx="59" cy="49" r="1.5" fill="#fff"/>
            <path d="M42 62 Q50 68 58 62" stroke="#9A2A06" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            <line x1="50" y1="8" x2="50" y2="18" stroke="#9A2A06" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="50" cy="8" r="3" fill="#9A2A06"/>
            <rect x="14" y="48" width="10" height="14" rx="3" fill="#dde6ef"/>
            <rect x="76" y="48" width="10" height="14" rx="3" fill="#dde6ef"/>
        </svg>
        <p class="quest-intro__text"><strong>{{ $meta['tagline'] }}</strong> — chaque chapitre, une histoire interactive avec Loop pour apprendre l'IA en jouant.</p>
    </aside>

    <section class="quest-status" aria-label="Votre carnet de bord">
        <div x-show="!email" x-cloak>
            <h2 style="margin:0 0 .5rem;font-size:1.15rem;color:var(--c-dark);">📓 Connecte ton carnet de bord</h2>
            <p class="quest-login-help">Reçois un lien magique par courriel pour sauvegarder ta progression. Pas de mot de passe, jamais.</p>
            <form class="quest-login-form" @submit.prevent="submitLogin">
                <input type="email" required placeholder="ton.email@exemple.com" x-ref="emailInput" :disabled="submitting" aria-label="Adresse courriel">
                <button type="submit" :disabled="submitting"><span x-show="!submitting">📩 Recevoir mon lien</span><span x-show="submitting">Envoi…</span></button>
            </form>
            <p class="quest-login-msg" :class="msgType" x-show="msg" x-text="msg" role="status"></p>
            <p class="quest-login-help" style="margin-top:.65rem;">Tu peux aussi jouer en mode anonyme — ton progrès reste local à ce navigateur.</p>
        </div>
        <div x-show="email" x-cloak class="quest-status__connected">
            <div>
                <strong>Carnet connecté :</strong> <span class="quest-status__email" x-text="email"></span>
            </div>
            <form method="POST" action="{{ route('tools.quest.logout') }}">
                @csrf
                <button type="submit" style="background:none;border:1px solid #d1d5db;color:var(--c-muted);padding:.4rem .85rem;border-radius:8px;cursor:pointer;font-size:.85rem;">Déconnexion</button>
            </form>
        </div>
    </section>

    <h2 class="quest-section-title">📜 Chapitres de la saga</h2>
    <div class="quest-grid">
        @php $chapterList = array_values($chapters); @endphp
        @foreach($chapterList as $idx => $chapter)
            @php
                $slug = $chapter['slug'];
                $isCompleted = in_array($slug, $completedSlugs, true);
                $isCurrent = $slug === $currentChapterSlug && ! $isCompleted;
                $isAvailable = $idx === 0 || $isCompleted || $isCurrent;
                $cardClasses = $isCurrent ? 'quest-card--current' : ($isAvailable ? '' : 'quest-card--locked');
            @endphp
            <article class="quest-card {{ $cardClasses }}">
                <div class="quest-card__head">
                    <span class="quest-card__icon" aria-hidden="true">{{ $chapter['icon'] }}</span>
                    <div>
                        <div class="quest-card__act">{{ $chapter['act'] }}</div>
                        <h3 class="quest-card__title">Ch.{{ $chapter['number'] }} — {{ $chapter['title'] }}</h3>
                        <p class="quest-card__subtitle">{{ $chapter['subtitle'] }}</p>
                    </div>
                </div>
                <div class="quest-card__meta">
                    <span class="quest-chip quest-chip--time">⏱ {{ $chapter['estimated_minutes'] }} min</span>
                    <span class="quest-chip quest-chip--concept">📖 {{ $chapter['concept_taught'] }}</span>
                    @if($isCompleted)
                        <span class="quest-chip quest-chip--completed">✓ Complété</span>
                    @elseif($isAvailable)
                        <span class="quest-chip quest-chip--available">▶ Disponible</span>
                    @else
                        <span class="quest-chip quest-chip--locked">🔒 À venir</span>
                    @endif
                </div>
                @if($isAvailable)
                    <a href="{{ route('tools.quest.chapter', ['slug' => $slug]) }}" class="quest-card__cta {{ $isCompleted ? 'quest-card__cta--replay' : '' }}">
                        {{ $isCompleted ? '↻ Rejouer' : '▶ Commencer' }}
                    </a>
                @else
                    <span style="text-align:center;color:var(--c-muted);font-style:italic;margin-top:auto;padding:.65rem;">Bientôt disponible</span>
                @endif
            </article>
        @endforeach
    </div>

    @if(! empty($badges))
        <h2 class="quest-section-title">🏆 Mes badges</h2>
        <div class="quest-badges">
            @foreach($badges as $badgeKey => $badge)
                @php $unlocked = $progress && in_array($badge['id'], $progress->badges ?? [], true); @endphp
                <div class="quest-badge {{ $unlocked ? 'quest-badge--unlocked' : 'quest-badge--locked' }}" title="{{ $badge['description'] }}">
                    <div class="quest-badge__icon">{{ $badge['icon'] }}</div>
                    <div class="quest-badge__name">{{ $badge['name'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <footer class="quest-footer">
        <p>📬 La saga continue chaque semaine via <a href="/">la newsletter La veille</a>.</p>
        <p style="font-size:.8rem;opacity:.7;">🔒 Loi 25 — Tes données restent chez toi. Aucun pisteur, aucun mot de passe.</p>
    </footer>
    </div>{{-- /.container --}}
</div>

<script>
function questHub() {
    return {
        async submitLogin() {
            this.submitting = true;
            this.msg = '';
            try {
                const r = await fetch('{{ route("tools.quest.login") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
                    body: JSON.stringify({ email: this.$refs.emailInput.value }),
                });
                const j = await r.json();
                this.msg = j.message;
                this.msgType = j.ok ? 'ok' : 'err';
            } catch (e) {
                this.msg = 'Erreur réseau. Réessayez.';
                this.msgType = 'err';
            } finally { this.submitting = false; }
        },
    };
}
</script>
@endsection
