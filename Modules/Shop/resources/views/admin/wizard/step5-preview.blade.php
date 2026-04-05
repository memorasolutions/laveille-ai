@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Wizard produit — apercu')

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Etape 5 : apercu et confirmation</h4>
        <div class="progress mb-4" style="height: 6px;">
            <div class="progress-bar bg-success" style="width: 100%"></div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header fw-semibold">Design</div>
                    <div class="card-body text-center">
                        @if($designUrl)
                            <img src="{{ $designUrl }}" alt="Design" class="img-fluid" style="max-height: 250px; border-radius: 8px;">
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header fw-semibold">Recapitulatif</div>
                    <div class="card-body">
                        <p><strong>Nom :</strong> {{ $wizard['name'] ?? '-' }}</p>
                        <p><strong>Categorie :</strong> {{ ucfirst($wizard['category'] ?? '-') }}</p>
                        <p><strong>Description :</strong> {{ $wizard['short_description'] ?? '-' }}</p>
                        <p><strong>Couleurs :</strong> {{ implode(', ', $wizard['colors'] ?? []) }}</p>
                        <p><strong>Tailles :</strong> {{ implode(', ', array_map('strtoupper', $wizard['sizes'] ?? [])) }}</p>
                        <p><strong>Variants :</strong> {{ count($variants) }} combinaisons</p>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($variants))
        <div class="card mb-4">
            <div class="card-header fw-semibold">{{ count($variants) }} variants generees</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Taille</th>
                                <th>Couleur</th>
                                <th>Cout USD</th>
                                <th>Prix CAD</th>
                                <th>Gelato UID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($variants as $v)
                            <tr>
                                <td>{{ $v['size'] ?? '-' }}</td>
                                <td>{{ ucfirst($v['color'] ?? '-') }}</td>
                                <td>{{ $v['cost'] ? number_format($v['cost'], 2) . ' $' : 'N/A' }}</td>
                                <td class="fw-semibold">{{ $v['price'] ? number_format($v['price'], 2) . ' $' : 'N/A' }}</td>
                                <td><code style="font-size: 10px;">{{ Str::limit($v['gelato_uid'] ?? '', 50) }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="alert alert-info">
            Le produit sera cree en mode brouillon. Publiez-le depuis la liste des produits.
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('admin.shop.wizard.step4') }}" class="btn btn-outline-secondary">Retour</a>
            <form action="{{ route('admin.shop.wizard.step5') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">Creer le produit (brouillon)</button>
            </form>
        </div>
    </div>
</div>
@endsection
