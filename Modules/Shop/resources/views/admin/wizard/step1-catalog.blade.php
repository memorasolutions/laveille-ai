@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Wizard produit — catalogue')

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Etape 1 : choisir un catalogue</h4>
        <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-primary" style="width: 20%"></div>
        </div>

        <form method="POST" action="{{ route('admin.shop.wizard.step1') }}">
            @csrf
            <div class="row">
                @foreach($catalogs as $catalog)
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="card h-100" style="cursor: pointer;" for="cat-{{ $catalog['catalogUid'] ?? $catalog['catalog_uid'] ?? '' }}">
                        <div class="card-body d-flex align-items-center gap-3">
                            <input class="form-check-input" type="radio" name="catalog_uid" id="cat-{{ $catalog['catalogUid'] ?? '' }}" value="{{ $catalog['catalogUid'] ?? '' }}" required>
                            <span class="fw-semibold">{{ $catalog['title'] ?? $catalog['catalogUid'] ?? '' }}</span>
                        </div>
                    </label>
                </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">Suivant</button>
            </div>
        </form>
    </div>
</div>
@endsection
