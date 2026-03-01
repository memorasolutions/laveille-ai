@extends('auth::layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('onboarding.complete') }}" method="POST" x-data="{ step: 1, totalSteps: {{ $steps->count() }} }">
                @csrf

                {{-- Progress bar --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">{{ __('Étape') }} <span x-text="step"></span> / <span x-text="totalSteps"></span></small>
                        <form action="{{ route('onboarding.skip') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm text-muted p-0">{{ __('Passer') }}</button>
                        </form>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" :style="'width: ' + (step / totalSteps * 100) + '%'"></div>
                    </div>
                </div>

                @foreach($steps as $onboardingStep)
                <div x-show="step === {{ $loop->iteration }}" x-cloak>
                    <div class="card rounded-3 border-0 shadow-sm">
                        <div class="card-body text-center py-5 px-4">
                            @if($onboardingStep->icon)
                            <div class="mb-3">
                                <svg class="text-primary" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $onboardingStep->icon }}"/>
                                </svg>
                            </div>
                            @endif

                            <h3 class="fw-bold mb-2">{{ $onboardingStep->title }}</h3>
                            <p class="text-muted mb-4">{{ $onboardingStep->description }}</p>

                            @if($onboardingStep->slug === 'profile')
                            <div class="text-start mx-auto" style="max-width: 400px;">
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('Nom') }}</label>
                                    <input type="text" name="name" id="name" class="form-control" value="{{ auth()->user()->name }}">
                                </div>
                                <div class="mb-3">
                                    <label for="bio" class="form-label">{{ __('Biographie') }}</label>
                                    <textarea name="bio" id="bio" class="form-control" rows="3">{{ auth()->user()->bio }}</textarea>
                                </div>
                            </div>
                            @endif

                            @if($onboardingStep->slug === 'preferences')
                            <div class="text-start mx-auto" style="max-width: 400px;">
                                <div class="mb-3">
                                    <label for="locale" class="form-label">{{ __('Langue') }}</label>
                                    <select name="locale" id="locale" class="form-select">
                                        <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>Français</option>
                                        <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">{{ __('Fuseau horaire') }}</label>
                                    <select name="timezone" id="timezone" class="form-select">
                                        <option value="America/Montreal">Montréal (EST)</option>
                                        <option value="America/Toronto">Toronto (EST)</option>
                                        <option value="Europe/Paris">Paris (CET)</option>
                                        <option value="America/Vancouver">Vancouver (PST)</option>
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="card-footer bg-transparent d-flex justify-content-between py-3">
                            <button type="button" class="btn btn-secondary" x-show="step > 1" @click="step--">{{ __('Précédent') }}</button>
                            <div x-show="step === 1"></div>

                            <template x-if="step < totalSteps">
                                <button type="button" class="btn btn-primary" @click="step++">{{ __('Suivant') }}</button>
                            </template>
                            <template x-if="step === totalSteps">
                                <button type="submit" class="btn btn-success">{{ __('Terminer') }}</button>
                            </template>
                        </div>
                    </div>
                </div>
                @endforeach
            </form>
        </div>
    </div>
</div>
@endsection
