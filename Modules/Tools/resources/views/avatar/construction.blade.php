@extends(fronttheme_layout())

@section('title', 'Avatar Studio : en construction · La veille')
@section('meta_description', 'Notre créateur d\'avatar cartoon arrive bientôt sur laveille.ai. Personnalisez votre compagnon de quête IA.')

@push('robots')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<style>
.av-construction {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
    background: linear-gradient(135deg, #fef3e8 0%, #fef9ec 100%);
}
.av-construction__card {
    max-width: 640px;
    width: 100%;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(154, 42, 6, 0.08);
    padding: 2.5rem 2rem;
    text-align: center;
    border-top: 6px solid #f97316;
}
.av-construction__icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    display: inline-block;
    animation: avSwing 2.4s ease-in-out infinite;
}
@keyframes avSwing {
    0%,100% { transform: rotate(-6deg); }
    50% { transform: rotate(6deg); }
}
.av-construction__chip {
    display: inline-block;
    background: #fed7aa;
    color: #c2410c;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    margin-bottom: 0.85rem;
}
.av-construction h1 {
    font-size: 2rem;
    color: #064E5A;
    margin: 0 0 0.75rem;
    line-height: 1.2;
}
.av-construction p {
    color: #52586a;
    font-size: 1.0625rem;
    line-height: 1.65;
    margin: 0 0 1rem;
}
.av-construction__features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin: 1.5rem 0;
    text-align: left;
}
@media (max-width: 540px) {
    .av-construction__features { grid-template-columns: 1fr; }
    .av-construction h1 { font-size: 1.5rem; }
}
.av-construction__feature {
    background: #f0f4f8;
    border-left: 3px solid #064E5A;
    padding: 0.65rem 0.85rem;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #1a1d23;
}
.av-construction__feature strong { color: #064E5A; }
.av-construction__cta {
    display: inline-block;
    background: #f97316;
    color: #fff;
    padding: 0.85rem 1.75rem;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.15s ease;
    margin-top: 1rem;
}
.av-construction__cta:hover { background: #c2410c; }
.av-construction__small {
    font-size: 0.8125rem;
    color: #737373;
    margin-top: 1.25rem;
}
</style>

<div class="av-construction">
    <div class="av-construction__card" role="region" aria-labelledby="av-title">
        <span class="av-construction__icon" aria-hidden="true">🎨</span>
        <span class="av-construction__chip">En construction</span>
        <h1 id="av-title">Avatar Studio arrive bientôt</h1>
        <p>On prépare un créateur d'avatar cartoon pour vous accompagner dans vos quêtes IA, vos badges et votre carnet de bord.</p>

        <div class="av-construction__features" role="list">
            <div class="av-construction__feature" role="listitem"><strong>🎭 Style cartoon</strong><br>Pas de photo, juste du fun</div>
            <div class="av-construction__feature" role="listitem"><strong>🇨🇦 Touches Québec</strong><br>Tuque, mitaines, foulard érable</div>
            <div class="av-construction__feature" role="listitem"><strong>🎛️ Ultra-personnalisable</strong><br>Mode avancé avec micro-ajustements</div>
            <div class="av-construction__feature" role="listitem"><strong>🔒 Loi 25</strong><br>Zéro upload de photo, données chez vous</div>
        </div>

        <a class="av-construction__cta" href="{{ route('newsletter.subscribe') ?? '/' }}">
            M'avertir au lancement
        </a>

        <p class="av-construction__small">Inscription à l'infolettre. Vous recevez aussi votre dose hebdo de veille IA Québec.</p>
    </div>
</div>
@endsection
