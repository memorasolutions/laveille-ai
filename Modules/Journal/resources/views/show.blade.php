<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $journal->title . ' - ' . config('app.name'))
@section('meta_description', 'Journal personnel « ' . $journal->title . ' », publié sur laveille.ai.')

@if (! $journal->isPublished())
    @section('page_noindex', true)
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $journal->title])
@endsection

@push('head')
    {!! \Modules\SEO\Services\JsonLdService::render(
        \Modules\SEO\Services\JsonLdService::article($journal),
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => 'Mes journaux', 'url' => route('journal.index')],
            ['name' => $journal->title, 'url' => route('journal.show', $journal)],
        ])
    ) !!}
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding journal-template-{{ $journal->template }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if (! $journal->isPublished())
                    <div class="alert alert-secondary">
                        Ce journal est un <strong>brouillon privé</strong> — seul(e) vous pouvez le voir tant qu'il n'est pas publié.
                    </div>
                @endif

                <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 4px;">{{ $journal->title }}</h1>
                @if ($journal->journal_date)
                    <p class="text-muted small mb-2">{{ $journal->journal_date->format('Y-m-d') }}</p>
                @endif

                @include('fronttheme::partials.article-action-bar', [
                    'model' => $journal,
                    'modelType' => 'Modules\\Journal\\Models\\Journal',
                ])
                <div class="mb-4">
                    <a href="{{ route('directory.takedown.create', ['url' => route('journal.show', $journal)]) }}" style="color:#9CA3AF; font-size:0.75rem; text-decoration:underline; text-underline-offset:2px;">⚖️ {{ __('Titulaire de droits ? Demander un retrait') }}</a>
                </div>

                @if ($isOwner)
                    <div class="d-flex gap-2 mb-4">
                        <a href="{{ route('journal.edit', $journal) }}" class="btn btn-sm btn-outline-primary">Éditer</a>
                        <a href="{{ route('journal.index') }}" class="btn btn-sm btn-outline-secondary">Mes journaux</a>
                    </div>
                @endif

                <div class="d-flex flex-column gap-4">
                    @forelse ($blocks as $block)
                        <div class="journal-block journal-block-{{ $block->type }}">
                            @switch($block->type)
                                @case('text')
                                    <div class="journal-block-content">{!! $block->payload['html'] ?? '' !!}</div>
                                    @break
                                @case('image')
                                    <img src="{{ $block->payload['url'] ?? '' }}" alt="" class="img-fluid rounded">
                                    @break
                                @case('video')
                                    <div class="ratio ratio-16x9">
                                        <iframe src="{{ $block->payload['embed_url'] ?? '' }}" title="Vidéo" allowfullscreen loading="lazy"></iframe>
                                    </div>
                                    @break
                                @default
                                    <a href="{{ $block->payload['url'] ?? '#' }}" class="text-decoration-none">
                                        <div class="border rounded p-3">
                                            <div class="fw-semibold">{{ $block->payload['title'] ?? '' }}</div>
                                            <div class="small text-muted">{{ $block->payload['excerpt'] ?? '' }}</div>
                                        </div>
                                    </a>
                            @endswitch
                        </div>
                    @empty
                        <p class="text-muted">Ce journal est vide pour l'instant.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
