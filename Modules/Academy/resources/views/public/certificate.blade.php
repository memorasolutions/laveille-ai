{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', 'Certificat – ' . $certificate->course->title . ' – ' . config('app.name'))
@section('meta_description', 'Certificat de complétion du cours « ' . $certificate->course->title . ' » délivré par ' . config('app.name') . '.')

@php
    // ── Personnalisation au niveau du COURS (E3) ────────────────────────────────
    // Chaque champ NULL retombe sur le défaut actuel → rétrocompatibilité totale
    // (un certificat émis avant E3, sur un cours non personnalisé, est rendu à
    // l'identique). Anti-XSS : titre/signature échappés via e() au rendu ; le
    // message est rendu via LessonItem::renderRichText (markdown nettoyé,
    // html_input=strip → aucune injection possible).
    $__course = $certificate->course;

    // Couleur d'accent : on n'accepte QU'un hexadécimal #RGB ou #RRGGBB ; toute
    // valeur non conforme (forgée, vide) → on garde le défaut de charte #064E5A.
    $__defaultAccent = '#064E5A';
    $__accent = $__course->certificate_accent_color ?? '';
    $__accent = (is_string($__accent) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $__accent) === 1)
        ? $__accent
        : $__defaultAccent;

    $__certTitle     = $__course->certificate_title ?: 'Certificat de complétion';
    $__certSignature = $__course->certificate_signature_name ?: null;
    $__certMessageHtml = filled($__course->certificate_message)
        ? \Modules\Academy\Models\LessonItem::renderRichText($__course->certificate_message)
        : null;

    // JSON-LD EducationalOccupationalCredential - tableau PHP, jamais de package spatie/schema-org
    $__certJsonLd = [
        '@context'            => 'https://schema.org',
        '@type'               => 'EducationalOccupationalCredential',
        'name'                => $__certTitle . ' – ' . $certificate->course->title,
        'description'         => 'Certificat de complétion du cours « ' . $certificate->course->title . ' » décerné par ' . config('app.name') . '.',
        'url'                 => url(route('academy.certificates.show', $certificate->public_url_slug)),
        'dateCreated'         => $certificate->issued_at?->toIso8601String(),
        'credentialCategory'  => 'Certificate',
        'recognizedBy'        => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
        'about'               => ['@type' => 'Course', 'name' => $certificate->course->title],
        'holder'              => ['@type' => 'Person', 'name' => $certificate->user->name],
    ];
    // JSON_HEX_TAG échappe < et > (→ < / >) : empêche tout « </script> »
    // injecté via un champ texte (titre, nom de cours, message) de fermer la balise
    // <script type="ld+json"> et d'exécuter du code (anti-XSS dans le JSON-LD).
    $__certJsonLdEncoded = json_encode($__certJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp

@push('head')
<script type="application/ld+json">{!! $__certJsonLdEncoded !!}</script>
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => 'Certificat',
        'breadcrumbItems' => ['Académie', $certificate->course->title, 'Certificat'],
    ])
@endsection

@push('styles')
<style>
.certificate-wrapper {
    display: flex;
    justify-content: center;
    padding: 2rem 1rem;
}
.certificate-card {
    background: #fff;
    border: 2px solid #064E5A;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(6,78,90,0.12);
    padding: 3rem 2.5rem;
    max-width: 800px;
    width: 100%;
    position: relative;
    text-align: center;
    font-family: var(--f-body, -apple-system, BlinkMacSystemFont, sans-serif);
}
.certificate-card::before {
    content: '';
    position: absolute;
    inset: 10px;
    border: 1px solid rgba(6,78,90,0.15);
    border-radius: 12px;
    pointer-events: none;
}
.certificate-logo {
    color: var(--c-primary, #064E5A);
    font-size: 2rem;
    font-weight: 900;
    letter-spacing: -1px;
    margin-bottom: 0.25rem;
}
.certificate-label {
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #6B7280;
    margin-bottom: 2rem;
}
.certificate-certifie {
    font-size: 1rem;
    color: #6B7280;
    margin-bottom: 0.5rem;
}
.certificate-name {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--c-primary, #064E5A);
    font-family: var(--f-heading, Georgia, serif);
    margin: 0.25rem 0 1rem;
    line-height: 1.2;
}
.certificate-completed {
    font-size: 1rem;
    color: #6B7280;
    margin-bottom: 0.5rem;
}
.certificate-course-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1A1D23;
    margin: 0.25rem 0 2rem;
    font-family: var(--f-heading, Georgia, serif);
}
.certificate-divider {
    border: none;
    border-top: 1px solid #E5E7EB;
    margin: 1.5rem 0;
}
.certificate-meta {
    display: flex;
    justify-content: center;
    gap: 2.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.certificate-meta-item {
    text-align: center;
}
.certificate-meta-label {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #6B7280;
    display: block;
    margin-bottom: 0.25rem;
}
.certificate-meta-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #374151;
}
.certificate-serial {
    font-size: 0.78rem;
    color: #6B7280;
    font-family: 'Courier New', monospace;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}
