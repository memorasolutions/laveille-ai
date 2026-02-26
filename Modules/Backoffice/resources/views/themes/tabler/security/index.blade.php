@extends('backoffice::layouts.admin', ['title' => 'Sécurité', 'subtitle' => 'Tableau de bord'])
@section('content')
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">IPs bloquées</div><div class="h1 mb-0 mt-2">{{ $blockedIpsCount ?? 0 }}</div></div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">Échecs login (aujourd'hui)</div><div class="h1 mb-0 mt-2">{{ $failedLoginsToday ?? 0 }}</div></div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">Sessions actives</div><div class="h1 mb-0 mt-2">{{ $activeSessionsCount ?? 0 }}</div></div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">Dernier scan</div><div class="h3 mb-0 mt-2">{{ $lastSecurityScan ?? 'N/A' }}</div></div></div>
    </div>
</div>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Actions rapides</h3></div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('admin.blocked-ips.index') }}" class="btn btn-outline-primary"><i class="ti ti-ban me-1"></i> Gérer les IPs bloquées</a>
                    <a href="{{ route('admin.login-history.index') }}" class="btn btn-outline-primary"><i class="ti ti-login me-1"></i> Historique des connexions</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
