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

4. Critères rédactionnels :
   • Ton sobre éducatif (pas de buzz, pas de hype, pas de superlatifs).
   • Calibration mots ADAPTATIVE selon nombre d'URLs :
     - 3-7 URLs   → 150-200 mots/section, total ~1000-1500 mots (concentré court).
     - 8-12 URLs  → 175-200 mots/section, total ~1800-2500 mots (sweet spot historique).
     - 13-20 URLs → 130-160 mots/section, total ~2200-3200 mots (concentré dense). Évite la redondance.
   • Intro 80-120 mots qui annonce 3-4 thématiques majeures observées (regroupements naturels).
   • 3+ sections doivent contextualiser stratèges québécois — MAIS VARIE les angles à chaque fois :
     - Loi 25 / protection RP (au plus 4 sections)
     - MILA / IVADO recherche
     - RECITcn / éducation numérique
     - PME et secteur privé québécois
     - Santé / cliniques universitaires
     - Municipalités / gouvernement
     - Marché FR-CA / industrie locale
     Règle anti-répétition : JAMAIS la formule « Au Québec, où la Loi 25... » 2 sections consécutives.
     Diversifie : « Pour les institutions québécoises... », « Les chercheurs du MILA... »,
     « Cette annonce intéresse les développeurs francophones... », etc.
   • Lien sortant /actualites/{slug} OBLIGATOIRE dans chaque paragraphe, varie le texte d'ancre :
     « Lecture complète », « Analyse complète », « Détails sur la fiche », « Voir le contexte »,
     « Décryptage », « Approfondir », « Source originale ».

5. Génère featured_image — workflow hiérarchique :

   PRIORITÉ 1 : Gemini Pro Playwright (compte user gratuit, IDÉAL) :
   • Workflow MCP playwright (réf MEMORY feedback_miniatures_gemini_playwright) :
     a) browser_navigate → https://gemini.google.com/app
     b) browser_click sur "Créer une image"
     c) browser_type le prompt image (cf format ci-dessous)
     d) browser_wait_for ~35s
     e) browser_evaluate canvas.toDataURL('image/png') → base64
     f) sauvegarder .b64 → cwebp + sips → public/storage/news/concentres/{slug}.webp

   PRIORITÉ 2 (FALLBACK si Playwright KO / session expirée / pas de browser dispo) :
   • RÉUTILISER l'image featured du dernier concentré publié (query articles WHERE category_id=6 ORDER BY published_at DESC LIMIT 1)
   • Set featured_image = même path que le concentré précédent
   • Ajouter tag « image-fallback » dans tags JSON pour audit
   • NE PAS bloquer la publication pour absence d'image dédiée

   INTERDIT : multi-ai-mcp generate_image (1min.ai à 0 crédits constant).

   STYLE STRICT charte Memora : isométrique 3D, fond clair gris-bleu, palette navy #064E5A + orange #9A2A06, AUCUN texte, aucun logo de marque tiers.

   Le prompt image DOIT SYNTHÉTISER VISUELLEMENT les thèmes dominants de l'ENSEMBLE des sections (3-5 acteurs/concepts qui reviennent : OpenAI + Google + Anthropic + agentic + GPU → composition isométrique avec puces/serveurs + avatars IA stylisés + interfaces conversationnelles + réseaux interconnectés).

   Output : master 1280x720 → crop 600x340.

   PROMPT IMAGE TYPE (adapter selon thèmes) :
   "Isometric 3D illustration, light gray-blue background, navy #064E5A + orange #9A2A06 palette, no text. Compose a scene synthesizing [3-5 themes from sections]: interconnected AI servers, conversational interface bubbles, model nodes, data streams, satellite small icons of [actors detected]. Style: flat shading, soft shadows, Memora editorial illustration, professional editorial feel, 1280x720."

6. Publie en DB articles via cpanel_file_write + PHP self-delete :
   - status='published', is_featured=1, category_id=6
   - published_at = \Carbon\Carbon::now('America/Toronto')->subMinutes(5)
     PIÈGE TZ MAJEUR : les datetimes sont stockés en TZ applicative (America/Toronto), pas UTC.
     Si tu utilises now('UTC')->subMinutes(5), Laravel l'interprétera comme Toronto-time → article dans le futur de 4-5h → exclu du scope published() → 404.
     TOUJOURS now('America/Toronto') ou simplement now() (qui utilise app.timezone par défaut).
   - title/slug/content/excerpt en JSON localized fr_CA + fr (Spatie Translatable)
   - cache:clear + view:clear Artisan après INSERT

7. Vérifie : curl https://laveille.ai/ doit montrer le nouveau concentré en première position. Curl /blog idem. Sinon investiguer (cache, published_at, widget filter).

OUTPUT FINAL : URL article publié + confirmation accueil OK.
