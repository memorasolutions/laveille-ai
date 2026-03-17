@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Promotions'), 'subtitle' => __('Boutique')])

@section('content')
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
            <li class="breadcrumb-item">{{ __('Boutique') }}</li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Promotions') }}</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">{{ __('Promotions') }}</h5>
            <a href="{{ route('admin.ecommerce.promotions.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="me-1"></i> {{ __('Nouvelle promotion') }}
            </a>
        </div>
        <div class="card-body p-0">
            @if($promotions->isEmpty())
                <div class="text-center text-muted py-4">{{ __('Aucune promotion trouvée.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Nom') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Valeur') }}</th>
                                <th>{{ __('Cible') }}</th>
                                <th>{{ __('Priorité') }}</th>
                                <th>{{ __('Actif') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($promotions as $promotion)
                                <tr>
                                    <td>{{ $promotion->name }}</td>
                                    <td>
                                        @switch($promotion->type)
                                            @case('percentage_off')<span class="badge bg-info">{{ __('Pourcentage') }}</span>@break
                                            @case('fixed_off')<span class="badge bg-success">{{ __('Montant fixe') }}</span>@break
                                            @case('bogo')<span class="badge bg-warning">{{ __('BOGO') }}</span>@break
                                            @case('free_shipping')<span class="badge bg-primary">{{ __('Livraison gratuite') }}</span>@break
                                            @case('tiered_pricing')<span class="badge bg-secondary">{{ __('Prix dégressifs') }}</span>@break
                                            @default <span class="badge bg-light text-dark">{{ $promotion->type }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($promotion->type === 'percentage_off')
                                            {{ $promotion->value }}%
                                        @elseif($promotion->type === 'fixed_off')
                                            {{ number_format((float) $promotion->value, 2) }} $
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @switch($promotion->applies_to)
                                            @case('all'){{ __('Tout') }}@break
                                            @case('specific_products'){{ __('Produits') }}@break
                                            @case('specific_categories'){{ __('Catégories') }}@break
                                        @endswitch
                                    </td>
                                    <td>{{ $promotion->priority }}</td>
                                    <td>
                                        @if($promotion->is_active)
                                            <span class="badge bg-success">{{ __('Oui') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('Non') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.ecommerce.promotions.edit', $promotion) }}" class="btn btn-outline-primary btn-sm" title="{{ __('Modifier') }}">
                                            <i data-lucide="pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.ecommerce.promotions.destroy', $promotion) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer cette promotion ?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('Supprimer') }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    {{ $promotions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
