<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Newsletter', 'subtitle' => 'Statistiques'])

@section('content')
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i data-lucide="bar-chart-3" style="width: 40px; height: 40px;"></i>
                </div>
                <div>
                    <h6 class="card-title text-white-50 mb-1 fw-normal">Total événements</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($eventCounts->sum(), 0, ',', ' ') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i data-lucide="mail-open" style="width: 40px; height: 40px;"></i>
                </div>
                <div>
                    <h6 class="card-title text-white-50 mb-1 fw-normal">Taux d'ouverture</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($openRate, 1, ',', ' ') }}%</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i data-lucide="mouse-pointer-click" style="width: 40px; height: 40px;"></i>
                </div>
                <div>
                    <h6 class="card-title text-white-50 mb-1 fw-normal">Taux de clic</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($clickRate, 1, ',', ' ') }}%</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i data-lucide="users" style="width: 40px; height: 40px;"></i>
                </div>
                <div>
                    <h6 class="card-title text-white-50 mb-1 fw-normal">Ouvertures</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($eventCounts->get('opened', 0), 0, ',', ' ') }}</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                    <i data-lucide="mouse-pointer-click" class="me-2 text-muted" style="width: 18px; height: 18px;"></i>
                    Top 10 liens cliqués
                </h5>
            </div>
            <div class="card-body p-0">
                @if($topLinks->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 5%;">#</th>
                                    <th>Lien</th>
                                    <th class="text-end pe-3" style="width: 15%;">Clics</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topLinks as $index => $item)
                                    <tr>
                                        <td class="ps-3 text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ $item->link }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none" title="{{ $item->link }}">
                                                {{ Str::limit($item->link, 60) }}
                                            </a>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span class="badge bg-primary rounded-pill">{{ number_format($item->count, 0, ',', ' ') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i data-lucide="inbox" class="mb-2" style="width: 32px; height: 32px;"></i>
                        <p class="mb-0">Aucun lien cliqué pour le moment.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="card-title mb-0">
                    <i data-lucide="users" class="me-2 text-muted" style="width: 18px; height: 18px;"></i>
                    Top 10 abonnés actifs
                </h5>
            </div>
            <div class="card-body p-0">
                @if($topSubscribers->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 5%;">#</th>
                                    <th>Courriel</th>
                                    <th class="text-center" style="width: 15%;">Ouvertures</th>
                                    <th class="text-center pe-3" style="width: 15%;">Clics</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topSubscribers as $index => $subscriber)
                                    <tr>
                                        <td class="ps-3 text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <i data-lucide="mail" class="me-1 text-muted" style="width: 14px; height: 14px;"></i>
                                            {{ $subscriber->email }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success rounded-pill">{{ number_format($subscriber->opened_count, 0, ',', ' ') }}</span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <span class="badge bg-warning rounded-pill">{{ number_format($subscriber->clicked_count, 0, ',', ' ') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i data-lucide="inbox" class="mb-2" style="width: 32px; height: 32px;"></i>
                        <p class="mb-0">Aucun abonné actif pour le moment.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="alert alert-light border text-muted text-center mb-0" role="alert">
            <i data-lucide="info" class="me-1" style="width: 16px; height: 16px;"></i>
            Statistiques des 30 derniers jours. Les données proviennent des webhooks Brevo.
        </div>
    </div>
</div>
@endsection
