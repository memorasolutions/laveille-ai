<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', $experiment->name)
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.experiments.index') }}">{{ __('Experiences A/B') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $experiment->name }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ $experiment->name }}</h4>
        <a href="{{ route('admin.experiments.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> {{ __('Retour') }}</a>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Informations') }}</h5>
                    <dl class="mb-0">
                        <dt>{{ __('Slug') }}</dt><dd><code>{{ $experiment->slug }}</code></dd>
                        <dt>{{ __('Description') }}</dt><dd>{{ $experiment->description ?? '-' }}</dd>
                        <dt>{{ __('Statut') }}</dt><dd>@php $statusClasses = ['draft' => 'secondary', 'running' => 'primary', 'completed' => 'success']; @endphp<span class="badge bg-{{ $statusClasses[$experiment->status] ?? 'secondary' }}">{{ $experiment->status }}</span></dd>
                        <dt>{{ __('Variantes') }}</dt><dd>@foreach($experiment->variants as $variant)<span class="badge bg-light text-dark border me-1">{{ $variant }}</span>@endforeach</dd>
                        <dt>{{ __('Debut') }}</dt><dd>{{ $experiment->started_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                        <dt>{{ __('Fin') }}</dt><dd>{{ $experiment->ended_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            @if($experiment->status === 'draft')
            <div class="card mb-4">
                <div class="card-body text-center py-4">
                    <p class="text-muted mb-3">{{ __('Cette experience est en brouillon. Demarrez-la pour commencer a collecter des donnees.') }}</p>
                    <form action="{{ route('admin.experiments.start', $experiment) }}" method="POST">
                        @csrf
                        <button type="button" class="btn btn-primary btn-lg" onclick="if(confirm('{{ __('Demarrer cette experience ?') }}')) this.closest('form').submit()"><i data-lucide="play"></i> {{ __('Demarrer l\'experience') }}</button>
                    </form>
                </div>
            </div>
            @endif
            @if($experiment->status === 'running' || $experiment->status === 'completed')
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Resultats') }}</h5>
                    @if(count($results) > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>{{ __('Variante') }}</th><th>{{ __('Participants') }}</th><th>{{ __('Conversions') }}</th><th>{{ __('Taux') }}</th></tr></thead>
                            <tbody>
                                @foreach($results as $variant => $data)
                                <tr>
                                    <td>{{ $variant }}@if($experiment->status === 'completed' && $experiment->winner === $variant)<span class="badge bg-success ms-1">{{ __('Gagnant') }}</span>@endif</td>
                                    <td>{{ $data['participants'] }}</td><td>{{ $data['conversions'] }}</td><td>{{ number_format($data['rate'] * 100, 1) }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted mb-0">{{ __('Aucune donnee collectee pour le moment.') }}</p>
                    @endif
                </div>
            </div>
            @endif
            @if($experiment->status === 'running')
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ __('Terminer l\'experience') }}</h5>
                    <form action="{{ route('admin.experiments.complete', $experiment) }}" method="POST">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-3">
                                <label for="winner" class="form-label">{{ __('Variante gagnante') }}</label>
                                <select class="form-select @error('winner') is-invalid @enderror" id="winner" name="winner" required>
                                    <option value="">{{ __('Selectionnez...') }}</option>
                                    @foreach($experiment->variants as $variant)<option value="{{ $variant }}">{{ $variant }}</option>@endforeach
                                </select>
                                @error('winner')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <button type="button" class="btn btn-success w-100" onclick="if(confirm('{{ __('Terminer cette experience ?') }}')) this.closest('form').submit()"><i data-lucide="flag"></i> {{ __('Terminer') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            @if($experiment->status === 'completed')
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-success mb-0">
                        <i data-lucide="trophy" class="me-2"></i>
                        {{ __('Variante gagnante :') }} <strong>{{ $experiment->winner }}</strong>
                        - {{ __('terminee le') }} {{ $experiment->ended_at->format('d/m/Y a H:i') }}
                    </div>
                </div>
            </div>
            @endif
            <div class="card">
                <div class="card-body text-center">
                    <form action="{{ route('admin.experiments.destroy', $experiment) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-outline-danger" onclick="if(confirm('{{ __('Supprimer definitivement cette experience ?') }}')) this.closest('form').submit()"><i data-lucide="trash-2"></i> {{ __('Supprimer cette experience') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
