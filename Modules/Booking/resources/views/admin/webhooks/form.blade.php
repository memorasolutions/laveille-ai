<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title')
    @isset($webhook) Modifier le webhook @else Créer un webhook @endisset
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">@isset($webhook) Modifier le webhook @else Créer un webhook @endisset</h5>
                </div>
                <form method="POST" action="@isset($webhook){{ route('admin.booking.webhooks.update', $webhook) }}@else{{ route('admin.booking.webhooks.store') }}@endisset">
                    @csrf
                    @isset($webhook)
                        @method('PUT')
                    @endisset

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="url" class="form-label">URL du webhook *</label>
                            <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url', $webhook->url ?? '') }}" placeholder="https://example.com/webhook" required>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Événements</label>
                            <div class="border rounded p-3">
                                @foreach(['appointment.created' => 'Rendez-vous créé', 'appointment.confirmed' => 'Rendez-vous confirmé', 'appointment.cancelled' => 'Rendez-vous annulé', 'appointment.rescheduled' => 'Rendez-vous replanifié'] as $val => $lbl)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="events[]" value="{{ $val }}" id="event_{{ Str::slug($val, '_') }}"
                                            @if(is_array(old('events', $webhook->events ?? [])) && in_array($val, old('events', $webhook->events ?? []))) checked @endif>
                                        <label class="form-check-label" for="event_{{ Str::slug($val, '_') }}">{{ $lbl }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('events')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @if(old('is_active', $webhook->is_active ?? true)) checked @endif>
                                <label class="form-check-label" for="is_active">Webhook actif</label>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.booking.webhooks.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
