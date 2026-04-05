@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Wizard produit — produit de base')

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Etape 2 : choisir un produit de base</h4>
        <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-primary" style="width: 40%"></div>
        </div>

        <form method="POST" action="{{ route('admin.shop.wizard.step2') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>UID</th>
                                    <th>Categorie</th>
                                    <th>Couleur</th>
                                    <th>Taille</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    @if(($product['attributes']['ProductStatus'] ?? '') === 'activated')
                                    <tr>
                                        <td>
                                            <input class="form-check-input" type="radio" name="product_uid" value="{{ $product['productUid'] ?? '' }}" required>
                                        </td>
                                        <td><code style="font-size: 11px;">{{ Str::limit($product['productUid'] ?? '', 60) }}</code></td>
                                        <td>{{ $product['attributes']['GarmentCategory'] ?? $product['attributes']['MugSize'] ?? $product['attributes']['BagSubCategory'] ?? '-' }}</td>
                                        <td>{{ $product['attributes']['GarmentColor'] ?? $product['attributes']['BagColor'] ?? $product['attributes']['MugMaterial'] ?? '-' }}</td>
                                        <td>{{ $product['attributes']['GarmentSize'] ?? '-' }}</td>
                                        <td><span class="badge bg-success">{{ $product['attributes']['ProductStatus'] ?? '' }}</span></td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.shop.wizard.step1') }}" class="btn btn-outline-secondary">Retour</a>
                <button type="submit" class="btn btn-primary">Suivant</button>
            </div>
        </form>
    </div>
</div>
@endsection
