<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Mes journaux - ' . config('app.name'))
@section('meta_description', 'Gérez vos journaux personnels : créez, éditez, publiez ou supprimez.')
@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mes journaux'])
@endsection

@push('styles')
<style>
    {{-- Refonte 2026 (cf. journal show.blade.php) : cartes calmes, hover discret, typographie affinée. --}}
    .journal-index-title { font-size: clamp(1.6rem, 1.3rem + 1vw, 2.1rem); font-weight: 800; letter-spacing: -0.01em; }
    .journal-card {
        background: #fff; border: 1px solid #EEF0F2 !important; border-radius: 14px;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .journal-card:hover { box-shadow: 0 10px 24px -12px rgba(6,78,90,0.16); transform: translateY(-1px); }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h1 class="journal-index-title" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0;">Mes journaux</h1>
                    <x-core::button href="{{ route('journal.create') }}" variant="primary">+ Nouveau journal</x-core::button>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($journals->isEmpty())
                    <p class="text-muted">Vous n'avez pas encore créé de journal. Sélectionnez du contenu sur le site (actualités, glossaire, annuaire) via le bouton « Ajouter à mon journal », ou commencez par en créer un.</p>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach ($journals as $journal)
                            <div class="journal-card p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $journal->title }}</div>
                                    <div class="small text-muted">
                                        {{ $journal->blocks_count }} bloc(s) ·
                                        {{-- Couleur en style inline (tokens charte) plutôt que text-bg-success/secondary :
                                             sur ce thème public (FrontTheme), le pont CSS --bs-success-rgb consommé par
                                             .text-bg-success n'est pas garanti disponible au même titre que sur le
                                             thème admin, rendant le badge "Publié" illisible (texte blanc sur fond
                                             transparent) — même classe de fragilité déjà rencontrée avec Tiptap. --}}
                                        <span class="badge" style="background-color: {{ $journal->isPublished() ? 'var(--sys-success, #047857)' : 'var(--sys-text-muted, #6b7280)' }}; color: #fff;">
                                            {{ $journal->isPublished() ? 'Publié' : 'Brouillon privé' }}
                                        </span>
                                        · mis à jour le {{ $journal->updated_at->format('Y-m-d') }}
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <x-core::button href="{{ route('journal.show', $journal) }}" variant="secondary" size="sm">Prévisualiser</x-core::button>
                                    <x-core::button href="{{ route('journal.edit', $journal) }}" variant="secondary" size="sm">Éditer</x-core::button>
                                    <form method="POST" action="{{ route('journal.destroy', $journal) }}" x-data>
                                        @csrf
                                        @method('DELETE')
                                        {{-- @js() (directive Blade) ne se compile pas dans un attribut de balise composant
                                             <x-core::button> (contrairement à un <button> HTML brut) - trouvé par la
                                             vérification visuelle du 2026-07-11 : le HTML rendu contenait "@js(...)" en
                                             texte brut, non compilé. Fix : pré-calculer via {{ }} (echo standard, déjà
                                             utilisé sans problème pour le prop "variant" dynamique ci-dessus). --}}
                                        @php $deleteConfirmMessage = \Illuminate\Support\Js::from('Supprimer définitivement « ' . $journal->title . ' » ?'); @endphp
                                        <x-core::button variant="danger" size="sm" @click="$dispatch('open-confirm-global', { message: {{ $deleteConfirmMessage }}, callback: () => $el.closest('form').submit() })">Supprimer</x-core::button>
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
