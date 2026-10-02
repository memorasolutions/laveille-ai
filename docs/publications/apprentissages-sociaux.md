# Apprentissages - contenu et réseaux sociaux de laveille.ai

> But : voir ce qui attire, garder des notes, ajuster nos astuces. Mis à jour mensuellement.
> Chaque apprentissage confirmé remonte dans les skills `/publier` (rédaction, visuel) et
> `/article` (formats, banc d'essai). Ce fichier est la mémoire vivante de « ce qui marche ».

## Méthode - comment on mesure (et le piège à éviter)

Le mauvais réflexe serait de regarder « combien de personnes ont vu tel post Facebook » : le
portail et Facebook ne donnent pas de portée fiable par publication (métriques de page seulement,
plusieurs mortes - mesuré, « personne ne voit les publications »). On mesure donc ce que la
publication RAMÈNE chez nous.

- **Acquisition du site** (qui vient, d'où) : GA4, propriété **500300528** (module Analytics :
  `ga4_daily`). Outil ad hoc : `mcp__ga4__ga4_traffic_sources` / `ga4_top_pages`.
- **Visibilité en recherche** (quelles requêtes nous trouvent) : GSC (`gsc_daily`).
- **Par publication** : le signal fiable = **clics veille.la + sessions GA4 via UTM**, jointes au
  registre `docs/publications/registre-publications.csv`. Condition : appliquer le lien court
  `veille.la` + UTM sur CHAQUE publication (standard déjà dans `/publier`). Regrouper ensuite par
  **type de post** (carrousel, image simple, actu, article) et **par réseau**.
- **Cadence** : un rollup **mensuel**, pas une obsession post-par-post (aligné sur le banc d'essai
  mensuel de `/article`). Tant que le volume reste faible, l'analyse se fait avec les outils GA4
  existants; on n'automatise (commande dédiée dans le module Analytics) que si le rollup manuel
  devient pénible - anti-sur-ingénierie.

## Ce qu'on sait déjà (point de départ, GA4 sur 30 jours au 2026-10-02)

Rapport détaillé : `docs/rapports/2026-10-02-acquisition-sources-laveille.html`.

- **SEO Google et ChatGPT/AEO = nos deux meilleurs canaux de qualité** (sessions longues, faible
  rebond). C'est là que se jouent les visiteurs engagés.
- **Facebook** : du volume (~95 sessions/mois) mais faible portée et presque tout non balisé -
  GA4 ne peut pas dire QUELLE publication l'amène tant que le veille.la + UTM n'est pas posé.
- **LinkedIn** : ~0 (2 sessions en 30 jours).
- **Format** (recherche Perplexity 2026-10-02) : la vulgarisation qui FAIT GAGNER DU TEMPS ou
  CALME UNE INQUIÉTUDE précise, en **carrousel** ou **vidéo courte**, bat les annonces d'outils.
  Le plus gros levier inexploité = l'**infolettre** (audience possédée, hors algorithme).

## Décision du 2026-10-02 : pas de nouveau réseau, approfondir l'infolettre

Oracles consultés : Perplexity (recherche) + Codex (réfutation, via Hermes), CONVERGENTS. **Non consultés : Gemini, ChatGPT, claude.ai (sessions navigateur déconnectées)** - le panel complet reste à refaire si besoin, mais la décision tient sur 2 oracles + la donnée.

- **Ne PAS ajouter de réseau social dans les 90 jours.** « Être partout » est incohérent pour une personne seule; le levier est d'approfondir ce qui attire déjà et de le convertir.
- **Canal à approfondir : l'INFOLETTRE** (audience possédée, hors algorithme, hebdomadaire tenable en solo). Promesse précise : « chaque semaine, un usage d'IA testé et expliqué en français pour le Québec ». Convertir les visites SEO/AEO/Teams déjà acquises.
- **Piste candidate (Codex), à tester petit** : une chronique prête à republier chez des relais québécois (chambres de commerce, bibliothèques, associations enseignantes) - un conseil, un outil gratuit, un lien balisé. Exploite une distribution existante (cohérent avec le signal Teams), sans production vidéo.
- **Correction (Codex)** : ne pas dire que SEO/AEO « dominent » - ils montrent le meilleur SIGNAL de qualité sur de petits nombres, sans conversion mesurée. Mesurer inscriptions et clics vers les outils par heure investie avant de conclure.

## Registre d'apprentissages (append-only, une ligne par constat vérifié)

| Date | Canal | Type de post | Observé (preuve) | À répéter / à éviter |
|---|---|---|---|---|
| 2026-10-02 | Tous | - | Point de départ : SEO/AEO dominent la qualité; Facebook basse portée et non balisé; LinkedIn nul (GA4 30 j, #2798) | **Poser veille.la + UTM sur CHAQUE post** pour rendre le social attribuable; approfondir l'infolettre |
| 2026-10-02 | Stratégie | - | 2 oracles (Perplexity + Codex) convergent : ne pas ajouter de réseau | Approfondir l'INFOLETTRE; tester une chronique chez des relais québécois |

## Comment ce fichier change nos astuces

Un format qui attire (mesuré) **deux fois** devient une règle inscrite dans `/publier` ou
`/article`. Un format qui échoue **deux fois** est écarté, avec son motif. Une divergence entre
réseaux n'est pas du bruit : elle dit où mettre l'effort. Le but n'est jamais de publier plus,
c'est de publier ce qui ramène des gens vers les outils et le site.
