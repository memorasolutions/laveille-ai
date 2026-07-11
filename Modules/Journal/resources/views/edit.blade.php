<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Éditer « ' . $journal->title . ' » - ' . config('app.name'))
@section('meta_description', 'Constructeur de journal personnel : ajoutez, réorganisez et publiez vos blocs de contenu.')
@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Éditer mon journal'])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin-bottom: 20px;">
                    Éditer mon journal
                </h1>

                <livewire:journal.journal-builder :journal="$journal" />
            </div>
        </div>
    </div>
</section>
@endsection
