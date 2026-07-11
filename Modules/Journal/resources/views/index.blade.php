<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Mes journaux - ' . config('app.name'))
@section('meta_description', 'Gérez vos journaux personnels : créez, éditez, publiez ou supprimez.')
@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mes journaux'])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0;">Mes journaux</h1>
                    <a href="{{ route('journal.create') }}" class="btn btn-primary">+ Nouveau journal</a>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($journals->isEmpty())
                    <p class="text-muted">Vous n'avez pas encore créé de journal. Sélectionnez du contenu sur le site (actualités, glossaire, annuaire) via le bouton « Ajouter à mon journal », ou commencez par en créer un.</p>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach ($journals as $journal)
                            <div class="border rounded p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $journal->title }}</div>
                                    <div class="small text-muted">
                                        {{ $journal->blocks_count }} bloc(s) ·
                                        <span class="badge {{ $journal->isPublished() ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $journal->isPublished() ? 'Publié' : 'Brouillon privé' }}
                                        </span>
                                        · mis à jour le {{ $journal->updated_at->format('Y-m-d') }}
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('journal.show', $journal) }}" class="btn btn-sm btn-outline-secondary">Prévisualiser</a>
                                    <a href="{{ route('journal.edit', $journal) }}" class="btn btn-sm btn-outline-primary">Éditer</a>
                                    <form method="POST" action="{{ route('journal.destroy', $journal) }}" x-data>
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="$dispatch('open-confirm-global', { message: @js('Supprimer définitivement « ' . $journal->title . ' » ?'), callback: () => $el.closest('form').submit() })">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
