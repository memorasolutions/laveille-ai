<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Avis clients'), 'subtitle' => __('Boutique')])

@section('content')
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
            <li class="breadcrumb-item">{{ __('Boutique') }}</li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Avis clients') }}</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">{{ __('Avis clients') }}</h5>
            <span class="badge bg-warning">{{ $reviews->total() }} {{ __('avis') }}</span>
        </div>
        <div class="card-body p-0">
            @if($reviews->isEmpty())
                <div class="text-center text-muted py-4">{{ __('Aucun avis trouvé.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Produit') }}</th>
                                <th>{{ __('Client') }}</th>
                                <th>{{ __('Note') }}</th>
                                <th>{{ __('Titre') }}</th>
                                <th>{{ __('Statut') }}</th>
                                <th>{{ __('Vérifié') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reviews as $review)
                                <tr>
                                    <td>{{ $review->product->name }}</td>
                                    <td>{{ $review->user->name ?? __('Invité') }}</td>
                                    <td>
                                        @foreach(range(1, 5) as $i)
                                            <i data-lucide="star" style="width:14px;height:14px;{{ $i <= $review->rating ? 'fill:currentColor;' : '' }}" class="{{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endforeach
                                    </td>
                                    <td><span class="d-inline-block text-truncate" style="max-width:150px;" title="{{ $review->title }}">{{ $review->title ?? '-' }}</span></td>
                                    <td>
                                        @if($review->is_approved)
                                            <span class="badge bg-success">{{ __('Approuvé') }}</span>
                                        @else
                                            <span class="badge bg-warning">{{ __('En attente') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($review->is_verified_purchase)
                                            <span class="badge bg-info">{{ __('Oui') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Non') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $review->created_at->format('d/m/Y') }}</td>
                                    <td class="text-end">
                                        @if(! $review->is_approved)
                                        <form action="{{ route('admin.ecommerce.reviews.approve', $review) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="{{ __('Approuver') }}">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        @endif
                                        <form action="{{ route('admin.ecommerce.reviews.reject', $review) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer cet avis ?') }}')">
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
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
