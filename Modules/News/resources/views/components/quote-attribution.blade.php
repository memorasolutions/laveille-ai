{{--
    Attribution complète sous une citation verbatim tirée d'une source externe.

    Conformité article 29.2 de la Loi sur le droit d'auteur canadienne (L.R.C. 1985, ch.
    C-42) : l'utilisation équitable aux fins de communication des nouvelles exige la
    mention de la SOURCE et, lorsque cette information figure dans la source, du NOM DE
    L'AUTEUR. Composant réutilisable UNIQUE de ce rendu (DRY) - aucun autre gabarit ne doit
    reconstruire ce balisage.

    Ordre d'information imposé : journaliste (si connu) → média → date → lien original.

    Repli conforme si l'auteur manque : la mention du média seule reste valide au sens de
    l'article 29.2, qui n'exige le nom de l'auteur que s'il figure dans la source. Aucun
    tiret, virgule ou séparateur orphelin n'apparaît alors.

    Props :
        article  (\Modules\News\Models\NewsArticle) requis

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@props(['article'])

@php
    $nwMediaName = $article->source->name ?? __('Source');
    $nwAuthorName = trim((string) ($article->author ?? ''));

    // Journaliste + média sur un seul segment ("Jean Untel, The Verge") - repli sur le
    // média seul quand l'auteur manque, sans virgule orpheline.
    $nwWhoLabel = $nwAuthorName !== '' ? $nwAuthorName.', '.$nwMediaName : $nwMediaName;

    $nwDateLabel = $article->pub_date ? format_date($article->pub_date) : null;
    $nwOriginalUrl = $article->resolved_url ?: $article->url;

    $nwSegments = array_values(array_filter([$nwWhoLabel, $nwDateLabel], fn ($segment) => filled($segment)));
@endphp
<cite class="nw-quote-attribution">- {{ implode(' · ', $nwSegments) }}@if($nwOriginalUrl) · <a href="{{ $nwOriginalUrl }}" target="_blank" rel="noopener" class="nw-quote-source-link">{{ __('Voir l\'article original') }} &rarr;</a>@endif</cite>
