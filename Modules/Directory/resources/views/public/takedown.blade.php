@extends(fronttheme_layout())
@section('title', __('Demande de retrait de contenu') . ' - ' . config('app.name'))
@section('meta_description', __('Formulaire de demande de retrait de contenu (droit d\'auteur, marque, données personnelles).'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Demande de retrait')])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Demande de retrait de contenu') }}</h2>
                        <p style="color: var(--c-text-secondary);">
                            {{ __('Ce formulaire s’adresse aux titulaires de droits (droit d\'auteur, marque) ou aux personnes concernées par leurs données personnelles. Toute demande sera traitée de bonne foi après vérification de votre identité et de la légitimité de votre requête. Une déclaration sous peine de responsabilité est requise.') }}
                        </p>

                        @if(session('success'))
                            <div class="alert alert-success" role="alert" style="border-radius:8px;margin-bottom:20px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger" role="alert" style="border-radius:8px;margin-bottom:20px;">
                                <ul style="margin:0;padding-left:18px;">
                                    @foreach($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('directory.takedown.store') }}">
                            @csrf

                            @if($tool)
                                <input type="hidden" name="directory_tool_id" value="{{ $tool->id }}">
                            @endif

                            <div style="display:none;" aria-hidden="true">
                                <label>{{ __('Laissez ce champ vide') }}
                                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                                </label>
                            </div>

                            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:24px;">

                                <fieldset>
                                    <legend style="font-weight:700;color:var(--c-dark);font-size:16px;margin-bottom:12px;">{{ __('Contenu visé') }}</legend>
                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="target_url" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('URL du contenu visé') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <input type="url" id="target_url" name="target_url" required
                                            value="{{ old('target_url', $tool ? route('directory.show', $tool->slug) : '') }}"
                                            class="form-control {{ $errors->has('target_url') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;height:44px;"
                                            aria-describedby="target_url_help">
                                        <p id="target_url_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('L\'URL exacte du contenu visé.') }}
                                        </p>
                                        @error('target_url')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend style="font-weight:700;color:var(--c-dark);font-size:16px;margin-bottom:12px;">{{ __('Vos coordonnées') }}</legend>
                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="requester_name" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Nom complet') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <input type="text" id="requester_name" name="requester_name" required
                                            value="{{ old('requester_name') }}"
                                            class="form-control {{ $errors->has('requester_name') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;height:44px;"
                                            aria-describedby="requester_name_help">
                                        <p id="requester_name_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Vous ou la personne autorisée.') }}
                                        </p>
                                        @error('requester_name')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="requester_email" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Adresse courriel') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <input type="email" id="requester_email" name="requester_email" required
                                            value="{{ old('requester_email') }}"
                                            class="form-control {{ $errors->has('requester_email') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;height:44px;"
                                            aria-describedby="requester_email_help">
                                        <p id="requester_email_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Pour vous contacter.') }}
                                        </p>
                                        @error('requester_email')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="requester_organization" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Organisation (facultatif)') }}
                                        </label>
                                        <input type="text" id="requester_organization" name="requester_organization"
                                            value="{{ old('requester_organization') }}"
                                            class="form-control {{ $errors->has('requester_organization') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;height:44px;"
                                            aria-describedby="requester_organization_help">
                                        <p id="requester_organization_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Nom de votre organisation, si applicable.') }}
                                        </p>
                                        @error('requester_organization')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="requester_role" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Qualité') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <select id="requester_role" name="requester_role" required
                                            class="form-control {{ $errors->has('requester_role') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;height:44px;"
                                            aria-describedby="requester_role_help">
                                            <option value="">{{ __('—') }}</option>
                                            <option value="titulaire" {{ old('requester_role') == 'titulaire' ? 'selected' : '' }}>{{ __('Titulaire des droits') }}</option>
                                            <option value="mandataire" {{ old('requester_role') == 'mandataire' ? 'selected' : '' }}>{{ __('Mandataire') }}</option>
                                            <option value="avocat" {{ old('requester_role') == 'avocat' ? 'selected' : '' }}>{{ __('Avocat') }}</option>
                                            <option value="autre" {{ old('requester_role') == 'autre' ? 'selected' : '' }}>{{ __('Autre') }}</option>
                                        </select>
                                        <p id="requester_role_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Votre rôle par rapport aux droits invoqués.') }}
                                        </p>
                                        @error('requester_role')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend style="font-weight:700;color:var(--c-dark);font-size:16px;margin-bottom:12px;">{{ __('Droit invoqué') }}</legend>
                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="right_type" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Type de droit') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <select id="right_type" name="right_type" required
                                            class="form-control {{ $errors->has('right_type') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;height:44px;"
                                            aria-describedby="right_type_help">
                                            <option value="">{{ __('—') }}</option>
                                            <option value="droit_auteur" {{ old('right_type') == 'droit_auteur' ? 'selected' : '' }}>{{ __('Droit d\'auteur') }}</option>
                                            <option value="marque" {{ old('right_type') == 'marque' ? 'selected' : '' }}>{{ __('Marque de commerce') }}</option>
                                            <option value="vie_privee" {{ old('right_type') == 'vie_privee' ? 'selected' : '' }}>{{ __('Vie privée / données personnelles') }}</option>
                                            <option value="autre" {{ old('right_type') == 'autre' ? 'selected' : '' }}>{{ __('Autre') }}</option>
                                        </select>
                                        <p id="right_type_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Le type de droit que vous invoquez.') }}
                                        </p>
                                        @error('right_type')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="right_details" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Preuves ou détails') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <textarea id="right_details" name="right_details" required rows="3"
                                            class="form-control {{ $errors->has('right_details') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;"
                                            placeholder="{{ __('Preuve : n° d\'enregistrement de marque, lien vers l\'œuvre originale, date de création…') }}"
                                            aria-describedby="right_details_help">{{ old('right_details') }}</textarea>
                                        <p id="right_details_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Fournissez des éléments justificatifs précis.') }}
                                        </p>
                                        @error('right_details')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend style="font-weight:700;color:var(--c-dark);font-size:16px;margin-bottom:12px;">{{ __('Description') }}</legend>
                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label for="description" style="display:block;font-weight:600;color:#111827;font-size:14px;margin-bottom:4px;">
                                            {{ __('Explication') }} <span style="color:var(--c-danger);">*</span>
                                        </label>
                                        <textarea id="description" name="description" required rows="4"
                                            class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                                            style="border-radius:8px;box-shadow:none;"
                                            aria-describedby="description_help">{{ old('description') }}</textarea>
                                        <p id="description_help" class="help" style="font-size:13px;color:var(--c-text-muted);margin-top:4px;">
                                            {{ __('Expliquez clairement pourquoi ce contenu devrait être retiré.') }}
                                        </p>
                                        @error('description')
                                            <span style="color:var(--c-danger);font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </fieldset>

                                <fieldset>
                                    <legend style="font-weight:700;color:var(--c-dark);font-size:16px;margin-bottom:12px;">{{ __('Déclaration') }}</legend>
                                    <div class="form-group" style="margin-bottom:14px;">
                                        <label style="display:flex;align-items:flex-start;gap:8px;" for="declaration_accepted">
                                            <input type="checkbox" id="declaration_accepted" name="declaration_accepted" value="1" required
                                                {{ old('declaration_accepted') ? 'checked' : '' }}
                                                style="margin-top:4px;">
                                            <span style="font-size:14px;color:var(--c-text-secondary);">
                                                {{ __('Je déclare de bonne foi, sous peine de responsabilité, être titulaire des droits invoqués (ou son représentant autorisé), et que les informations fournies sont exactes.') }}
                                            </span>
                                        </label>
                                        @error('declaration_accepted')
                                            <span style="color:var(--c-danger);font-size:12px;display:block;margin-top:4px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </fieldset>

                                <button type="submit" class="ct-btn ct-btn-primary" style="border-radius:8px;height:44px;padding:0 24px;">
                                    {{ __('Envoyer la demande') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
