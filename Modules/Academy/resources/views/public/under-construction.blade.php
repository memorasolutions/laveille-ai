@extends('fronttheme::layouts.master')

@section('title', 'Académie — bientôt disponible · La veille de Stef')
@section('meta_description', "L'Académie de La veille de Stef est en construction. Nous travaillons activement à son lancement public sur laveille.ai.")

@section('content')
<section class="lv-academy-uc" aria-labelledby="academy-uc-title">
    <div class="lv-academy-uc__card" role="region" aria-label="Académie en construction">
        <p class="lv-academy-uc__badge">
            <span aria-hidden="true">🎓</span>
            <span>En construction</span>
        </p>

        <h1 id="academy-uc-title" class="lv-academy-uc__title">Académie — bientôt disponible</h1>

        <p class="lv-academy-uc__lead">
            Notre espace de formation est en préparation. Nous y travaillons activement pour
            t'offrir des cours clairs et concrets sur l'IA et le numérique. Reviens bientôt !
        </p>

        <div class="lv-academy-uc__timeline" aria-label="Étapes du développement">
            <h2 class="lv-academy-uc__timeline-title">Avancement prévu</h2>
            <ul class="lv-academy-uc__steps">
                <li class="lv-academy-uc__step lv-academy-uc__step--done">
                    <span class="lv-academy-uc__step-icon" aria-hidden="true">✓</span>
                    <span class="lv-academy-uc__step-label"><strong>Conception</strong> — Terminée</span>
                </li>
                <li class="lv-academy-uc__step lv-academy-uc__step--current">
                    <span class="lv-academy-uc__step-icon" aria-hidden="true">🚧</span>
                    <span class="lv-academy-uc__step-label"><strong>Développement</strong> — En cours</span>
                </li>
                <li class="lv-academy-uc__step lv-academy-uc__step--upcoming">
                    <span class="lv-academy-uc__step-icon" aria-hidden="true">🎯</span>
                    <span class="lv-academy-uc__step-label"><strong>Lancement public</strong> — À venir</span>
                </li>
            </ul>
        </div>

        <div class="lv-academy-uc__actions">
            <a href="{{ route('home') }}"
               class="lv-academy-uc__btn lv-academy-uc__btn--primary"
               aria-label="Retour à l'accueil">
                ← Retour à l'accueil
            </a>
        </div>
    </div>
</section>

<style>
.lv-academy-uc {
    --uc-primary: #064E5C;
    --uc-primary-hover: #053E4A;
    --uc-accent: #7C2D12;
    --uc-dark: #1A1D23;
    --uc-bg: #F0FDFA;
    --uc-card-bg: #FFFFFF;
    --uc-border: rgba(11, 114, 133, 0.18);
    --uc-radius: 10px;
    background: var(--uc-bg);
    min-height: 70vh;
    padding: 4rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}
.lv-academy-uc__card {
    max-width: 720px;
    width: 100%;
    background: var(--uc-card-bg);
    border: 1px solid var(--uc-border);
    border-radius: var(--uc-radius);
    padding: 2.5rem 1.75rem;
    text-align: center;
    box-shadow: 0 10px 35px rgba(11, 114, 133, 0.08);
}
@media (min-width: 768px) { .lv-academy-uc__card { padding: 3.25rem 2.5rem; } }
.lv-academy-uc__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.95rem;
    background: #FFF2EC;
    color: var(--uc-accent);
    border: 1px solid #FECABF;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin: 0 0 1.25rem;
}
.lv-academy-uc__title {
    font-size: clamp(1.8rem, 4vw, 2.4rem);
    font-weight: 700;
    color: var(--uc-dark);
    margin: 0 0 0.85rem;
    line-height: 1.2;
}
.lv-academy-uc__lead {
    font-size: 1.075rem;
    color: var(--uc-dark);
    max-width: 56ch;
    margin: 0 auto 2rem;
    line-height: 1.55;
}
.lv-academy-uc__timeline {
    background: #E6F7F5;
    border: 1px solid var(--uc-border);
    border-radius: var(--uc-radius);
    padding: 1.25rem 1.5rem;
    margin: 0 0 2rem;
    text-align: left;
}
.lv-academy-uc__timeline-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--uc-primary);
    margin: 0 0 0.85rem;
}
.lv-academy-uc__steps { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.65rem; }
.lv-academy-uc__step { display: flex; align-items: center; gap: 0.75rem; color: var(--uc-dark); font-size: 0.975rem; line-height: 1.4; }
.lv-academy-uc__step-icon {
    flex-shrink: 0;
    width: 28px; height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
}
.lv-academy-uc__step--done .lv-academy-uc__step-icon { background: var(--uc-primary); color: #FFFFFF; }
.lv-academy-uc__step--current .lv-academy-uc__step-icon { background: #7C2D12; color: #FFFFFF; }
.lv-academy-uc__step--upcoming .lv-academy-uc__step-icon { background: transparent; color: var(--uc-primary); border: 2px solid var(--uc-primary); }
.lv-academy-uc__step--upcoming { opacity: 0.72; }
.lv-academy-uc__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.85rem;
}
.lv-academy-uc__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 1.5rem;
    border-radius: var(--uc-radius);
    font-weight: 600;
    text-decoration: none;
    transition: background-color 0.18s, transform 0.18s;
    min-height: 44px;
    min-width: 200px;
}
.lv-academy-uc__btn--primary { background: var(--uc-primary); color: #FFFFFF; border: 2px solid var(--uc-primary); }
.lv-academy-uc__btn--primary:hover, .lv-academy-uc__btn--primary:focus { background: var(--uc-primary-hover); border-color: var(--uc-primary-hover); }
.lv-academy-uc__btn:focus-visible { outline: 3px solid var(--uc-accent); outline-offset: 3px; }
@media (prefers-reduced-motion: reduce) { .lv-academy-uc__btn { transition: none; } }
</style>
@endsection
