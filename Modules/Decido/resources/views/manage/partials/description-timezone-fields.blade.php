{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Option E (skill /100 hors gate) : description + fuseau horaire, partagés entre les 2
     formulaires dédiés (DRY). Destiné à être inclus DANS le <details> "Plus d'options" de chaque
     formulaire (jamais visible par défaut - la recherche pp_search juillet 2026 confirme que la
     réduction du nombre de champs VISIBLES au départ compte plus que le nombre total de champs). --}}
<div class="mb-4">
    <label for="description" class="form-label">Description (optionnel)</label>
    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
</div>

<div class="mb-4">
    <label for="timezone" class="form-label">Fuseau horaire</label>
    <select id="timezone" name="timezone" class="form-select">
        <option value="America/Toronto" {{ old('timezone', 'America/Toronto') === 'America/Toronto' ? 'selected' : '' }}>Toronto (HNE/HAE)</option>
        {{-- SIM_decido0717 (2026-07-17) : "America/Montreal" a été retiré de la base IANA tzdata en
             2014 (fusionné dans America/Toronto, mêmes règles HNE/HAE) - ce n'est plus un identifiant
             timezone_identifiers_list() valide sur PHP moderne, donc rejeté par la règle Laravel
             'timezone'. On GARDE cette valeur historique côté formulaire (old() round-trip inchangé,
             zéro risque de régression sur FEAT_010) ; le contrôleur (store(), PollManageController)
             normalise désormais cet alias vers America/Toronto avant validation. --}}
        <option value="America/Montreal" {{ old('timezone') === 'America/Montreal' ? 'selected' : '' }}>Montréal (HNE/HAE)</option>
        <option value="Europe/Paris" {{ old('timezone') === 'Europe/Paris' ? 'selected' : '' }}>Paris (HNEC/HAEC)</option>
    </select>
    @error('timezone')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
