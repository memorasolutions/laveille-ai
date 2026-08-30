# Sauvegarde meta['title'] - 15 articles Concentré hebdo (Modules/Blog)

Date de la sauvegarde : 28 aout 2026, 14h23 Québec (18h23 UTC).

Contexte : `Article::getSeoTitleAttribute()` (Modules/Blog/app/Models/Article.php, ligne 263) lit
`meta['title']`, jamais `meta['seo_title']`. Les seeders passés avaient écrit dans `meta['seo_title']`,
une clé morte que l'accesseur ne lit jamais - la balise `<title>` publiée reste donc le titre brut.
Correctif : écrire `meta['title']` sur 15 articles, en préservant toutes les autres clés de `meta`.

Capture obtenue via un script PHP éphémère en LECTURE SEULE (jeton expirant comparé par
`hash_equals`, auto-suppression en fin d'exécution), déposé via `cpanel_file_write` et invoqué en
POST sur `https://laveille.ai/`, AVANT toute écriture. Les données ci-dessous sont l'état RÉEL de
production au moment de la capture (colonne `meta` telle quelle, JSON brut, clé par clé).

## Vérification préalable (les 15 id sont bien des articles Concentré)

Pour chacun des 15 id : `category_id = 6`, `category_slug = le-concentre`,
`category_name = LE CONCENTRÉ`, `status = published`, `deleted_at = null`. La plage de dates du
titre/slug actuel correspond exactement à la plage de dates du nouveau titre cible, pour chacun
des 15 id (vérifié ligne par ligne ci-dessous). Aucune anomalie, aucun id retiré du lot.

## État AVANT écriture (colonne `meta` complète, par id)

### id 2 - « Le concentré de la dernière semaine – 26 janvier 2026 au 1 février 2026 »
slug : `le-concentre-de-la-derniere-semaine-26-janvier-2026-au-1-fevrier-2026`
meta AVANT : `null`

### id 3 - « Le concentré de la dernière semaine – 19 janvier 2026 »
slug : `le-concentre-de-la-derniere-semaine-19-janvier-2026`
meta AVANT : `null`

### id 5 - « Le concentré de la dernière semaine – 12 janvier 2026 »
slug : `le-concentre-de-la-derniere-semaine-12-janvier-2026`
meta AVANT : `null`

### id 54 - « Le concentré de la semaine : 12 avril au 19 avril 2026 »
slug : `le-concentre-de-la-semaine-12-avril-au-19-avril-2026`
meta AVANT :
```json
{
    "seo_title": "Concentré IA semaine du 12 au 19 avril 2026 — laveille.ai",
    "meta_description": "Découvrez le concentré IA de la semaine du 12 au 19 avril 2026 : agents autonomes, Adobe créatif, GPT-5.4 Pro et débats éthiques. Restez à jour, nous autres au Québec!"
}
```

### id 55 - « Concentré IA — semaine du 14 au 21 avril 2026 »
slug : `concentre-ia-semaine-14-21-avril-2026`
meta AVANT :
```json
{
    "seo_title": "Concentré IA — semaine du 14 au 21 avril 2026 | laveille.ai",
    "meta_description": "8 actualités clés de l'IA : Claude Design, Kimi K2.6, Qwen 3.6, Copilot Studio mode agent, GPT-5.4 Mini, Gemini 3.1 Flash, Claude Mythos et réglementation. Analyse pour PME québécoises."
}
```

### id 56 - « Concentré IA — semaine du 27 avril au 3 mai 2026 »
slug : `concentre-ia-semaine-27-avril-3-mai-2026`
meta AVANT :
```json
{
    "seo_title": "Concentré IA — semaine du 27 avril au 3 mai 2026 | laveille.ai",
    "meta_description": "GPT-5.5 agentique, empoisonnement d'agents IA, capex Big Tech 630 G$, FDA essais cliniques, David Silver, gouvernance APRA et ASML +36 %. 8 actualités IA décryptées pour les stratèges québécois."
}
```

### id 57 - « Concentré IA — semaine du 4 au 10 mai 2026 »
slug : `concentre-ia-semaine-4-10-mai-2026`
meta AVANT :
```json
{
    "seo_title": "Concentré IA — semaine du 4 au 10 mai 2026 | laveille.ai",
    "meta_description": "Course aux puces TPU, agents IA autonomes, gouvernance éthique et cybersécurité : panorama des avancées clés en IA pour la semaine du 4 au 10 mai 2026."
}
```

### id 58 - « Concentré IA — semaine du 11 au 17 mai 2026 »
slug : `concentre-ia-semaine-11-17-mai-2026`
meta AVANT (seul id avec une clé `title` déjà présente - à l'ANCIENNE valeur) :
```json
{
    "title": "Concentré IA — semaine du 11 au 17 mai 2026",
    "description": "Concentré IA 11-17 mai 2026 : commerce conversationnel (Amazon, OpenAI), alignement et sûreté (Anthropic, Palisade), gouvernance UE (DMA, CNIL), pénurie mondiale de talents."
}
```

### id 60 - « Concentré IA : la semaine du 18 au 24 mai 2026 »
slug : `concentre-ia-semaine-18-24-mai-2026`
meta AVANT : `null`

### id 61 - « Concentré IA — semaine du 25 au 31 mai 2026 »
slug : `concentre-ia-semaine-25-31-mai-2026`
meta AVANT : `null`

### id 62 - « Concentré IA — semaine du 1 au 7 juin 2026 »
slug : `concentre-ia-semaine-1-7-juin-2026`
meta AVANT : `null`

### id 63 - « Concentré IA — semaine du 8 au 14 juin 2026 »
slug : `concentre-ia-semaine-8-14-juin-2026`
meta AVANT : `null`

### id 64 - « Concentré IA - semaine du 29 juin au 5 juillet 2026 »
slug : `concentre-ia-semaine-29-5-juillet-2026`
meta AVANT :
```json
{
    "seo_title": "Concentré IA semaine du 29 juin au 5 juillet 2026 - laveille.ai",
    "meta_description": "Concentré IA de la semaine du 29 juin au 5 juillet 2026 : Claude Sonnet 5, Fable 5, Gemini partout chez Google, régulation santé et éducation par IA."
}
```

### id 65 - « Concentré IA - semaine du 6 au 12 juillet 2026 »
slug : `concentre-ia-semaine-6-12-juillet-2026`
meta AVANT :
```json
{
    "seo_title": "Concentré IA hebdo : 20 faits marquants du 6 au 12 juillet 2026",
    "meta_description": "Cette semaine : AWS et la recherche pharmaceutique, Claude Code avec navigateur, Apple investit 30 milliards $, Meta désactive Muse Image."
}
```

### id 66 - « Concentré IA — semaine du 13 au 19 juillet 2026 »
slug : `concentre-ia-semaine-13-19-juillet-2026`
meta AVANT : `null`

## Plan d'écriture (rappel)

Pour chaque id ci-dessus, ajout ou écrasement de la seule clé `meta['title']` avec la valeur cible
fournie par le mandat de cette session. Toutes les autres clés (`seo_title`, `meta_description`,
`description`) sont préservées telles quelles. Aucune touche à `title`, à `slug`, ni à aucune autre
colonne de la table `articles`. `meta['seo_title']` n'est jamais retirée (clé morte, inoffensive).

## Retour arrière

Pour annuler, réappliquer exactement les blocs « meta AVANT » ci-dessus sur les id correspondants
(remplacement complet de la colonne `meta` par la valeur montrée : JSON tel quel, `null` reste
`null`), puis vider le cache de réponses (se produit automatiquement à l'enregistrement, via
`Article::boot()` -> `static::saved(fn () => ResponseCache::clear())`).
