@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Wizard produit — options')

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Etape 3 : couleurs et tailles</h4>
        <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-primary" style="width: 60%"></div>
        </div>

        <form method="POST" action="{{ route('admin.shop.wizard.step3') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">Couleurs</div>
                        <div class="card-body">
                            @forelse($colors as $color)
                            <div class="form-check form-check-inline mb-2">
                                <input class="form-check-input" type="checkbox" name="colors[]" id="color-{{ $color }}" value="{{ $color }}">
                                <label class="form-check-label" for="color-{{ $color }}">{{ ucfirst(str_replace('-', ' ', $color)) }}</label>
                            </div>
                            @empty
                            @foreach($bagColors as $color)
                            <div class="form-check form-check-inline mb-2">
                                <input class="form-check-input" type="checkbox" name="colors[]" id="color-{{ $color }}" value="{{ $color }}">
                                <label class="form-check-label" for="color-{{ $color }}">{{ ucfirst(str_replace('-', ' ', $color)) }}</label>
                            </div>
                            @endforeach
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header fw-semibold">Tailles</div>
                        <div class="card-body">
                            @forelse($sizes as $size)
                            <div class="form-check form-check-inline mb-2">
                                <input class="form-check-input" type="checkbox" name="sizes[]" id="size-{{ $size }}" value="{{ $size }}">
                                <label class="form-check-label" for="size-{{ $size }}">{{ strtoupper($size) }}</label>
                            </div>
                            @empty
                            <p class="text-muted">Pas de tailles pour cette categorie (taille unique).</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.shop.wizard.step2') }}" class="btn btn-outline-secondary">Retour</a>
                <button type="submit" class="btn btn-primary">Suivant</button>
            </div>
        </form>
    </div>
</div>
@endsection
