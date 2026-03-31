@extends(fronttheme_layout())

@section('title', 'Bibliothèque de prompts')
@section('meta_description', 'Explorez et copiez des prompts IA créés par la communauté de La veille.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Bibliothèque de prompts')])
@endsection

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "{{ __('Accueil') }}", "item": "{{ route('home') }}"},
        {"@type": "ListItem", "position": 2, "name": "{{ __('Bibliothèque de prompts') }}", "item": "{{ route('prompts.index') }}"}
    ]
}
</script>
@endpush

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">

    <div class="text-center" style="margin-bottom: 30px;">
        <h1>Bibliothèque de prompts</h1>
        <p class="lead text-muted">Explorez les prompts partagés par la communauté</p>
    </div>

    {{-- Barre recherche + tri --}}
    <form action="{{ route('prompts.index') }}" method="GET" style="margin-bottom: 25px;">
        <div class="row">
            <div class="col-sm-8">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Rechercher un prompt..." value="{{ $query }}">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary">Rechercher</button>
                    </span>
                </div>
            </div>
            <div class="col-sm-4" style="margin-top: 0;">
                <select name="sort" class="form-control" onchange="this.form.submit()">
                    <option value="recent" {{ $sort === 'recent' ? 'selected' : '' }}>Plus récents</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Plus anciens</option>
                    <option value="alpha" {{ $sort === 'alpha' ? 'selected' : '' }}>Alphabétique</option>
                </select>
            </div>
        </div>
    </form>

    <p class="text-muted" style="margin-bottom: 20px;">{{ $prompts->total() }} prompt{{ $prompts->total() > 1 ? 's' : '' }} partagé{{ $prompts->total() > 1 ? 's' : '' }}</p>

    {{-- Grille de prompts --}}
    <div class="row">
        @forelse($prompts as $prompt)
            <div class="col-sm-6 col-md-4" style="margin-bottom: 20px;">
                <div class="panel panel-default" style="height: 100%;" x-data="{ copied: false }">
                    <div class="panel-heading">
                        <h3 class="panel-title" style="font-weight: 600;">{{ $prompt->name }}</h3>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted" style="font-size: 12px; margin-bottom: 8px;">
                            Par {{ $prompt->user->name ?? 'Anonyme' }} — {{ $prompt->created_at->diffForHumans() }}
                        </p>
                        <p style="font-size: 14px; line-height: 1.5;">{{ Str::limit($prompt->prompt_text, 150) }}</p>
                    </div>
                    <div class="panel-footer" style="text-align: right;">
                        <button class="btn btn-sm btn-default js-copy-prompt"
                                type="button"
                                :class="{ 'btn-success': copied }"
                                data-prompt-id="{{ $prompt->public_id }}"
                                @click="
                                    const text = document.getElementById('prompt-{{ $prompt->public_id }}')?.textContent || '';
                                    navigator.clipboard.writeText(text).then(() => { copied = true; setTimeout(() => copied = false, 2000); });
                                ">
                            <span x-show="!copied"><i class="fa fa-copy"></i> Copier</span>
                            <span x-show="copied" x-cloak><i class="fa fa-check"></i> Copié</span>
                        </button>
                    </div>
                </div>
                <script type="application/json" id="prompt-{{ $prompt->public_id }}">{{ $prompt->prompt_text }}</script>
            </div>
        @empty
            <div class="col-xs-12">
                <div class="alert alert-info">
                    <p>Aucun prompt partagé pour le moment.</p>
                    @if(Route::has('tools.show'))
                        <a href="{{ route('tools.show', 'constructeur-prompts') }}" class="btn btn-primary" style="margin-top: 10px;">
                            Créer un prompt
                        </a>
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($prompts->hasPages())
        <div class="text-center" style="margin-top: 20px;">
            {{ $prompts->links() }}
        </div>
    @endif

</div>
@endsection
