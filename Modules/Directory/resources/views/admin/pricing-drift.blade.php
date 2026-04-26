@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Dérive tarifaire')])

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">
            <i data-lucide="alert-triangle" class="me-2"></i>
            Dérive tarifaire
        </h4>
        <a href="{{ route('admin.directory.index') }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" class="me-1"></i>
            Retour
        </a>
    </div>

    @if(!empty($distribution))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i data-lucide="pie-chart" class="me-2"></i>
                    Distribution tarification
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($distribution as $pricingKey => $count)
                        <div class="badge bg-light text-dark border px-3 py-2">
                            <strong>{{ ucfirst(str_replace('_', ' ', $pricingKey)) }}</strong>
                            <span class="badge bg-primary ms-1">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <i data-lucide="database" class="text-primary" width="24" height="24"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Total dérivé</h6>
                        <h5 class="mb-0 fw-bold">{{ $totalDrifted }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <i data-lucide="clock" class="text-warning" width="24" height="24"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Jamais vérifié</h6>
                        <h5 class="mb-0 fw-bold">{{ $neverChecked }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <i data-lucide="alert-octagon" class="text-danger" width="24" height="24"></i>
                    </div>
                    <div>
                        <h6 class="mb-1">Critique (180j+)</h6>
                        <h5 class="mb-0 fw-bold">{{ $criticalDrift }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($totalDrifted === 0)
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i data-lucide="check-circle" class="me-2 flex-shrink-0"></i>
            <div>Tous les outils sont vérifiés.</div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">Tarification</th>
                                <th scope="col">Dernière vérif</th>
                                <th scope="col">Statut</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tools as $tool)
                                <tr>
                                    <td>
                                        @if($tool->url)
                                            <img src="https://www.google.com/s2/favicons?domain={{ urlencode(parse_url($tool->url, PHP_URL_HOST) ?? '') }}&sz=16" alt="" width="16" height="16" class="me-2">
                                        @endif
                                        {{ $tool->name }}
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ ucfirst($tool->pricing) }}</span>
                                    </td>
                                    <td>
                                        @if (is_null($tool->last_enriched_at))
                                            <span class="badge bg-warning text-dark">Jamais</span>
                                        @else
                                            {{ $tool->last_enriched_at->format('Y-m-d') }}
                                            <small class="text-muted">({{ $tool->last_enriched_at->diffForHumans() }})</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!is_null($tool->last_enriched_at) && $tool->last_enriched_at->lt(now()->subDays(180)))
                                            <span class="badge bg-danger">Critique</span>
                                        @elseif (is_null($tool->last_enriched_at))
                                            <span class="badge bg-secondary">Jamais</span>
                                        @else
                                            <span class="badge bg-warning text-dark">À vérifier</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('directory.show', $tool->slug) }}" class="btn btn-sm btn-outline-primary me-1" target="_blank" aria-label="Voir l'outil">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        <a href="{{ route('admin.directory.edit', $tool) }}" class="btn btn-sm btn-outline-secondary" aria-label="Modifier l'outil">
                                            <i data-lucide="pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="alert alert-info mb-0">Aucun outil ne présente une dérive</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">
            {{ $tools->links() }}
        </div>
    @endif
</div>
@endsection
