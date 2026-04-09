@extends('backoffice::layouts.admin', ['title' => __('Produits boutique')])

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">{{ __('Produits') }}</h4>
                    <a href="{{ route('admin.shop.products.create') }}" class="btn btn-primary">{{ __('Ajouter un produit') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:60px;">{{ __('Image') }}</th>
                                <th>{{ __('Nom') }}</th>
                                <th>{{ __('Prix') }}</th>
                                <th>{{ __('Categorie') }}</th>
                                <th>{{ __('Statut') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                            <tr @if($product->trashed()) style="opacity: 0.5;" @endif>
                                <td>
                                    @if(!empty($product->images) && isset($product->images[0]))
                                        <img src="{{ $product->images[0] }}" alt="" width="50" style="border-radius: 4px;">
                                    @else
                                        <div style="width:50px;height:50px;background:#e2e8f0;border-radius:4px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#94a3b8;">{{ substr($product->name, 0, 1) }}</div>
                                    @endif
                                </td>
                                <td>{{ $product->name }}<br><small class="text-muted">{{ $product->slug }}</small></td>
                                <td>{{ number_format($product->price, 2, ',', ' ') }} $</td>
                                <td>{{ $product->category ?? '-' }}</td>
                                <td>
                                    @switch($product->status)
                                        @case('published') <span class="badge bg-success">{{ __('Publie') }}</span> @break
                                        @case('draft') <span class="badge bg-warning text-dark">{{ __('Brouillon') }}</span> @break
                                        @case('archived') <span class="badge bg-danger">{{ __('Archive') }}</span> @break
                                    @endswitch
                                </td>
                                <td>
                                    <a href="{{ route('admin.shop.products.edit', $product) }}" class="btn btn-outline-primary btn-sm">{{ __('Editer') }}</a>
                                    <form action="{{ route('admin.shop.products.destroy', $product) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Supprimer ce produit ?') }}')">{{ __('Supprimer') }}</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">{{ __('Aucun produit.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">{{ $products->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
