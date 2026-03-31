@extends('backoffice::layouts.admin')

@section('title', 'Configuration voting et reputation')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Configuration voting et reputation</h4>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.voting.settings.update') }}" method="POST">
        @csrf

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Seuils de votes</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="voting_threshold_noticed" class="form-label">Seuil "remarque"</label>
                        <input type="number" step="1" min="0" class="form-control @error('voting.threshold_noticed') is-invalid @enderror" id="voting_threshold_noticed" name="voting.threshold_noticed" value="{{ old('voting.threshold_noticed', $settings['voting.threshold_noticed']) }}">
                        @error('voting.threshold_noticed') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="voting_threshold_approved" class="form-label">Seuil "approuve"</label>
                        <input type="number" step="1" min="0" class="form-control @error('voting.threshold_approved') is-invalid @enderror" id="voting_threshold_approved" name="voting.threshold_approved" value="{{ old('voting.threshold_approved', $settings['voting.threshold_approved']) }}">
                        @error('voting.threshold_approved') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="voting_threshold_favorite" class="form-label">Seuil "favori"</label>
                        <input type="number" step="1" min="0" class="form-control @error('voting.threshold_favorite') is-invalid @enderror" id="voting_threshold_favorite" name="voting.threshold_favorite" value="{{ old('voting.threshold_favorite', $settings['voting.threshold_favorite']) }}">
                        @error('voting.threshold_favorite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="voting_rate_limit" class="form-label">Limite de votes par heure</label>
                        <input type="number" step="1" min="0" class="form-control @error('voting.rate_limit') is-invalid @enderror" id="voting_rate_limit" name="voting.rate_limit" value="{{ old('voting.rate_limit', $settings['voting.rate_limit']) }}">
                        @error('voting.rate_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Points de reputation</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="voting_reputation_vote_cast" class="form-label">Points par vote donne</label>
                        <input type="number" step="1" min="0" class="form-control @error('voting.reputation_vote_cast') is-invalid @enderror" id="voting_reputation_vote_cast" name="voting.reputation_vote_cast" value="{{ old('voting.reputation_vote_cast', $settings['voting.reputation_vote_cast']) }}">
                        @error('voting.reputation_vote_cast') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="voting_reputation_community_approved" class="form-label">Points pour approbation communautaire</label>
                        <input type="number" step="1" min="0" class="form-control @error('voting.reputation_community_approved') is-invalid @enderror" id="voting_reputation_community_approved" name="voting.reputation_community_approved" value="{{ old('voting.reputation_community_approved', $settings['voting.reputation_community_approved']) }}">
                        @error('voting.reputation_community_approved') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Niveaux et multiplicateurs</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="reputation_threshold_contributeur" class="form-label">Seuil contributeur (points)</label>
                        <input type="number" step="1" min="0" class="form-control @error('reputation.threshold_contributeur') is-invalid @enderror" id="reputation_threshold_contributeur" name="reputation.threshold_contributeur" value="{{ old('reputation.threshold_contributeur', $settings['reputation.threshold_contributeur']) }}">
                        @error('reputation.threshold_contributeur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reputation_multiplier_contributeur" class="form-label">Multiplicateur contributeur</label>
                        <input type="number" step="0.01" min="1" class="form-control @error('reputation.multiplier_contributeur') is-invalid @enderror" id="reputation_multiplier_contributeur" name="reputation.multiplier_contributeur" value="{{ old('reputation.multiplier_contributeur', $settings['reputation.multiplier_contributeur']) }}">
                        @error('reputation.multiplier_contributeur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reputation_threshold_verifie" class="form-label">Seuil verifie (points)</label>
                        <input type="number" step="1" min="0" class="form-control @error('reputation.threshold_verifie') is-invalid @enderror" id="reputation_threshold_verifie" name="reputation.threshold_verifie" value="{{ old('reputation.threshold_verifie', $settings['reputation.threshold_verifie']) }}">
                        @error('reputation.threshold_verifie') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reputation_multiplier_verifie" class="form-label">Multiplicateur verifie</label>
                        <input type="number" step="0.01" min="1" class="form-control @error('reputation.multiplier_verifie') is-invalid @enderror" id="reputation_multiplier_verifie" name="reputation.multiplier_verifie" value="{{ old('reputation.multiplier_verifie', $settings['reputation.multiplier_verifie']) }}">
                        @error('reputation.multiplier_verifie') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reputation_threshold_expert" class="form-label">Seuil expert (points)</label>
                        <input type="number" step="1" min="0" class="form-control @error('reputation.threshold_expert') is-invalid @enderror" id="reputation_threshold_expert" name="reputation.threshold_expert" value="{{ old('reputation.threshold_expert', $settings['reputation.threshold_expert']) }}">
                        @error('reputation.threshold_expert') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reputation_multiplier_expert" class="form-label">Multiplicateur expert</label>
                        <input type="number" step="0.01" min="1" class="form-control @error('reputation.multiplier_expert') is-invalid @enderror" id="reputation_multiplier_expert" name="reputation.multiplier_expert" value="{{ old('reputation.multiplier_expert', $settings['reputation.multiplier_expert']) }}">
                        @error('reputation.multiplier_expert') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reputation_ban_duration_days" class="form-label">Duree du ban (jours)</label>
                        <input type="number" step="1" min="0" class="form-control @error('reputation.ban_duration_days') is-invalid @enderror" id="reputation_ban_duration_days" name="reputation.ban_duration_days" value="{{ old('reputation.ban_duration_days', $settings['reputation.ban_duration_days']) }}">
                        @error('reputation.ban_duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</div>
@endsection
