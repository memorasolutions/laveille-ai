<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Mes téléchargements'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Mon compte') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Mes téléchargements') }}</li>
    </ol>
</nav>

<h4 class="fw-bold mb-4 d-flex align-items-center gap-2">
    <i data-lucide="download" class="icon-md text-primary"></i> {{ __('Mes téléchargements') }}
</h4>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Fichier') }}</th>
                        <th>{{ __('Commande') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($downloads as $dl)
                    <tr>
                        <td>
                            <i data-lucide="file" class="icon-sm me-1 text-muted"></i>
                            {{ $dl['filename'] }}
                        </td>
                        <td>{{ $dl['order_number'] }}</td>
                        <td class="text-end">
                            <a href="{{ $dl['download_url'] }}" class="btn btn-sm btn-primary">
                                <i data-lucide="download" class="icon-sm me-1"></i> {{ __('Télécharger') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">{{ __('Aucun téléchargement disponible.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
