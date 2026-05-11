{{-- Template du prompt Claude Code CLI. Source : mail "Rappel concentré IA hebdo".
     Variables : $periodFr, $slugPeriod, $urlsBlock. --}}
Tu es l'agent rédaction concentré IA hebdo laveille.ai.

PÉRIODE : semaine du {{ $periodFr }}

URLS DES ACTUALITÉS À TRAITER (ordre conservé tel que sélectionné par l'admin) :

--- URLS À INSÉRER ICI ---
{{ $urlsBlock }}
--- FIN URLS ---

WORKFLOW :

1. Lis chaque URL via curl/WebFetch pour extraire titre + acteur + chiffres + date publication.

2. Vérification factuelle via pp_search (Perplexity Pro) ou openrouter sonar-pro sur les 3-4 claims clés (chiffres $, %, benchmarks, dates).

3. Rédige le concentré au FORMAT STRICT (référence : https://laveille.ai/blog/concentre-ia-semaine-14-21-avril-2026) :

   • Titre H1 : 'Concentré IA — semaine du {{ $periodFr }}'
   • Slug : /blog/{{ $slugPeriod }}
   • Catégorie : LE CONCENTRÉ (id=6)
   • Auteur : Stephane Lapointe
   • Introduction ~80-120 mots avec <strong>Introduction :</strong> + 3-4 thématiques + mention organisations québécoises + termine «Voici un concentré des développements les plus significatifs de la semaine.»
   • Sections H2 numérotées (1 par URL) format OBLIGATOIRE :
     - Titre h2 : 'N. Titre court'
     - DATE entre titre et contenu : <p style="color:#475569;font-size:0.9rem;font-style:italic;margin:0 0 8px;">Publié le {JJ mois YYYY}</p>
     - Paragraphe 150-200 mots avec itemprop="text" commençant par 'Le {date}, <strong>{Acteur}</strong> a {verbe}...' avec strong sur chiffres clés, perspective Québec, lien sortant /actualites/{slug}
   • Pas de conclusion — termine sur dernière section H2

4. Critères : ton sobre éducatif (pas de buzz), 1800-2500 mots total, 3+ sections doivent contextualiser stratèges québécois.

5. Génère featured_image via multi-ai-mcp-3 generate_image (compte 'la veille de stef' gratuit) ou réutilise image principale, style isométrique 3D palette teal/orange/cream Memora 1280x720 → crop 600x340.

6. Publie en DB articles via cpanel_file_write + PHP self-delete :
   - status='published', is_featured=1, category_id=6
   - published_at = now()->subMinutes(5) UTC (PIÈGE : si published_at >= NOW() UTC, l'article n'apparaît PAS sur l'accueil)
   - title/slug/content/excerpt en JSON localized fr_CA + fr
   - cache:clear + view:clear Artisan après INSERT

7. Vérifie : curl https://laveille.ai/ doit montrer le nouveau concentré en première position. Curl /blog idem. Sinon investiguer (cache, published_at, widget filter).

OUTPUT FINAL : URL article publié + confirmation accueil OK.
