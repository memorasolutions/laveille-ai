{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', 'Badge vérifié – ' . $certificate->course->title . ' – ' . config('app.name'))
@section('meta_description', 'Badge OpenBadge 3.0 vérifié : ' . $certificate->user->name . ' a complété le cours « ' . $certificate->course->title . ' » dispensé par ' . config('app.name') . '.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => 'Badge OpenBadge 3.0',
        'breadcrumbItems' => ['Académie', $certificate->course->title, 'Badge'],
    ])
@endsection

@push('styles')
<style>
.ob-wrapper {
    display: flex;
    justify-content: center;
    padding: 2rem 1rem;
}
.ob-card {
    background: #fff;
    border-radius: 16px;
    border: 2px solid #064E5A;
    box-shadow: 0 8px 32px rgba(6,78,90,0.12);
    max-width: 640px;
    width: 100%;
    padding: 2.5rem 2rem;
    text-align: center;
}
.ob-badge-icon {
    width: 100px;
    height: 100px;
    background-color: #064E5A;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: #fff;
    font-size: 3rem;
    line-height: 1;
}
.ob-verified {
    display: inline-block;
    background-color: #064E5A;
    color: #fff;
    border-radius: 8px;
    padding: 0.5rem 1.25rem;
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
}
.ob-course-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #064E5A;
    margin-bottom: 1.25rem;
}
.ob-meta {
    text-align: left;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
}
.ob-meta p {
    margin-bottom: 0.35rem;
}
.ob-description {
    font-size: 0.95rem;
    color: #4B5563;
    margin-bottom: 1.75rem;
}
.ob-serial {
    font-family: 'Courier New', monospace;
    font-size: 0.78rem;
    color: #6B7280;
}
.ob-assertion-url {
    display: block;
    font-family: 'Courier New', monospace;
    font-size: 0.7rem;
    word-break: break-all;
    color: #6B7280;
    margin-top: 1.5rem;
}
.ob-btn-linkedin {
    display: inline-block;
    background-color: #0A66C2;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.55rem 1.25rem;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
}
.ob-btn-linkedin:hover { background-color: #004182; color: #fff; }
</style>
@endpush

@section('content')
<div class="ob-wrapper">
    <div class="ob-card">

        {{-- Icône du badge --}}
        <div class="ob-badge-icon">✓</div>

        {{-- Bandeau vérification --}}
        <div class="ob-verified">✅ Badge vérifié et authentique</div>

        {{-- Titre du cours --}}
        <div class="ob-course-title">{{ $certificate->course->title }}</div>

        {{-- Méta --}}
        <div class="ob-meta">
            <p><strong>Émis à :</strong> {{ $certificate->user->name }}</p>
            <p>
                <strong>Date d'émission :</strong>
                {{ $certificate->issued_at
                    ? $certificate->issued_at->locale('fr_CA')->translatedFormat('j F Y')
                    : '-' }}
            </p>
            <p>
                <strong>N° de série :</strong>
                <span class="ob-serial">{{ $certificate->serial }}</span>
            </p>
        </div>

        {{-- Description --}}
        <p class="ob-description">
            Ce badge vérifie que <strong>{{ $certificate->user->name }}</strong>
            a complété avec succès le cours
            « {{ $certificate->course->title }} »
            dispensé par {{ config('app.name') }}.
        </p>

        {{-- Actions --}}
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            <a href="{{ $linkedInUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="ob-btn-linkedin">
                Ajouter à LinkedIn
            </a>
            <a href="{{ $assertionUrl }}"
               download="badge-{{ $certificate->serial }}.json"
               class="btn btn-outline-secondary">
                Télécharger le JSON
            </a>
        </div>

        {{-- Retour au certificat --}}
        <div class="mb-3">
            <a href="{{ route('academy.certificates.show', $certificate->public_url_slug) }}"
               class="text-decoration-none" style="color: #064E5A; font-size: 0.9rem;">
                ← Voir le certificat complet
            </a>
        </div>

        {{-- URL de l'assertion (vérification par des tiers) --}}
        <a href="{{ $assertionUrl }}"
           target="_blank"
           rel="noopener noreferrer"
           class="ob-assertion-url">{{ $assertionUrl }}</a>

    </div>
</div>
@endsection
