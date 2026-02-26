@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-20">
        <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
        {{ session('success') }}
    </div>
@endif

<div class="row">
    <div class="col-md-6 col-lg-3 mb-20">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-24">
                <iconify-icon icon="solar:database-outline" class="icon text-primary-600 mb-12" style="font-size: 36px"></iconify-icon>
                <h6 class="mb-8">Cache applicatif</h6>
                <p class="text-secondary-light text-sm mb-16">Données mises en cache (Redis/file)</p>
                <form action="{{ route('admin.cache.clear-cache') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm radius-8">Vider</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-20">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-24">
                <iconify-icon icon="solar:settings-outline" class="icon text-warning-main mb-12" style="font-size: 36px"></iconify-icon>
                <h6 class="mb-8">Configuration</h6>
                <p class="text-secondary-light text-sm mb-16">Cache des fichiers config/</p>
                <form action="{{ route('admin.cache.clear-config') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm radius-8">Vider</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-20">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-24">
                <iconify-icon icon="solar:eye-outline" class="icon text-info-main mb-12" style="font-size: 36px"></iconify-icon>
                <h6 class="mb-8">Vues compilées</h6>
                <p class="text-secondary-light text-sm mb-16">Cache des templates Blade</p>
                <form action="{{ route('admin.cache.clear-views') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-info btn-sm radius-8">Vider</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-20">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-24">
                <iconify-icon icon="solar:routing-outline" class="icon text-success-main mb-12" style="font-size: 36px"></iconify-icon>
                <h6 class="mb-8">Routes</h6>
                <p class="text-secondary-light text-sm mb-16">Cache du registre de routes</p>
                <form action="{{ route('admin.cache.clear-routes') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm radius-8">Vider</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card p-0 radius-12">
    <div class="card-body text-center p-24">
        <form action="{{ route('admin.cache.clear-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger-600 radius-8 d-inline-flex align-items-center gap-2">
                <iconify-icon icon="solar:trash-bin-trash-outline" class="icon text-lg"></iconify-icon>
                Vider tous les caches
            </button>
        </form>
    </div>
</div>
@endsection
