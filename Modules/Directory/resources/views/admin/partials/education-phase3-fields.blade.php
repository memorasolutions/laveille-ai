<section aria-labelledby="education-phase3-heading" style="margin-top:2rem;">
    <div style="background:#fff;border-radius:8px;padding:1.75rem;border:1px solid #e5e7eb;">
        <h2 id="education-phase3-heading" style="color:#374151;margin:0 0 1rem;font-size:1.125rem;font-weight:700;">
            Caracteristiques techniques et conformite (Phase 3)
        </h2>

        {{-- 1. Remise academique --}}
        <div style="margin-bottom:1.5rem;">
            <label style="display:flex;align-items:center;gap:8px;color:#374151;font-weight:600;cursor:pointer;min-height:44px;">
                <input
                    type="checkbox"
                    name="is_academic_discount"
                    value="1"
                    {{ old('is_academic_discount', $tool->is_academic_discount) ? 'checked' : '' }}
                    style="width:20px;height:20px;cursor:pointer;"
                >
                Remise academique disponible (etudiants/enseignants)
            </label>
            @error('is_academic_discount')
                <p role="alert" style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- 2. Niveaux education couverts --}}
        <fieldset style="border:1px solid #e5e7eb;padding:1rem;border-radius:6px;margin-bottom:1.5rem;">
            <legend style="color:#374151;font-weight:600;padding:0 0.5rem;">Niveaux education couverts</legend>

            @php
                $currentLevels = old('education_level', $tool->education_level ?? []);
                $levelOptions = [
                    'primaire'   => 'Primaire',
                    'secondaire' => 'Secondaire',
                    'superieur'  => 'Superieur',
                ];
            @endphp

            @foreach ($levelOptions as $value => $label)
                <label style="display:flex;align-items:center;gap:8px;color:#374151;font-weight:500;cursor:pointer;min-height:44px;">
                    <input
                        type="checkbox"
                        name="education_level[]"
                        value="{{ $value }}"
                        {{ is_array($currentLevels) && in_array($value, $currentLevels) ? 'checked' : '' }}
                        style="width:20px;height:20px;cursor:pointer;"
                    >
                    {{ $label }}
                </label>
            @endforeach

            @error('education_level')
                <p role="alert" style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</p>
            @enderror
            @error('education_level.*')
                <p role="alert" style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</p>
            @enderror
        </fieldset>

        {{-- 3. Conformite vie privee --}}
        <div style="margin-bottom:1.5rem;">
            <label for="privacy_compliance" style="display:block;color:#374151;font-weight:600;margin-bottom:0.5rem;">
                Conformite vie privee
            </label>
            <input
                type="text"
                id="privacy_compliance"
                name="privacy_compliance"
                list="privacy_compliance_list"
                maxlength="100"
                value="{{ old('privacy_compliance', $tool->privacy_compliance) }}"
                style="width:100%;min-height:44px;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:6px;color:#374151;font-size:1rem;box-sizing:border-box;"
            >
            <datalist id="privacy_compliance_list">
                <option value="RGPD">
                <option value="COPPA">
                <option value="FERPA">
                <option value="HIPAA">
                <option value="Loi 25">
            </datalist>
            <p style="color:#374151;font-size:13px;margin-top:4px;">Norme de conformite (RGPD, COPPA, FERPA, etc.). Max 100 caracteres.</p>
            @error('privacy_compliance')
                <p role="alert" style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- 4. Courbe d apprentissage --}}
        <div style="margin-bottom:1.5rem;">
            <label for="learning_curve" style="display:block;color:#374151;font-weight:600;margin-bottom:0.5rem;">
                Courbe d apprentissage
            </label>
            <select
                id="learning_curve"
                name="learning_curve"
                style="width:100%;min-height:44px;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:6px;color:#374151;font-size:1rem;box-sizing:border-box;background:#fff;"
            >
                <option value="">-- Non defini --</option>
                @php
                    $currentCurve = old('learning_curve', $tool->learning_curve);
                    $curveLabels = [
                        1 => '1 - Tres intuitive',
                        2 => '2 - Facile',
                        3 => '3 - Moderee',
                        4 => '4 - Avancee',
                        5 => '5 - Expert',
                    ];
                @endphp
                @foreach ($curveLabels as $val => $lbl)
                    <option value="{{ $val }}" {{ $currentCurve !== null && (int) $currentCurve === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            @error('learning_curve')
                <p role="alert" style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- 5. Acces API --}}
        <div style="margin-bottom:1.5rem;">
            <label style="display:flex;align-items:center;gap:8px;color:#374151;font-weight:600;cursor:pointer;min-height:44px;">
                <input
                    type="checkbox"
                    name="has_api_access"
                    value="1"
                    {{ old('has_api_access', $tool->has_api_access) ? 'checked' : '' }}
                    style="width:20px;height:20px;cursor:pointer;"
                >
                Acces API public disponible
            </label>
            @error('has_api_access')
                <p role="alert" style="color:#b91c1c;font-size:13px;margin-top:4px;">{{ $message }}</p>
            @enderror
        </div>

    </div>
</section>
