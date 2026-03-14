<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Préférences de notification'))

@section('content')

<div class="mb-3">
    <h1 class="fw-semibold mb-1" style="font-size:1.25rem;">{{ __('Préférences de notification') }}</h1>
    <p class="text-muted mb-0 text-sm">{{ __('Choisissez les notifications que vous souhaitez recevoir par canal.') }}</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('user.notification-preferences.update') }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="font-size:0.8rem;">{{ __('Notification') }}</th>
                            <th class="text-center" style="font-size:0.8rem;width:100px;">
                                <i data-lucide="mail" style="width:14px;height:14px;"></i> {{ __('Courriel') }}
                            </th>
                            <th class="text-center" style="font-size:0.8rem;width:100px;">
                                <i data-lucide="bell" style="width:14px;height:14px;"></i> {{ __('App') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($types as $type => $config)
                            <tr>
                                <td class="align-middle" style="font-size:0.85rem;">{{ $config['label'] }}</td>
                                @foreach(['mail', 'database'] as $channel)
                                    <td class="text-center align-middle">
                                        @if(in_array($channel, $config['channels']))
                                            @php $key = $type.'.'.$channel; @endphp
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       name="preferences[{{ $key }}]"
                                                       value="1"
                                                       {{ ($preferences[$key] ?? true) ? 'checked' : '' }}>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary rounded-2">
            <i data-lucide="save" class="me-1" style="width:14px;height:14px;"></i>{{ __('Enregistrer') }}
        </button>
        <a href="{{ route('user.notifications') }}" class="btn btn-outline-secondary rounded-2 ms-2">{{ __('Retour') }}</a>
    </div>
</form>

@endsection
