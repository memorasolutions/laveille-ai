@extends('backoffice::layouts.admin', ['title' => __('Modifier le produit')])

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ __('Editer') }} : {{ $product->name }}</h4>
                <form method="POST" action="{{ route('admin.shop.products.update', $product) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    @include('shop::admin.products._form', ['product' => $product])
                    <button type="submit" class="btn btn-primary me-2">{{ __('Mettre à jour') }}</button>
                    <a href="{{ route('admin.shop.products.index') }}" class="btn btn-outline-primary">{{ __('Annuler') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
