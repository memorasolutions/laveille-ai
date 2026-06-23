<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Newsletter', 'subtitle' => 'Statistiques'])

@section('content')

{{-- ÉTAT VIDE : aucun événement d'engagement enregistré --}}
@unless($hasEvents)
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-start mb-4" role="alert">
        <i data-lucide="info" class="me-3 mt-1 flex-shrink-0" style="width: 22px; height: 22px;"></i>
        <div>
            <h5 class="alert-heading mb-1">Aucune donnée d'engagement pour l'instant</h5>
            <p class="mb-0">
                Configure le webhook Brevo (Réglages &rarr; Webhooks) ; les statistiques
                apparaîtront après le prochain envoi. Les compteurs ci-dessous restent à 0
                en attendant les premiers événements.
            </p>
        </div>
    </div>
@endunless

{{-- ===================== CARTES GLOBALES ===================== --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i data-lucide="activity" class="text-primary mb-2" style="width: 28px; height: 28px;"></i>
                <h6 class="text-muted mb-1 fw-normal small">Total événements</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($global['total_events'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i data-lucide="mail-open" class="text-success mb-2" style="width: 28px; height: 28px;"></i>
                <h6 class="text-muted mb-1 fw-normal small">Ouvertures</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($global['opens'], 0, ',', ' ') }}</h3>
                <small class="text-muted">{{ number_format($global['unique_opens'], 0, ',', ' ') }} uniques</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i data-lucide="mouse-pointer-click" class="text-warning mb-2" style="width: 28px; height: 28px;"></i>
                <h6 class="text-muted mb-1 fw-normal small">Clics</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($global['clicks'], 0, ',', ' ') }}</h3>
                <small class="text-muted">{{ number_format($global['unique_clicks'], 0, ',', ' ') }} uniques</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i data-lucide="alert-triangle" class="text-secondary mb-2" style="width: 28px; height: 28px;"></i>
                <h6 class="text-muted mb-1 fw-normal small">Rebonds</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($global['bounces'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i data-lucide="user-minus" class="text-danger mb-2" style="width: 28px; height: 28px;"></i>
                <h6 class="text-muted mb-1 fw-normal small">Désabonnements réels</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($global['real_unsubs'], 0, ',', ' ') }}</h3>
                @if($global['hygiene_purges'] > 0)
                    <small class="text-muted d-block mt-1">
                        + {{ number_format($global['hygiene_purges'], 0, ',', ' ') }}
                        <span title="Non-confirmés purgés automatiquement apres 7 jours - ne sont pas de vrais departs">purges J+7</span>
                    </small>
                @endif
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i data-lucide="shield-alert" class="text-danger mb-2" style="width: 28px; height: 28px;"></i>
                <h6 class="text-muted mb-1 fw-normal small">Plaintes / spam</h6>
                <h3 class="mb-0 fw-bold">{{ number_format($global['spam'], 0, ',', ' ') }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ===================== TABLEAU PAR NUMÉRO ===================== --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center">
                <h5 class="card-title mb-0">
                    <i data-lucide="newspaper" class="me-2 text-muted" style="width: 18px; height: 18px;"></i>
                    Engagement par numéro
                </h5>
            </div>
            <div class="card-body p-0">
                @if(!empty($rows))
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Numéro</th>
                                    <th>Objet</th>
                                    <th>Envoyé le</th>
                                    <th class="text-center">Destinataires</th>
                                    <th class="text-center">Ouv. uniques</th>
                                    <th class="text-center">Taux d'ouv.</th>
                                    <th class="text-center">Clics uniques</th>
                                    <th class="text-center pe-3">CTR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-nowrap">{{ $row['label'] }}</td>
                                        <td>
                                            <span title="{{ $row['subject'] }}">{{ \Illuminate\Support\Str::limit($row['subject'], 50) }}</span>
                                        </td>
                                        <td class="text-nowrap text-muted">
                                            {{ optional($row['sent_at'])->copy()->setTimezone('America/Toronto')->format('d/m/Y H:i') }}
                                            <small class="d-block text-muted">(Québec)</small>
                                        </td>
                                        <td class="text-center">{{ number_format($row['recipients'], 0, ',', ' ') }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-success rounded-pill">{{ number_format($row['unique_opens'], 0, ',', ' ') }}</span>
                                        </td>
                                        <td class="text-center fw-semibold">{{ number_format($row['open_rate'], 1, ',', ' ') }}&nbsp;%</td>
                                        <td class="text-center">
                                            <span class="badge bg-warning rounded-pill">{{ number_format($row['unique_clicks'], 0, ',', ' ') }}</span>
                                        </td>
                                        <td class="text-center fw-semibold pe-3">{{ number_format($row['ctr'], 1, ',', ' ') }}&nbsp;%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i data-lucide="inbox" class="mb-2" style="width: 32px; height: 32px;"></i>
                        <p class="mb-0">Aucun numéro envoyé pour le moment.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===================== REPARTITION DES ABONNES (table newsletter_subscribers) ===================== --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center">
                <h5 class="card-title mb-0">
                    <i data-lucide="users" class="me-2 text-muted" style="width: 18px; height: 18px;"></i>
                    Repartition des abonnes (base locale)
                </h5>
            </div>
            <div class="card-body pb-2">
                <div class="row g-3">
                    {{-- Désabonnements réels --}}
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded border bg-danger bg-opacity-5">
                            <div class="d-flex align-items-center gap-3">
                                <i data-lucide="user-minus" class="text-danger flex-shrink-0" style="width: 24px; height: 24px;"></i>
                                <div>
                                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($global['real_unsubs'], 0, ',', ' ') }}</h4>
                                    <div class="fw-semibold small">Désabonnements réels</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">
                                        Abonnes confirmes qui ont cliqué « se désabonner ».
                                        Ce sont de vrais departs a analyser.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Purges d'hygiène --}}
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded border bg-secondary bg-opacity-5">
                            <div class="d-flex align-items-center gap-3">
                                <i data-lucide="filter-x" class="text-secondary flex-shrink-0" style="width: 24px; height: 24px;"></i>
                                <div>
                                    <h4 class="mb-0 fw-bold text-secondary">{{ number_format($global['hygiene_purges'], 0, ',', ' ') }}</h4>
                                    <div class="fw-semibold small">Purges d'hygiene (J+7)</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">
                                        Non-confirmes purges automatiquement apres 7 jours.
                                        Ils n'ont jamais recu d'infolettre : ce ne sont pas des departs.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($global['hygiene_purges'] > 0 && ($global['real_unsubs'] + $global['hygiene_purges']) > 0)
                    @php
                        $totalUnsubs = $global['real_unsubs'] + $global['hygiene_purges'];
                        $hygieneRatio = $totalUnsubs > 0 ? round($global['hygiene_purges'] / $totalUnsubs * 100, 0) : 0;
                    @endphp
                    <div class="alert alert-warning border-0 d-flex align-items-start gap-2 mt-3 mb-0" role="alert">
                        <i data-lucide="alert-triangle" class="flex-shrink-0 mt-1" style="width: 16px; height: 16px;"></i>
                        <div class="small">
                            <strong>{{ $hygieneRatio }}&nbsp;% des « désabonnements » sont en fait des purges d'hygiene</strong>
                            ({{ number_format($global['hygiene_purges'], 0, ',', ' ') }} sur {{ number_format($totalUnsubs, 0, ',', ' ') }}).
                            Le chiffre de désabonnements réels a prendre en compte est
                            <strong>{{ number_format($global['real_unsubs'], 0, ',', ' ') }}</strong>.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="alert alert-light border text-muted mb-0" role="alert">
            <i data-lucide="info" class="me-1" style="width: 16px; height: 16px;"></i>
            <strong>Désabonnements réels</strong> : abonnes confirmes qui ont utilise le lien de désabonnement.
            <strong>Purges J+7</strong> : inscrits qui n'ont jamais confirme leur courriel, purges automatiquement apres 7 jours par la commande
            <code>newsletter:purge-unconfirmed</code> — ils n'ont jamais recu d'infolettre et ne representent pas un vrai depart.
            Les cartes « Désabonnements Brevo » (colonne de gauche) proviennent des webhooks
            (table <code>newsletter_events</code>). Les ouvertures et clics « uniques » par numero sont attribues
            par appartenance (liste d'envoi) et fenetre temporelle. Heures : Québec (America/Toronto).
        </div>
    </div>
</div>
@endsection
