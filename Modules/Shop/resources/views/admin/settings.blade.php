@extends('admin.layouts.master')

@section('title', __('Parametres boutique'))

@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ __('Parametres de la boutique') }}</h4>

                @if(!$hasSettingsModule)
                    <div class="alert alert-info">{{ __('Le module Settings n\'est pas actif. Modifiez les variables SHOP_* dans le fichier .env.') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.shop.settings.update') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('Devise') }}</label>
                            <input type="text" class="form-control" name="currency" value="{{ $config['currency'] ?? 'CAD' }}" maxlength="3">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('TPS') }} (%)</label>
                            <input type="number" step="0.001" class="form-control" name="tax_tps" value="{{ $config['tax']['tps'] ?? 5.0 }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('TVQ') }} (%)</label>
                            <input type="number" step="0.001" class="form-control" name="tax_tvq" value="{{ $config['tax']['tvq'] ?? 9.975 }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Produits par page') }}</label>
                        <input type="number" class="form-control" name="pagination" value="{{ $config['pagination'] ?? 12 }}" min="1" max="100" style="max-width: 120px;">
                    </div>

                    <h6 class="mt-4 mb-3">{{ __('Configuration API') }}</h6>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Gelato API key') }}</label>
                        <input type="text" class="form-control" value="{{ $config['gelato']['api_key'] ? '***' . substr($config['gelato']['api_key'], -4) : __('Non configuree') }}" disabled>
                        <small class="text-muted">{{ __('Variable .env : GELATO_API_KEY') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Stripe secret key') }}</label>
                        <input type="text" class="form-control" value="{{ $config['stripe']['secret_key'] ? '***' . substr($config['stripe']['secret_key'], -4) : __('Non configuree') }}" disabled>
                        <small class="text-muted">{{ __('Variable .env : STRIPE_SECRET_KEY') }}</small>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('Sauvegarder') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