.certificate-verify-url {
    font-size: 0.72rem;
    color: #6B7280;
    word-break: break-all;
    font-family: 'Courier New', monospace;
}
.certificate-badge {
    display: inline-block;
    background: rgba(6,78,90,0.07);
    color: var(--c-primary, #064E5A);
    border-radius: 100px;
    padding: 6px 18px;
    font-size: 0.82rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}
/* ── Personnalisation E3 : accent + message + signature ── */
.certificate-card::before { border-color: var(--cert-accent, rgba(6,78,90,0.15)); opacity: 0.25; }
.certificate-badge {
    background: color-mix(in srgb, var(--cert-accent, #064E5A) 8%, transparent);
    color: var(--cert-accent, #064E5A);
}
.certificate-message {
    font-size: 0.98rem;
    color: #6B7280;
    max-width: 600px;
    margin: -1rem auto 1.5rem;
}
.certificate-message > :first-child { margin-top: 0; }
.certificate-message > :last-child { margin-bottom: 0; }
.certificate-signature {
    margin-top: 2rem;
    margin-bottom: 0.5rem;
}
.certificate-signature-line {
    display: block;
    width: 220px;
    max-width: 80%;
    margin: 0 auto 0.4rem;
    border-top: 1px solid var(--cert-accent, #064E5A);
}
.certificate-signature-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #374151;
}
/* ── Impression ── */
@media print {
    header, footer, nav,
    [class*="breadcrumb"],
    .ct-footer,
    .wpo-header-area,
    .no-print {
        display: none !important;
    }
    body { background: none !important; }
    .certificate-wrapper { padding: 0; }
    .certificate-card {
        box-shadow: none;
        border: 2px solid #064E5A;
        border-radius: 8px;
        padding: 2rem 1.5rem;
        max-width: 100%;
        break-inside: avoid;
    }
    .certificate-card::before { inset: 6px; }
}
</style>
@endpush

@section('content')
@php
    // La couleur d'accent (déjà validée hex plus haut) pilote l'identité visuelle :
    // logo + nom (via --c-primary), bordure de carte, badge. On la passe en variables
    // CSS inline sur la carte : aucune injection possible (preg_match strict #hex).
    $__cardStyle = '--c-primary: ' . $__accent . '; --cert-accent: ' . $__accent
        . '; border-color: ' . $__accent . ';';
@endphp
<div class="certificate-wrapper">
    <div class="certificate-card" style="{{ $__cardStyle }}">

        {{-- En-tête organisateur --}}
        <div class="certificate-logo">{{ config('app.name') }}</div>
        <div class="certificate-label">{{ $__certTitle }}</div>

        <span class="certificate-badge">✓ Formation complétée</span>

        {{-- Corps --}}
        <p class="certificate-certifie">Délivré à</p>
        <div class="certificate-name">{{ $certificate->user->name }}</div>

        <p class="certificate-completed">pour avoir complété avec succès le cours</p>
        <div class="certificate-course-title">{{ $certificate->course->title }}</div>

        {{-- Message / mention personnalisé(e) du cours (E3), sous le nom du cours.
             Markdown nettoyé (html_input=strip) → rendu {!! !!} sûr (anti-XSS). --}}
        @if($__certMessageHtml)
            <div class="certificate-message academy-richtext">{!! $__certMessageHtml !!}</div>
        @endif

        <hr class="certificate-divider">

        {{-- Méta --}}
        <div class="certificate-meta">
            <div class="certificate-meta-item">
                <span class="certificate-meta-label">Date d'émission</span>
                <span class="certificate-meta-value">
                    {{ $certificate->issued_at
                        ? $certificate->issued_at->locale('fr_CA')->translatedFormat('j F Y')
                        : '-' }}
                </span>
            </div>
            @if($certificate->hours_earned)
                <div class="certificate-meta-item">
                    <span class="certificate-meta-label">Durée</span>
                    <span class="certificate-meta-value">
                        {{ $certificate->hours_earned }} heure{{ $certificate->hours_earned > 1 ? 's' : '' }}
                    </span>
                </div>
            @endif
            @if($certificate->final_score !== null)
                <div class="certificate-meta-item">
                    <span class="certificate-meta-label">Score final</span>
                    <span class="certificate-meta-value">{{ $certificate->final_score }} %</span>
                </div>
            @endif
        </div>

        {{-- Signature personnalisée du cours (E3). Échappée par Blade (anti-XSS). --}}
        @if($__certSignature)
            <div class="certificate-signature">
                <span class="certificate-signature-line"></span>
                <span class="certificate-signature-name">{{ $__certSignature }}</span>
            </div>
        @endif

        {{-- Numéro de série & vérification --}}
        <div class="certificate-serial">N° {{ $certificate->serial }}</div>
        <div class="certificate-verify-url">
            Vérifier : {{ url(route('academy.certificates.show', $certificate->public_url_slug)) }}
        </div>

        {{-- Actions (masquées à l'impression) --}}
        <div class="mt-4 d-flex flex-wrap justify-content-center gap-3 no-print">
            <x-core::button :href="route('academy.certificates.download', $certificate->public_url_slug)" variant="primary" icon="📄">
                Télécharger le PDF
            </x-core::button>
            <x-core::button type="button" variant="secondary" icon="🖨️" onclick="window.print()">
                Imprimer
            </x-core::button>
            <x-core::button :href="route('academy.courses.show', $certificate->course->slug)" variant="secondary">
                Retour au cours
            </x-core::button>
        </div>

    </div>
</div>
@endsection
