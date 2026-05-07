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

                            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(11,114,133,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0B7285" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    </div>
                                    <h4 style="margin: 0; font-family: var(--f-heading, inherit); font-weight: 700; color: var(--c-dark, #1a1a2e); font-size: 1rem;">{{ __('Vos droits sur vos donnees personnelles') }}</h4>
                                </div>
                                <p style="font-size: 13px; color: #6b7280; margin-bottom: 12px;">{{ __('Conformement aux lois applicables (RGPD, Loi 25, LPRPDE), vous pouvez exercer les droits suivants :') }}</p>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin-bottom: 16px;">
                                    @foreach($request_types as $type => $label)
                                        <div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f8f9fa; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--c-dark, #1a1a2e);">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0B7285" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            {{ $label }}
                                        </div>
                                    @endforeach
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 16px; padding-top: 12px; border-top: 1px solid #f3f4f6; font-size: 13px; color: #6b7280;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0B7285" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        {{ __('Delai de reponse : :days jours', ['days' => $response_delay_days]) }}
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0B7285" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        {{ __('DPO :') }} <strong>{{ $company['dpo_name'] }}</strong> — <a href="mailto:{{ $company['dpo_email'] }}" style="color: var(--c-primary, #064E5A);">{{ $company['dpo_email'] }}</a>
                                    </div>
                                </div>
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
                                        style="-webkit-appearance:none;text-decoration:none;display:inline-block;background:var(--c-primary, #064E5A);color:#fff;padding:10px 24px;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;">
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
