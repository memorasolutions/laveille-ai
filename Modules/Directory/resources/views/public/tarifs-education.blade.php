@extends(fronttheme_layout())

@section('title', 'Outils IA pour l\'éducation Québec - tarifs enseignants et étudiants - ' . config('app.name'))

@section('meta_description', 'Outils IA gratuits et à tarif préférentiel pour enseignants, étudiants et institutions éducatives au Québec. Licences éducation vérifiées.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Tarifs éducation'])
@endsection

@push('styles')
<style>
.edu-hero { background: linear-gradient(135deg, #065f46, #059669); color: #fff; padding: 40px 28px; border-radius: var(--r-base); text-align: center; margin: 30px 0 40px; }
.edu-hero h1 { font-family: var(--f-heading); font-size: 2rem; margin: 0 0 14px; font-weight: 800; }
.edu-hero p { max-width: 720px; margin: 0 auto 22px; font-size: 1.05rem; line-height: 1.6; opacity: 0.95; }
.edu-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; max-width: 480px; margin: 0 auto; }
.edu-stats div { background: rgba(255,255,255,0.15); padding: 16px; border-radius: var(--r-base); }
.edu-stats strong { font-size: 1.6rem; display: block; line-height: 1.2; }
.edu-stats small { font-size: 0.85rem; opacity: 0.9; }
.edu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
.edu-card { background: #fff; border: 1px solid #e5e7eb; border-radius: var(--r-base); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; }
.edu-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.edu-card-header img { width: 36px; height: 36px; border-radius: 6px; border: 1px solid #e5e7eb; padding: 2px; background: #f9fafb; }
.edu-badge { background: #065f46; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.4px; text-transform: uppercase; }
.edu-card h3 { font-family: var(--f-heading); font-size: 1.1rem; margin: 0 0 8px; font-weight: 700; }
.edu-card h3 a { color: var(--c-dark); text-decoration: none; }
.edu-card h3 a:hover { color: var(--c-primary); }
.edu-card p { color: #4b5563; font-size: 0.9rem; line-height: 1.5; margin: 0 0 16px; flex-grow: 1; }
.edu-card-actions { display: flex; gap: 10px; margin-top: auto; }
.edu-btn-primary { flex: 1; text-align: center; padding: 9px 14px; background: #065f46; color: #fff; border-radius: var(--r-btn); text-decoration: none; font-weight: 600; font-size: 0.88rem; }
.edu-btn-primary:hover { background: #047857; color: #fff; }
.edu-btn-secondary { flex: 1; text-align: center; padding: 9px 14px; border: 1px solid #065f46; color: #065f46; border-radius: var(--r-btn); text-decoration: none; font-weight: 600; font-size: 0.88rem; }
.edu-btn-secondary:hover { background: #f0fdf4; }
.edu-empty { text-align: center; padding: 60px 24px; background: #f9fafb; border-radius: var(--r-base); border: 1px dashed #d1d5db; }
.edu-cta-wrap { text-align: center; margin: 40px 0 20px; }
.edu-cta { background: #065f46; color: #fff; padding: 14px 34px; border-radius: var(--r-btn); font-weight: 700; text-decoration: none; display: inline-block; font-size: 1rem; }
.edu-cta:hover { background: #047857; color: #fff; }
</style>
@endpush

@section('content')
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <section class="edu-hero" aria-labelledby="edu-title">
        <h1 id="edu-title">🎓 Outils IA pour l'éducation au Québec</h1>
        <p>Découvrez les outils d'intelligence artificielle offerts gratuitement ou à tarif préférentiel aux enseignants, étudiants et institutions éducatives. Sélection vérifiée pour la communauté scolaire québécoise.</p>
        <div class="edu-stats">
            <div><strong>{{ $tools->count() }}</strong><small>outils avec tarif éducation</small></div>
            <div><strong>{{ $tools->where('pricing','education')->count() }}</strong><small>gratuits pour enseignants</small></div>
        </div>
    </section>

    @if($tools->isNotEmpty())
    <div class="edu-grid" role="list" aria-label="Outils IA éducation">
        @foreach($tools as $tool)
            @php $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : ''; @endphp
            <article class="edu-card" role="listitem">
                <div class="edu-card-header">
                    @if($host)
                        <img src="https://www.google.com/s2/favicons?domain={{ $host }}&sz=64" alt="" loading="lazy" onerror="this.style.display='none'">
                    @endif
                    <span class="edu-badge">Éducation</span>
                </div>
                <h3><a href="{{ route('directory.show', $tool->slug) }}">{{ $tool->name }}</a></h3>
                <p>{{ Str::limit($tool->short_description, 140) }}</p>
                <div class="edu-card-actions">
                    @if($tool->url)
                        <a href="{{ $tool->url }}" target="_blank" rel="noopener noreferrer" class="edu-btn-primary" aria-label="Visiter {{ $tool->name }}">Visiter</a>
                    @endif
                    <a href="{{ route('directory.show', $tool->slug) }}" class="edu-btn-secondary" aria-label="Détails de {{ $tool->name }}">Détails</a>
                </div>
            </article>
        @endforeach
    </div>
    @else
    <div class="edu-empty">
        <div style="font-size: 52px; margin-bottom: 16px;" aria-hidden="true">🎓</div>
        <h2 style="font-family: var(--f-heading); color: var(--c-dark);">Aucun outil éducation répertorié pour l'instant</h2>
        <p style="color: #6b7280;">Soyez le premier à proposer un outil avec offre enseignant ou étudiant.</p>
    </div>
    @endif

    <div class="edu-cta-wrap">
        <a href="{{ route('directory.index') }}" class="edu-cta" aria-label="Proposer un outil éducation">Proposer un outil éducation</a>
    </div>
</div>

@if($tools->isNotEmpty())
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"ItemList","name":"Outils IA éducation Québec","description":"Outils IA avec tarif préférentiel pour enseignants, étudiants et institutions éducatives au Québec","numberOfItems":{{ $tools->count() }},"itemListElement":[@foreach($tools as $i => $t){"@type":"ListItem","position":{{ $i+1 }},"item":{"@type":"Product","name":{!! json_encode($t->name) !!},"description":{!! json_encode($t->short_description ?? '') !!},"url":{!! json_encode(route('directory.show', $t->slug)) !!},"offers":{"@type":"Offer","audience":{"@type":"EducationalAudience","educationalRole":"Teacher"}}}}@if(!$loop->last),@endif @endforeach]}
</script>
@endif
@endsection
