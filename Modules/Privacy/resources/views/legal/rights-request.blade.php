{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Exercer vos droits') . ' - ' . config('app.name'))
@section('meta_description', __('Formulaire d\'exercice de vos droits sur vos données personnelles — RGPD, Loi 25, LPRPDE.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Exercer vos droits')])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Exercer vos droits') }}</h2>

                        <div class="entry-details" style="line-height: 1.8;">
                            @if (session('success'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div class="alert alert-info">
                                <p style="margin-bottom: 8px;">
                                    <strong>{{ __('Conformément aux lois applicables (RGPD, Loi 25, LPRPDE), vous disposez de droits sur vos données personnelles :') }}</strong>
                                </p>
                                <ul style="margin-bottom: 8px;">
                                    @foreach($request_types as $type => $label)
                                        <li>{{ $label }}</li>
                                    @endforeach
                                </ul>
                                <p style="font-size: 13px; margin-bottom: 4px;">
                                    {{ __('Nous nous engageons à répondre à votre demande dans un délai de :days jours maximum.', ['days' => $response_delay_days]) }}
                                </p>
                                <p style="font-size: 13px; margin-bottom: 0;">
                                    {{ __('Pour toute question, contactez notre DPO :') }}
                                    <strong>{{ $company['dpo_name'] }}</strong> —
                                    <a href="mailto:{{ $company['dpo_email'] }}">{{ $company['dpo_email'] }}</a>
                                </p>
                            </div>

                            <form method="POST" action="{{ route('legal.rights.store') }}" enctype="multipart/form-data" novalidate>
                                @csrf

                                <div class="form-group">
                                    <label for="name">{{ __('Nom complet') }} <span style="color: #d9534f;">*</span></label>
                                    <input type="text" name="name" id="name" required aria-required="true"
                                        class="form-control @error('name') has-error @enderror"
                                        value="{{ old('name') }}" autocomplete="name">
                                    @error('name')
                                        <p style="color: #d9534f; font-size: 13px; margin-top: 4px;" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="email">{{ __('Adresse courriel') }} <span style="color: #d9534f;">*</span></label>
                                    <input type="email" name="email" id="email" required aria-required="true"
                                        class="form-control @error('email') has-error @enderror"
                                        value="{{ old('email') }}" autocomplete="email">
                                    @error('email')
                                        <p style="color: #d9534f; font-size: 13px; margin-top: 4px;" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="request_type">{{ __('Type de demande') }} <span style="color: #d9534f;">*</span></label>
                                    <select name="request_type" id="request_type" required aria-required="true"
                                        class="form-control @error('request_type') has-error @enderror">
                                        <option value="" disabled selected>{{ __('Sélectionnez un type') }}</option>
                                        @foreach($request_types as $type => $label)
                                            <option value="{{ $type }}" @selected(old('request_type') === $type)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('request_type')
                                        <p style="color: #d9534f; font-size: 13px; margin-top: 4px;" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">{{ __('Description de la demande') }} <span style="color: #d9534f;">*</span></label>
                                    <textarea name="description" id="description" required aria-required="true" rows="5"
                                        class="form-control @error('description') has-error @enderror">{{ old('description') }}</textarea>
                                    @error('description')
                                        <p style="color: #d9534f; font-size: 13px; margin-top: 4px;" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="file">{{ __('Document justificatif (optionnel)') }}</label>
                                    <input type="file" name="file" id="file" accept="application/pdf,image/jpeg,image/png" class="form-control">
                                    <p style="color: #999; font-size: 12px; margin-top: 4px;">{{ __('Formats acceptés : PDF, JPG, PNG. Taille maximale : 10 Mo.') }}</p>
                                    @error('file')
                                        <p style="color: #d9534f; font-size: 13px; margin-top: 4px;" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <a href="javascript:void(0)" onclick="this.closest('form').submit()"
                                        style="-webkit-appearance:none;text-decoration:none;display:inline-block;background:var(--c-primary, #0B7285);color:#fff;padding:10px 24px;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;">
                                        {{ __('Envoyer la demande') }}
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
