@extends('backoffice::layouts.master')
@section('title', __('Modifier la ressource'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">📝 {{ __('Modifier la ressource') }} #{{ $resource->id }}</h4>
    <a href="{{ route('admin.directory.resources') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Retour') }}</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.directory.resources.update', $resource->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">{{ __('Titre') }}</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $resource->title) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">{{ __('Type') }}</label>
                    <select name="type" class="form-select">
                        <option value="video" {{ $resource->type === 'video' ? 'selected' : '' }}>Video</option>
                        <option value="article" {{ $resource->type === 'article' ? 'selected' : '' }}>Article</option>
                        <option value="tutorial" {{ $resource->type === 'tutorial' ? 'selected' : '' }}>Tutoriel</option>
                        <option value="documentation" {{ $resource->type === 'documentation' ? 'selected' : '' }}>Documentation</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">{{ __('Langue') }}</label>
                    <select name="language" class="form-select">
                        <option value="fr" {{ $resource->language === 'fr' ? 'selected' : '' }}>Francais</option>
                        <option value="en" {{ $resource->language === 'en' ? 'selected' : '' }}>Anglais</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('URL') }}</label>
                    <input type="url" class="form-control" value="{{ $resource->url }}" disabled>
                    <small class="text-muted">{{ __('Non modifiable (identifiant unique)') }}</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('Video ID YouTube') }}</label>
                    <input type="text" class="form-control" value="{{ $resource->video_id ?? '-' }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('Durée (secondes)') }}</label>
                    <input type="number" name="duration_seconds" class="form-control" value="{{ old('duration_seconds', $resource->duration_seconds) }}" min="0">
                    @if($resource->duration_seconds)
                        <small class="text-muted">{{ gmdate($resource->duration_seconds >= 3600 ? 'G:i:s' : 'i:s', $resource->duration_seconds) }}</small>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('Nom de la chaine') }}</label>
                    <input type="text" name="channel_name" class="form-control" value="{{ old('channel_name', $resource->channel_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('URL de la chaine') }}</label>
                    <input type="url" name="channel_url" class="form-control" value="{{ old('channel_url', $resource->channel_url) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">{{ __('Résumé IA') }}</label>
                <textarea name="video_summary" class="form-control" rows="8" placeholder="{{ __('Résumé généré par IA ou saisi manuellement...') }}">{{ old('video_summary', $resource->video_summary) }}</textarea>
                <small class="text-muted">{{ __('Markdown supporté. Laissez vide pour aucun résumé.') }}</small>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_approved" value="0">
                    <input type="checkbox" name="is_approved" value="1" class="form-check-input" id="is_approved" {{ $resource->is_approved ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_approved">{{ __('Approuvé (visible publiquement)') }}</label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <div>
                    <small class="text-muted">
                        {{ __('Soumis par') }} {{ $resource->user?->name ?? __('Anonyme') }}
                        {{ __('le') }} {{ $resource->created_at?->format('d/m/Y H:i') ?? '-' }}
                        | {{ __('Outil') }} : {{ is_array($resource->tool?->name) ? ($resource->tool->name['fr_CA'] ?? '') : ($resource->tool?->name ?? '-') }}
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
