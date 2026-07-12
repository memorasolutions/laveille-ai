<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Créer un journal - ' . config('app.name'))
@section('meta_description', 'Créez votre journal personnel : sélectionnez un titre et un gabarit pour commencer.')
@section('page_noindex', true)

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Créer un journal'])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">
                    <h1 style="font-family: var(--f-heading); margin-bottom: 12px;">Créer un journal</h1>
                    <p style="margin-bottom: 28px; color: var(--sys-text-muted, #6B7280);">
                        Choisissez un titre et un gabarit de mise en page. Vous pourrez ajouter du contenu
                        et réorganiser vos blocs ensuite.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('journal.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Titre du journal</label>
                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="template" class="form-label fw-semibold">Gabarit de mise en page</label>
                            <select id="template" name="template" class="form-select" required>
                                @foreach ($templates as $key => $label)
                                    <option value="{{ $key }}" @selected(old('template') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-core::button type="submit" variant="primary">Créer le journal</x-core::button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
