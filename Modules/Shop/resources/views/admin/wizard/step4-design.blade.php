@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Wizard produit — design')

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Etape 4 : design et informations</h4>
        <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-primary" style="width: 80%"></div>
        </div>

        <form action="{{ route('admin.shop.wizard.step4') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nom du produit</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="T-shirt La veille.ai">
                    </div>

                    <div class="mb-3">
                        <label for="short_description" class="form-label fw-semibold">Description courte</label>
                        <textarea class="form-control" id="short_description" name="short_description" maxlength="500" rows="3" placeholder="Description visible sur la fiche produit"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label fw-semibold">Categorie</label>
                        <select class="form-select" id="category" name="category" required>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ ucfirst(str_replace('-', ' ', $cat)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="design" class="form-label fw-semibold">Design (PNG ou JPEG, max 10 Mo)</label>
                        <input class="form-control" type="file" id="design" name="design" accept="image/png,image/jpeg" required>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.shop.wizard.step3') }}" class="btn btn-outline-secondary">Retour</a>
                <button type="submit" class="btn btn-primary">Suivant</button>
            </div>
        </form>
    </div>
</div>
@endsection
