{{-- 2026-05-06 #158 : Partial DRY pour les champs WSD (auto-link glossaire). --}}
{{-- Réutilisé par Dictionary admin + Acronyms admin + tout futur module avec auto-link. --}}
{{-- Variables attendues (via @include) : --}}
{{--   $currentStrategy : string|null (la stratégie actuelle, défaut 'loose') --}}
{{--   $currentAliases  : array|null (les aliases actuels, défaut null) --}}
{{-- Usage : @include('core::partials.admin.wsd-fields', ['currentStrategy' => $term->match_strategy ?? null, 'currentAliases' => $term->aliases ?? null]) --}}

@php
    $strategy = old('match_strategy', $currentStrategy ?? 'loose');
    $aliasesText = old('aliases', is_array($currentAliases ?? null) ? implode(PHP_EOL, $currentAliases) : '');
@endphp

<div class="mb-3">
    <label for="match_strategy" class="form-label">Stratégie de correspondance</label>
    <select id="match_strategy" name="match_strategy" class="form-select">
        <option value="loose" @selected($strategy === 'loose')>Permissif (insensible casse)</option>
        <option value="partial_case_sensitive" @selected($strategy === 'partial_case_sensitive')>Casse partielle (1re lettre tolérante)</option>
        <option value="case_sensitive" @selected($strategy === 'case_sensitive')>Casse stricte</option>
        <option value="exact_phrase" @selected($strategy === 'exact_phrase')>Phrase exacte</option>
        <option value="never_auto" @selected($strategy === 'never_auto')>Jamais auto-link</option>
    </select>
    <div class="form-text">Définit comment les occurrences dans le texte sont détectées automatiquement.</div>
    @error('match_strategy')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="aliases" class="form-label">Alias</label>
    <textarea id="aliases" name="aliases" class="form-control" rows="4" placeholder="Une variation par ligne. Ex:&#10;Tokens&#10;tokenisation&#10;tokenization">{{ $aliasesText }}</textarea>
    <div class="form-text">Variations héritées de la même définition. Pluriels et casses alternatives, une par ligne.</div>
    @error('aliases')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
