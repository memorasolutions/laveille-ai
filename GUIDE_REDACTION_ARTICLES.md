# Guide de rédaction des articles — La veille de Stef

> Note globale du projet pour garantir l'uniformité éditoriale.
> Optimisé pour le SEO, l'AEO (Answer Engine Optimization) et le GEO (Generative Engine Optimization) — état de l'art mai 2026.
> Toute personne (ou IA) qui rédige un article sur laveille.ai suit ce guide.

---

## Règle absolue : citer les sources à la fin de CHAQUE article, en tout temps

Sans exception. Chaque article se termine par une section **« Sources »** listant les références utilisées.

- Minimum 3 sources externes fiables (médias reconnus, sites officiels, études, documentation primaire).
- **Format : chaque source DOIT être un lien cliquable `<a href>` vers l'URL officielle (en nouvel onglet), pas du texte brut.** Titre de la source + lien + date de consultation si pertinent. (Règle ultra importante, ne jamais oublier : une source non cliquable n'a aucune valeur SEO/AEO ni pour le lecteur.)
- Privilégier les sources primaires officielles : sites gouvernementaux (.gouv, quebec.ca), institutions (umontreal.ca, commission.europa.eu), documents officiels, plutôt que des blogs secondaires.
- Les sources renforcent l'EEAT (fiabilité) et sont un signal de citation majeur pour les moteurs génératifs (ChatGPT, Perplexity, Gemini, Google AI Overviews).
- Même un article d'opinion ou un retour d'expérience cite ses sources (outils mentionnés, données chiffrées, faits rapportés).

Exemple de bloc de fin :

```
## Sources

- Anthropic, « Constitutional AI » (anthropic.com), consulté en mai 2026
- Google Search Central, documentation E-E-A-T (developers.google.com)
- Étude interne MEMORA, automatisation PME (données 2026)
```

---

## 1. ADN éditorial (ne jamais s'en écarter)

