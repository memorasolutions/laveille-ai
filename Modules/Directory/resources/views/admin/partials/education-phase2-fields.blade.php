@php
    $audiences = [
        'K12' => 'Primaire et secondaire',
        'higher_ed' => 'Enseignement supérieur',
        'district' => 'Commission scolaire/district',
        'homeschool' => 'École à la maison',
        'individual_teacher' => 'Enseignant individuel',
    ];
    $selectedAudiences = old('education_target_audience', $tool->education_target_audience ?? []) ?? [];
@endphp

<section aria-labelledby="education-phase2-heading">
    <h2 id="education-phase2-heading" style="color: #1a1a1a; font-size: 1.35rem; font-weight: 700; margin-bottom: 0.25rem;">
        Plan Éducation — Phase 2
    </h2>
    <p style="color: #6b7280; font-size: 0.97rem; margin-bottom: 1.25rem;">
        Métadonnées avancées pour la différenciation des outils éducatifs.
    </p>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.75rem;">

        {{-- 1. Type de remise éducation --}}
        <div style="margin-bottom: 1.5rem;">
            <label for="education_discount_type" style="display: block; font-weight: 600; color: #1a1a1a; margin-bottom: 0.4rem;">
                Type de remise éducation
            </label>
            <select
                id="education_discount_type"
                name="education_discount_type"
                aria-describedby="education_discount_type_helper"
                style="display: block; width: 100%; min-height: 44px; padding: 0.5rem 0.75rem; font-size: 1rem; color: #1a1a1a; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;"
            >
                <option value="" @selected(old('education_discount_type', $tool->education_discount_type) === null || old('education_discount_type', $tool->education_discount_type) === '')>- Non défini -</option>
                <option value="teacher_free" @selected(old('education_discount_type', $tool->education_discount_type) === 'teacher_free')>Gratuit pour enseignants</option>
                <option value="teacher_discount" @selected(old('education_discount_type', $tool->education_discount_type) === 'teacher_discount')>Remise enseignants</option>
                <option value="institution_discount" @selected(old('education_discount_type', $tool->education_discount_type) === 'institution_discount')>Remise institution</option>
                <option value="quote_only" @selected(old('education_discount_type', $tool->education_discount_type) === 'quote_only')>Sur devis</option>
                <option value="university_license" @selected(old('education_discount_type', $tool->education_discount_type) === 'university_license')>Licence universitaire</option>
                <option value="student_discount" @selected(old('education_discount_type', $tool->education_discount_type) === 'student_discount')>Remise étudiants</option>
            </select>
            <p id="education_discount_type_helper" style="color: #6b7280; font-size: 0.875rem; margin-top: 0.3rem;">
                Sélectionnez le type de tarification éducative offert par cet outil.
            </p>
            @error('education_discount_type')
                <p role="alert" style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>

        {{-- 2. Public cible éducation --}}
        <div style="margin-bottom: 1.5rem;">
            <fieldset style="border: none; margin: 0; padding: 0;">
                <legend style="display: block; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem;">
                    Public cible éducation
                </legend>
                <p id="education_target_audience_helper" style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">
                    Cochez tous les publics visés par l'offre éducative de cet outil.
                </p>
                @foreach($audiences as $value => $audienceLabel)
                    <div style="margin-bottom: 0.4rem; display: flex; align-items: center; min-height: 44px;">
                        <input
                            type="checkbox"
                            id="education_target_audience_{{ $value }}"
                            name="education_target_audience[]"
                            value="{{ $value }}"
                            aria-describedby="education_target_audience_helper"
                            @checked(in_array($value, $selectedAudiences))
                            style="width: 20px; height: 20px; margin-right: 0.6rem; accent-color: #1a1a1a; cursor: pointer;"
                        >
                        <label for="education_target_audience_{{ $value }}" style="color: #1a1a1a; font-size: 1rem; cursor: pointer;">
                            {{ $audienceLabel }}
                        </label>
                    </div>
                @endforeach
                @error('education_target_audience')
                    <p role="alert" style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
                @error('education_target_audience.*')
                    <p role="alert" style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </fieldset>
        </div>

        {{-- 3. Vérification institutionnelle requise --}}
        <div style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; min-height: 44px;">
                <input
                    type="hidden"
                    name="education_verification_required"
                    value="0"
                >
                <input
                    type="checkbox"
                    id="education_verification_required"
                    name="education_verification_required"
                    value="1"
                    aria-describedby="education_verification_required_helper"
                    @checked(old('education_verification_required', $tool->education_verification_required))
                    style="width: 20px; height: 20px; margin-right: 0.6rem; accent-color: #1a1a1a; cursor: pointer;"
                >
                <label for="education_verification_required" style="font-weight: 600; color: #1a1a1a; font-size: 1rem; cursor: pointer;">
                    Vérification institutionnelle requise
                </label>
            </div>
            <p id="education_verification_required_helper" style="color: #6b7280; font-size: 0.875rem; margin-top: 0.3rem;">
                L'utilisateur doit prouver son statut éducatif.
            </p>
            @error('education_verification_required')
                <p role="alert" style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>

        {{-- 4. URL officielle page éducation --}}
        <div style="margin-bottom: 1.5rem;">
            <label for="education_official_url" style="display: block; font-weight: 600; color: #1a1a1a; margin-bottom: 0.4rem;">
                URL officielle page éducation
            </label>
            <input
                type="url"
                id="education_official_url"
                name="education_official_url"
                value="{{ old('education_official_url', $tool->education_official_url) }}"
                maxlength="500"
                placeholder="https://outil.com/education"
                aria-describedby="education_official_url_helper"
                style="display: block; width: 100%; min-height: 44px; padding: 0.5rem 0.75rem; font-size: 1rem; color: #1a1a1a; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;"
            >
            <p id="education_official_url_helper" style="color: #6b7280; font-size: 0.875rem; margin-top: 0.3rem;">
                Adresse complète de la page dédiée à l'offre éducative de l'outil.
            </p>
            @error('education_official_url')
                <p role="alert" style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>

        {{-- 5. Dernière vérification --}}
        <div style="margin-bottom: 0;">
            <label for="education_last_checked_at" style="display: block; font-weight: 600; color: #1a1a1a; margin-bottom: 0.4rem;">
                Dernière vérification
            </label>
            <input
                type="date"
                id="education_last_checked_at"
                name="education_last_checked_at"
                value="{{ old('education_last_checked_at', $tool->education_last_checked_at?->format('Y-m-d')) }}"
                aria-describedby="education_last_checked_at_helper"
                style="display: block; width: 100%; min-height: 44px; padding: 0.5rem 0.75rem; font-size: 1rem; color: #1a1a1a; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;"
            >
            <p id="education_last_checked_at_helper" style="color: #6b7280; font-size: 0.875rem; margin-top: 0.3rem;">
                Date du dernier contrôle de ces informations.
            </p>
            @error('education_last_checked_at')
                <p role="alert" style="color: #b91c1c; font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>

    </div>
</section>
