@extends('admin.layouts.master')

@section('title', __('Ajouter un produit'))

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ __('Nouveau produit') }}</h4>
                <form method="POST" action="{{ route('admin.shop.products.store') }}">
                    @csrf
                    @include('shop::admin.products._form')
                    <button type="submit" class="btn btn-primary me-2">{{ __('Sauvegarder') }}</button>
                    <a href="{{ route('admin.shop.products.index') }}" class="btn btn-outline-primary">{{ __('Annuler') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