| Marqueur | Règle |
|---|---|
| **Ouverture** | Toujours une analogie concrète du quotidien (le grille-pain, la salle de classe). On rend l'abstrait tangible dès la première ligne. |
| **Ton** | Vulgarisation experte. On s'adresse au lecteur (« vous »). Aucun jargon sans traduction immédiate. |
| **Ancrage Québec** | Au moins un exemple québécois par article (PME, école, gouvernement, réalité locale). |
| **Titre** | Un hook à tension ou curiosité, jamais un titre plat. Idéalement formulé comme une question ou un paradoxe. |
| **Longueur** | **1 200 à 1 500 mots (5 à 7 min de lecture). Cible révisée mai 2026 : les lecteurs trouvaient les articles trop longs.** Ne dépasser 1 800 mots que pour un vrai dossier de fond, rarement. La densité prime toujours sur le volume. |
| **Guillemets** | Guillemets français « » pour les citations (standard maison confirmé sur les articles existants), avec espace insécable interne. Jamais de guillemets droits dans le texte. |
| **Signature** | Chaque article est signé Stéphane Lapointe, avec lien vers la bio (EEAT). |
| **Opinion** | Au moins un point de vue tranché ou une donnée propriétaire (ce que l'IA ne peut pas générer seule). |

---

## 1bis. Règles typographiques strictes (audit obligatoire avant publication)

Non négociables. Un script d'audit doit confirmer zéro violation.

- **Jamais de tiret cadratin —.** Utiliser une virgule, des parenthèses ou deux phrases. (Piège : les modèles IA en remettent systématiquement, vérifier après chaque génération.)
- **Espace insécable AVANT** les deux-points, point-virgule, point d'interrogation et d'exclamation : `:` `;` `?` `!`. Et autour des guillemets : après « et avant ».
- **Aucun espace avant le point ni la virgule** (erreur fréquente des IA qui sur-appliquent la règle d'espacement).
- **Apostrophes cohérentes** : un seul type dans tout l'article, pas de mélange droite/courbe.
- **Pas de majuscules à l'américaine** (title case). Casse de phrase française. Les acronymes gardent leurs majuscules (IA, RGPD, CHUM).
- **Ne jamais commencer** un texte ou un paragraphe par « Bien sûr » ou « Certainement ».

## 1ter. Écriture authentiquement humaine (anti-détection IA, best practices mai 2026)

- **Varier la longueur des phrases** : ~30 % courtes (≤ 12 mots), ~50 % moyennes, ~15 % longues avec incises. Un texte « trop propre » (tout entre 14 et 20 mots) sonne IA.
- **Marqueurs personnels** : opinions assumées (« à mon avis », « je le dis comme je le pense »), micro-anecdotes, doutes honnêtes.
- **Expressions idiomatiques** occasionnelles (« pavé dans la mare », « prendre le taureau par les cornes »).
- **Bannir les tics IA** : pas de « Tout d'abord / Ensuite / Enfin » mécaniques, pas de « Dans cet article, nous allons », pas de « Il est important de noter que », superlatifs vagues à doser.
- **Transitions orales et variées** : « Concrètement », « Le point clé ici », « Sauf que », plutôt que des connecteurs scolaires répétés.

---

## 2. Structure citable (cœur AEO / GEO 2026)

1. **H1 = la question** que le lecteur poserait à une IA (ex. « C'est quoi le MCP ? »).
2. **Réponse courte de 40 à 50 mots juste sous le H1**, avant l'analogie. C'est le passage repris par les AI Overviews et Perplexity.
3. **Un tableau ou une liste** dans le premier tiers de l'article (les LLM citent en priorité le contenu structuré).
4. **5 à 8 H2 narratifs**, maximum 3 H3 par H2. Jamais de saut de niveau (H1 → H3 interdit).
5. **Paragraphes mono-idée**, 3 à 4 lignes maximum.
6. **Section FAQ** de 3 à 5 questions en fin d'article (réponses de 40 à 80 mots).
7. **Section Sources** tout à la fin (voir règle absolue ci-dessus).

Ordre recommandé : H1 → réponse courte → analogie d'ouverture → corps (H2/H3 + tableau) → FAQ → Sources.

---

## 3. EEAT (décisif depuis 2026)

- Auteur réel nommé (Stéphane Lapointe) + lien vers une page bio détaillée. Google pénalise les auteurs anonymes ou génériques.
- Date de publication ET date de mise à jour visibles.
- 3+ sources externes citées (section Sources obligatoire).
- Au moins un élément de preuve : test personnel, capture, chiffre réel, étude de cas.

---

## 4. Balisage technique (Schema.org)

- `BlogPosting` (headline, description, author, datePublished, dateModified, image, mainEntityOfPage).
- `FAQPage` pour la section FAQ.
- `Person` pour l'auteur (avec `sameAs` vers les profils publics).
- Tout ce qui est en JSON-LD doit exister en HTML visible.
- Helpers disponibles dans le projet : `lv_jsonld_blog_posting()`, `lv_jsonld_faq_page()`, `lv_jsonld_breadcrumb()`, `lv_jsonld_author_from_profile()`.

---

## 5. Formats d'articles (notés sur 100)

Notes pondérées : SEO durable (25) + citabilité AEO/GEO (25) + alignement ADN (20) + ROI/effort (15) + différenciation (15).

| Format | Note | Quand l'utiliser |
|---|---|---|
| **Guide explicatif evergreen** (« C'est quoi X ? ») | 94 | Format pilier. SEO durable maximal, AEO parfait. À privilégier. |
| **Étude de cas chiffrée** (« Comment j'ai fait X, résultats ») | 90 | EEAT + donnée propriétaire = très cité par les LLM. Effort élevé. |
| **Analyse-opinion à hook** (« Le pari fou de... ») | 88 | Différenciation et partage. Moins durable (lié à l'actualité). |
| **Série tutoriel perso** (« J'ai créé mon IA », 1-2-3) | 86 | EEAT en or (expérience vécue), fidélise. Audience plus niche. |
| **Comparatif / listicle outils** | 83 | Tableaux ultra-citables (AEO). À mettre à jour régulièrement. |
| **Concentré hebdomadaire** | 72 | Rendez-vous de fidélité, mais SEO périssable. Ne pas en faire le moteur principal. |

Mix recommandé : ossature Guide + Étude de cas, saupoudrée d'Analyse-opinion, Série pour la fidélité, Concentré comme rendez-vous léger.

---

## 6. Checklist avant publication

- [ ] H1 formulé comme une question
- [ ] Réponse courte de 40 mots juste sous le H1
- [ ] Analogie d'ouverture + 1 exemple Québec + ton « vous »
- [ ] 5 à 8 H2 narratifs, 1 tableau ou liste dans le premier tiers
- [ ] 2 300+ mots, paragraphes de 3 à 4 lignes
- [ ] 1 donnée ou avis propriétaire (introuvable ailleurs)
- [ ] FAQ de 3 à 5 questions (40 à 80 mots par réponse)
- [ ] **Section Sources en fin d'article (3+ références) — OBLIGATOIRE**
- [ ] Signé Stéphane + bio liée + date de mise à jour
- [ ] Schema BlogPosting + FAQPage + Person
- [ ] 2 à 3 liens internes + tags
- [ ] Méta-titre < 60 caractères, méta-description 140 à 160 caractères

---

## 7. Après publication

- IndexNow (déjà actif) pour indexation instantanée Bing / Yandex.
- Vérifier le rendu RSS et les cartes sociales (OG image dynamique générée automatiquement).
- Mettre à jour `dateModified` à chaque révision de fond.
