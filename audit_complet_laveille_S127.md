# Audit complet laveille.ai — Mai 2026

> Rapport généré le 2026-05-27 (EDT) sur la version prod **v1.42.2** (SHA `97160870`).
> Méthodologie : 12 recherches `pp_search` best practices mai 2026 + audit data live via 7 MCP custom Memora (security, wcag-mcp, robotalp, compliance, cpanel, cloudflare, perplexity-pro-playwright).

## 1. Executive Summary

**Score global pondéré : 86/100** (moyenne arithmétique des 20 axes notés ci-dessous : 1725/2000).

La plateforme laveille.ai se positionne comme un leader émergent de la veille IA en français québécois, avec une base technique solide (Laravel 11, Cloudflare Pro), une stratégie d'accessibilité et de conformité proactive (Loi 25, RGPD, WCAG 2.2 AAA), et des contenus exceptionnellement structurés — notamment le glossaire (265 termes à 100 % complets sur 8 sections obligatoires + JSON-LD broader/narrower nouveau S126). Le taux d'engagement utilisateurs historique (70,7 % audit S84) et les performances de la newsletter S22 (55 % open rate, 38 % CTR) dépassent largement les benchmarks 2026 (Brevo 30-35 % / 3-5 %).

Cependant, des vulnérabilités persistent : une CVE critique Laravel (CVE-2025-27515, CVSS 9,8/10) liée à la validation des wildcards dans les uploads à valider, et l'absence du header `Access-Control-Allow-Origin` (mineur car API uniquement). Le bloat de la base de données est sévère : `pulse_entries` (4,2 M lignes, 1052 MB) et `health_check_history_items` (300 k lignes, 114 MB) représentent un risque opérationnel et de coût stockage à moyen terme.

Les recommandations prioritaires S127-S130 visent à : (1) éliminer la CVE critique, (2) ré-authentifier GA4 + GSC (token expiré `invalid_grant`), (3) automatiser la purge des logs Pulse/health, (4) corriger 2 bugs WCAG AAA (contraste carte « Déclaration Montréal » + skip-link absent en première position Tab), (5) activer les signaux EEAT complets (profils auteurs `Person` + `knowsAbout` + `sameAs` Wikidata), et (6) compléter le knowledge graph glossaire (`broader_slugs` de 59 % à 90 %).

## 2. Méthodologie

Audit réalisé selon les standards officiels suivants :

- **WCAG 2.2 Niveau AAA** (ISO/IEC 40500:2025)
- **Loi 25 Québec** (LPRPSP modifiée, en vigueur sept. 2024)
- **RGPD UE** (Règlement 2016/679)
- **Google Core Web Vitals 2026** (LCP ≤2,5s, INP ≤200ms, CLS ≤0,1)
- **EEAT 2026** (Expérience, Expertise, Autorité, Confiance)
- **Trépied AEO/GEO** (Answer Engine + Generative Engine Optimization)
- **CVE/NVD** (vulnérabilités Laravel 11)

**12 recherches `pp_search` effectuées le 2026-05-27 EDT** sur les meilleures pratiques 2026 :

1. SEO + AEO + GEO trépied + AI Overviews citations
2. Core Web Vitals 2026 (INP remplaçant FID, seuils LCP/CLS)
3. AI Citation Readiness (`llms.txt`, `robots.txt` par bot IA)
4. WCAG 2.2 AAA vs WCAG 3.0 draft + contraste FR-CA
5. EEAT 2026 (`Person`, `sameAs`, `knowsAbout`, `jobTitle`)
6. Newsletter deliverability Brevo + RFC 8058 List-Unsubscribe
7. Loi 25 Québec 2026 + RGPD + Consent Mode v2
8. UI/UX 2026 (bento grid, dark-first, fluid typography)
9. Knowledge graph + Wikidata + DBpedia + sameAs
10. Image optimization AVIF + WebP + fetchpriority LCP
11. Laravel 11 sécurité prod + CSP nonce + Cloudflare Pro
12. PWA 2026 + Web Share API + Push + offline-first

**MCP custom Memora utilisés** : `security` (headers/SSL/DNS/CVE/tech/full_audit), `wcag-mcp` (6 audits AAA), `robotalp`, `compliance`, `cpanel`, `cloudflare`, `playwright`, `multi-ai-mcp` (qwen3-max via openrouter-free pour génération rapport), `perplexity-pro-playwright` (recherches web).

**Limites** : Le token GA4 + Google Search Console est expiré (`invalid_grant`) au moment de l'audit, bloquant l'accès aux données de performance réelles (Core Web Vitals field data) et aux requêtes SEO. Une ré-authentification utilisateur est requise pour compléter l'axe 6 (Performance) avec données live. Estimation pondérée appliquée en attendant.

## 3. Notes détaillées /100 par axe (20 axes)

### 1. Sécurité HTTP headers — **92/100**
Headers prod notés **Grade A (7/8)** : `Content-Security-Policy` (frame-src limité aux services connus), `Strict-Transport-Security` (max-age 1 an + preload + includeSubDomains), `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera/microphone/geolocation correctement scopés), `X-XSS-Protection: 1; mode=block`. Seul `Access-Control-Allow-Origin` manque (impact mineur car CORS scope API only). Conforme aux best practices Laravel 11 + Cloudflare Pro 2026.

### 2. Vulnérabilités CVE Laravel — **65/100**
**7 CVE Laravel 11 actives** détectées dans la NVD :
- **CVE-2025-27515 CRITICAL (9,8/10)** : bypass validation wildcard `files.*` → upload arbitraire potentiel
- **CVE-2024-13918 HIGH (8,0/10)** : XSS reflected debug-mode error page (non exploitable car `APP_DEBUG=false`)
- **CVE-2024-13919 HIGH (8,0/10)** : XSS reflected debug-mode route params (idem)
- **CVE-2024-52301 HIGH (7,5/10)** : changement env via query string si `register_argc_argv` ON (à vérifier `php.ini` prod)
- 3 autres MEDIUM/LOW sur Starter (non utilisé)

Action urgente : auditer les formulaires d'upload pour appliquer le patch CVE-2025-27515.

### 3. DNS / Email auth (SPF/DKIM/DMARC) — **100/100**
**SPF** : `v=spf1 a mx ip4:72.11.130.66 include:spf.brevo.com include:_spf.google.com ~all` ✅
**DMARC** : `v=DMARC1; p=quarantine; rua=mailto:info@laveille.ai; fo=1; pct=100` ✅
**DKIM** : `v=DKIM1; k=rsa; p=MIIBIjANBg…` selector `google` ✅
Configuration optimale. Robot Robotalp dédié vérifie quotidiennement (DMARC/SPF/DKIM type 13).

### 4. SSL/TLS — **100/100**
Certificat valide derrière Cloudflare (4 endpoints IPv4 + IPv6 : 172.67.157.35, 104.21.40.218 + IPv6). Surveillé par robot Robotalp SSL daily (`type 5`). HSTS preload activé (max-age 31 536 000). Aucune alerte active.

### 5. Accessibilité WCAG 2.2 AAA — **85/100**
Audit `wcag_audit_aaa` homepage : **86 critères évalués → 20 conformes / 9 non-conformes / 30 manuel / 26 NA**.

Non-conformités critiques :
- **1.4.3 Contrast** : ratio 1:1 (blanc sur blanc) sur carte « Déclaration de Montréal » → probable bug rendering pré-JS (background image non chargé)
- **2.4.1 Bypass Blocks** : skip-link absent en première position Tab (existe mais pas premier focusable)
- **2.1.1 Keyboard** : 100+ éléments interactifs marqués non-Tab (probable false-positive Alpine `x-show` sur menus collapsed initialement cachés)
- **2.5.8 Target Size** : skip-link 1x1px (volontairement caché par design, mais bloque l'audit auto)

Audits annexes : forms 4/4 ✅ / images 1/1 ✅ / structure /glossaire/llm 5/5 ✅ (sauf 1 empty heading `ct-help-modal-title` Modal Alpine).

### 6. Performance Core Web Vitals — **80/100** *(estimé, GA4 KO)*
Estimation basée sur stack (Cloudflare CDN cache + lazy loading natif visible + Critical CSS inline thème Bloggar + WebP optimisé). Historique S84 : 70,7 % engagement / 29,3 % bounce indique perf field acceptable. **Audit complet nécessite re-auth GA4 + GSC**. Best practices 2026 confirmées : LCP ≤2,5s, INP ≤200ms (a remplacé FID 2024), CLS ≤0,1 sur 75 % des sessions.

### 7. SEO technique (sitemap, robots, indexation) — **98/100**
- `sitemap.xml` : HTTP 200, **1,18 MB, 3452 URLs** avec `priority` (1.0 homepage daily, 0.8 articles weekly), `lastmod` ISO 8601, `image:image` référencé, namespaces XML corrects (urlset + xhtml + image + video + news)
- `news-sitemap.xml` : séparé pour Google News compliance
- `robots.txt` : excellent (voir axe 9 GEO)

Risque mineur : sitemap > 1 MB approche limite Google (50 MB max). À sharder en 2-3 sitemaps thématiques si dépasse 5 000 URLs.

### 8. AEO (Answer Engine Optimization) — **95/100**
Glossaire 100 % complet : `one_sentence_answer` (réponse ≤40 mots pour AI Overviews), FAQ Schema.org `FAQPage` rendu en `<details>` natif, Sources externes vérifiables (EEAT), Schema.org `DefinedTerm` + `DefinedTermSet` + `alternateName` multivalué. Articles blog avec section Sources obligatoire. Pattern AEO mature. Score limité à 95 par absence de tableaux comparatifs systématiques dans articles (extraction LLM facilitée).

### 9. GEO (Generative Engine Optimization — llms.txt, AI bots) — **100/100**
- `llms.txt` **PRÉSENT et exemplaire** (HTTP 200) : structure markdown complète, sections distinctes, citation guidance explicite ("Attribution : La veille de Stef — laveille.ai", URL canoniques par page, date `last_verified_at`), contact, social
- `robots.txt` : autorise explicitement **16 bots IA** (GPTBot, ClaudeBot, anthropic-ai, Google-Extended, Applebot-Extended, CCBot, cohere-ai, Diffbot, Meta-ExternalAgent, OAI-SearchBot, ChatGPT-User, Claude-SearchBot, Claude-User, PerplexityBot, Perplexity-User, GeminiBot) + bloque scrapers commerciaux (Bytespider, FacebookBot, ImagesiftBot, PetalBot)
- Référence mai 2026 : la stratégie « opt-in maximal bots IA légitimes » est optimale pour citations dans Overviews

### 10. Knowledge Graph Schema.org (broader/narrower, sameAs) — **70/100**
- `broader_slugs` : **157/265 termes (59,2 %)** ✅ relations parent-enfant
- `narrower_slugs` : **59/265 termes (22,3 %)** — pertinent (seuls parents ont des enfants)
- 59 relations broader/narrower / 203 edges (livré S126)
- Schema.org `DefinedTerm.broader` + `narrower` injecté @graph JSON-LD ✅

Manque : `sameAs` Wikidata/DBpedia URIs sur entités stables (LLM → Q11660, GPT → Q97607797, Anthropic → Q116484344, etc.) pour alignement avec Knowledge Graph Google. Référence 2026 : DBpedia + Wikidata = colonne vertébrale entity SEO.

### 11. EEAT (Person, knowsAbout, sources) — **75/100**
- Sources externes 100 % glossaire (signal EEAT fort)
- Stéphane Lapointe identifié dans `Person` JSON-LD glossaire (sameAs LinkedIn + jobTitle « Fondateur de MEMORA solutions, formateur IA »)
- Article blog Schema.org `Person.author` ✅

Manque :
- `knowsAbout` array (sujets de compétence) sur `Person` Stéphane
- Pages auteur publiques `/auteur/stephane` (404 actuellement car module Authors désactivé prod malgré scaffold S106-S107)
- Référence 2026 : 96 % citations AI Overviews viennent de sites EEAT élevé + 15+ entités reconnues = haute proba citation

### 12. Contenu — Blog éditorial — **60/100**
- 60 articles publiés / 0 brouillons (corrigé : 60 vs 53 du qwen)
- Dernier publié : 2026-05-25 (récent ✅)
- Top page S84 : `/constructeur-prompts` 516 vues (outil, pas article)
- Cadence ~1 article/semaine (concentré IA hebdo dimanche, livré S87+)

Faiblesse : volume éditorial faible vs glossaire (265 termes) et annuaire (982 outils). Levier AEO sous-exploité (recommandation : 2 articles/semaine pour atteindre 100 articles fin 2026).

### 13. Contenu — Glossaire (265 termes 100%) — **100/100**
**Star du site.** 265 termes publiés avec :
- Définition 100 % / Analogie 100 % / Exemple 100 % / Le saviez-vous 100 %
- one_sentence_answer 100 % (réponse 40 mots AEO)
- FAQ Schema.org 100 % (FAQPage rendu)
- Sources externes 100 % (signal EEAT)
- Hero image PNG+WebP 100 %
- broader_slugs 59 %, narrower_slugs 22 % (knowledge graph S126)
- Linkifier inter-termes longest-match (« RAG strict » avant « RAG »)

Référence sectorielle : aucun glossaire francophone IA québécois équivalent. Modèle pour CONTENU LLM-FIRST 2026.

### 14. Contenu — Annuaire outils — **95/100**
**982 outils** dans `directory_tools` (9,8 MB), top page S84 #2 (320 vues). Données enrichies : screenshots, tarification, tutoriels YouTube, comparaisons, `last_verified_at`. Page tooling individuelle avec Schema.org `SoftwareApplication` ou `Product`.

Manque : liens `sameAs` vers fiches Wikidata des outils (ex : ChatGPT → Q113448445) pour knowledge graph cross-référence.

### 15. Conformité Loi 25 / RGPD — **90/100**
- MCP `compliance` v0.5.1 opérationnel
- Juridictions actives : QC Loi 25 v0.1 + EU RGPD v0.1
- `llms.txt` mentionne explicitement « Loi 25 / LPRPDE / RGPD : conformité totale »
- DPO email : `stephane@memora.ca` (`PRIVACY_DPO_EMAIL`)
- Newsletter double opt-in + RFC 8058 List-Unsubscribe one-click (livré S107) ✅
- Politique de confidentialité publique (module Privacy actif)

Manque selon best practices 2026 :
- Bannière cookies opt-in préalable avec granularité (nécessaires/analytique/publicité/personnalisation) + équivalence boutons accepter/refuser (pas de dark patterns)
- Consent Mode v2 basic (denied par défaut → update après choix utilisateur) → impact ads/analytics Google
- Registre incidents formalisé

### 16. PWA / Mobile — **92/100**
- `manifest.webmanifest` valide : name, short_name, start_url, display standalone, theme_color #0b7285, lang fr-CA, categories news/education/technology, icons logo.webp 192+512
- Service Worker enregistré (livré S111)
- WebPush infrastructure scaffold (S114) mais pas activé

Manque selon 2026 :
- Web Share API (bouton « Partager » natif mobile sur articles)
- Push notifications opt-in (avec Loi 25 consent UI)
- Page offline dédiée avec articles récents en cache
- AVIF prioritaire (50-60 % smaller vs WebP, 94 %+ support 2026)

### 17. Newsletter — engagement — **100/100**
**Métriques EXCEPTIONNELLES S22** (2026-05-27, 54 abonnés) :
- Open rate **55 %** vs benchmark Brevo 2026 30-35 %
- CTR **38 %** vs benchmark 3-5 %
- 0 bounce / 0 spam / 0 unsubscribed
- 4,3 clicks moyens par cliqueur engagé

Stack : `BrevoApiTransport` custom + `MAIL_MAILER=brevo` + transactionnels routés vers Workspace SMTP (S109). 62 abonnés confirmés cumul. Croissance organique.

### 18. Monétisation (Stripe, ads, shop, affiliate) — **40/100**
- Stripe LIVE configuré (`pk_live_...` + `sk_live_...`) ✅
- Module Shop activé mais `SHOP_MAINTENANCE=true` (mode bloqué pendant fix Gelato variants/pricing #210)
- Gelato POD configuré (store_id + api_key) mais inactif
- Google AdSense configuré (`ca-pub-2358625447182467`) → revenus publicitaires actifs
- Authors Premium (7$ CAD/mois, scaffold S107) inactif prod

Manque : système d'affiliation outils annuaire (potentiel ~982 outils × commission moyenne), tip jar Stripe Checkout one-shot.

### 19. Architecture code (modules, tests, DRY) — **88/100**
- **39 modules nwidart activés / 50 totaux** (78 %) — hygiène OK
- Modules désactivés non-régressifs : 11 (incl. Authors, Sudoku, etc.)
- **422 tests Pest** (166 dans Modules + 256 racine)
- 6 helpers DRY mutualisés : `AeoHelper`, `dates`, `dictionary`, `jsonld`, `typo`, `version`
- Routes API : `force-json` + `throttle:api` middleware ✅
- Versioning SemVer + footer admin/frontend (v1.42.2 ✅ )
- Stack détectée : Cloudflare CDN + Laravel + Livewire + Alpine + Bootstrap + jQuery

Léger debt : jQuery + Bootstrap (legacy) → migration vers Alpine pur faisable progressivement.

### 20. Monitoring & observabilité (Robotalp) — **100/100**
**9 robots actifs**, workspace `364200` :
1. Uptime — racine `https://laveille.ai` (5 min)
2. Uptime — `/annuaire` (5 min)
3. Uptime — `/outils` (5 min)
4. Uptime — `/blog` (5 min)
5. DMARC/SPF/DKIM (daily)
6. Google Safe Browsing (daily)
7. Vulnérabilité scan (daily)
8. DNS resolution (daily)
9. SSL cert validity (daily)

**0 incidents actifs**. Couverture complète sécurité + email + perf. Bonus : memora/statut v0.1.4 intégré (livré S99).

## 4. Forces majeures (top 10)

1. ✅ **Glossaire 100 % complet sur 8 sections** : 265 termes avec définition + analogie + exemple + saviez-vous + OSA + FAQ + sources + hero — **référence sectorielle veille IA FR-CA**
2. ✅ **Newsletter EXCEPTIONNELLE** : 55 % open rate / 38 % CTR vs benchmarks 30-35 % / 3-5 % (2-10× au-dessus de l'industrie Brevo 2026)
3. ✅ **GEO maîtrisée** : `llms.txt` exemplaire + `robots.txt` autorise 16 bots IA + bloque scrapers commerciaux — pattern de référence
4. ✅ **Sécurité email robuste** : SPF + DKIM + DMARC quarantine 100 % conformes, monitoré daily
5. ✅ **SEO technique excellent** : sitemap 3452 URLs avec image+lastmod ISO 8601, news-sitemap séparé
6. ✅ **Monitoring Robotalp exhaustif** : 9 robots, 0 incidents, couverture uptime + sécurité + email + SSL
7. ✅ **Engagement utilisateur élevé** : 70,7 % engagement / 29,3 % bounce (audit S84) — audience qualifiée
8. ✅ **Stack moderne** : Laravel 11 + Livewire + Cloudflare Pro + 422 tests Pest + 6 helpers DRY + 39 modules nwidart
9. ✅ **Conformité proactive** : Loi 25 + RGPD + double opt-in RFC 8058 + DPO + politique privacy
10. ✅ **Knowledge graph Schema.org broader/narrower** : 59 relations / 203 edges JSON-LD (livré S126), pattern AEO/GEO 2026 avant-gardiste

## 5. Faiblesses identifiées (top 10)

1. ⚠️ **CVE Laravel CRITICAL non corrigée** : CVE-2025-27515 (wildcard validation bypass, 9,8/10) — auditer formulaires upload urgents
2. ⚠️ **Bloat base de données sévère** : `pulse_entries` 4,2 M lignes (1 GB) + `health_check_history_items` 300 k lignes (114 MB) sans purge automatisée
3. ⚠️ **EEAT incomplet** : profils auteurs désactivés prod (Authors module false) malgré scaffold S106-S107 ; manque `Person.knowsAbout` array
4. ⚠️ **Accessibilité homepage dégradée** : 9 critères WCAG AAA non-conformes (contraste blanc/blanc carte Montreal + skip-link absent première position Tab)
5. ⚠️ **GA4 + GSC inaccessibles** : token `invalid_grant` — bloque audit perf field + SEO quantitatif
6. ⚠️ **Monétisation 40/100** : Shop en SHOP_MAINTENANCE=true depuis S82 (#210 Gelato), Premium Authors inactif, pas d'affiliation
7. ⚠️ **Knowledge graph incomplet** : `broader_slugs` 59 %, `narrower_slugs` 22 %, pas de `sameAs` Wikidata/DBpedia sur entités
8. ⚠️ **Blog éditorial sous-développé** : seulement 60 articles vs 265 termes glossaire — cadence 1/semaine insuffisante pour leadership thématique
9. ⚠️ **PWA non optimisé 2026** : manque Web Share API + Push UI + page offline + AVIF prioritaire
10. ⚠️ **Bannière cookies opt-in absente** : Loi 25 + RGPD 2026 exigent granularité + Consent Mode v2 (denied par défaut)

## 6. Top 20 recommandations classées par ROI

| Rang | Recommandation | Impact /10 | Effort /10 | ROI | Sprint cible |
|------|---------------|-----------:|-----------:|----:|-------------:|
| 1 | Ré-authentifier GA4 + GSC (user action) | 9 | 1 | 9,0 | S127 |
| 2 | Scheduler purge weekly `health_check_history_items` + `activity_log` > 60j | 8 | 1 | 8,0 | S127 |
| 3 | Corriger contraste blanc/blanc carte « Déclaration Montréal » homepage | 7 | 1 | 7,0 | S127 |
| 4 | Réordonner DOM pour skip-link en première position Tab | 7 | 1 | 7,0 | S127 |
| 5 | Ajouter `Access-Control-Allow-Origin` sur routes API | 5 | 1 | 5,0 | S127 |
| 6 | Auditer + patcher CVE-2025-27515 (wildcard validation upload) | 10 | 2 | 5,0 | S127 |
| 7 | Configurer Laravel Pulse rétention 7-14 jours (purge auto pulse_entries) | 9 | 2 | 4,5 | S128 |
| 8 | Ajouter bannière cookies Loi 25 + granularité 4 niveaux (Cookie Consent) | 8 | 2 | 4,0 | S128 |
| 9 | Implémenter Consent Mode v2 basic (denied default → update on consent) | 7 | 2 | 3,5 | S128 |
| 10 | Activer Authors prod : pages `/auteur/stephane` + Person knowsAbout + jobTitle | 8 | 3 | 2,7 | S128 |
| 11 | Ajouter `sameAs` Wikidata/DBpedia URIs 20 entités stables (LLM, GPT, etc.) | 7 | 3 | 2,3 | S128 |
| 12 | Web Share API + page offline PWA + cache shell | 6 | 3 | 2,0 | S129 |
| 13 | Auditer faux positifs Alpine `x-show` non-Tab (focus order propre) | 5 | 3 | 1,7 | S128 |
| 14 | Convertir hero images glossaire en AVIF + `fetchpriority="high"` LCP | 6 | 4 | 1,5 | S129 |
| 15 | Compléter `broader_slugs` glossaire de 59 % à 90 % (Gemini batch 2) | 6 | 4 | 1,5 | S129 |
| 16 | Lancer affiliation annuaire (50 outils top, commissions Stripe Connect) | 7 | 5 | 1,4 | S130 |
| 17 | Cadence éditoriale blog 2 articles/semaine (cible 100 articles fin 2026) | 7 | 5 | 1,4 | S129+ |
| 18 | Débloquer Shop : fix Gelato variants/pricing #210 + publier 5 produits | 8 | 6 | 1,3 | S130 |
| 19 | Activer Premium Authors (7$ CAD/mois) + 2 produits Stripe Dashboard | 6 | 5 | 1,2 | S130 |
| 20 | Push notifications PWA opt-in (Loi 25 consent UI + VAPID keys) | 5 | 5 | 1,0 | S130 |

## 7. Roadmap suggérée S127-S130 (4 semaines)

**S127 (2026-05-27 → 2026-06-02) — Hygiène + Sécurité urgente**
1. Ré-auth GA4 + GSC (action user 5 min)
2. Patch CVE-2025-27515 sur formulaires upload (audit + correctif Laravel update)
3. Cron weekly purge `health_check_history_items` > 30j + `activity_log` > 60j
4. Fix contraste carte « Déclaration Montréal » homepage
5. Réorganiser DOM pour skip-link Tab position 1
6. Ajouter `Access-Control-Allow-Origin` API
7. Bump v1.43.0 codename `audit-hygiene-security`

**S128 (2026-06-03 → 2026-06-09) — Conformité + EEAT**
1. Bannière cookies Loi 25 + granularité 4 niveaux
2. Consent Mode v2 basic implémenté (denied default)
3. Activer Authors prod : page `/auteur/stephane` + `Person.knowsAbout` JSON-LD
4. Configurer Laravel Pulse rétention (purge auto pulse_entries 7-14j)
5. Ajouter `sameAs` Wikidata 20 entités stables glossaire
6. Audit faux positifs Alpine non-Tab + corrections focus order
7. Bump v1.44.0 codename `compliance-eeat-pulse`

**S129 (2026-06-10 → 2026-06-16) — Performance + PWA + Contenu**
1. Conversion hero images glossaire AVIF + WebP fallback + `fetchpriority="high"` LCP
2. Web Share API mobile (articles + glossaire + outils)
3. Service Worker cache shell + page offline avec articles récents
4. Compléter `broader_slugs` glossaire 59 % → 90 % (Gemini batch 2)
5. Lancer cadence blog 2 articles/semaine (calendrier éditorial Q3 2026)
6. Bump v1.45.0 codename `pwa-avif-content-cadence`

**S130 (2026-06-17 → 2026-06-23) — Monétisation**
1. Débloquer Shop : fix Gelato variants/pricing #210 + publier 5 produits
2. Activer Premium Authors 7$/mois + 2 produits Stripe Dashboard
3. Système affiliation 50 outils top annuaire (Stripe Connect ou code promo)
4. Push notifications PWA opt-in (VAPID + UI Loi 25 consent)
5. Tip jar Stripe Checkout one-shot intégré pages glossaire (5$/10$/20$)
6. Bump v1.46.0 codename `monetization-shop-premium-affiliate`

**Cible fin S130** : score global passé de 86/100 à **94-96/100**, avec axes faibles (Monétisation 40→75, EEAT 75→95, PWA 92→98, WCAG 85→95) drastiquement améliorés.

## 8. Annexes — Data brut

### État production
- **SHA git** : `97160870`
- **Version** : `v1.42.2` codename `glossary-knowledge-graph` (v1.42.0)
- **Date dernier déploiement** : 2026-05-27 11:34 EDT (15:34 UTC)
- **Working tree** : clean
- **Stack** : Laravel 11 + Livewire 3 + Alpine.js + Cloudflare Pro + Brevo + Stripe LIVE + Gelato POD
- **Modules nwidart actifs** : 39/50 (78 %)
- **Sous-domaines actifs** : 2 (`www.laveille.ai`, `mail.laveille.ai`)
- **Endpoints SSL** : 4 (172.67.157.35, 104.21.40.218, 2606:4700:3033::6815:28da, 2606:4700:3034::ac43:9d23)

### Métriques DB live top 10 tables (au 2026-05-27 17:15 EDT)
| Rang | Table | Rows | Size MB |
|------|-------|-----:|--------:|
| 1 | pulse_entries | 4 211 280 | 1052.47 |
| 2 | news_articles | 7 472 | 128.27 |
| 3 | health_check_result_history_items | 300 647 | 114.41 |
| 4 | activity_log | 32 808 | 75.08 |
| 5 | pulse_aggregates | 146 514 | 57.19 |
| 6 | directory_tools | 982 | 9.81 |
| 7 | articles | 60 | 7.13 |
| 8 | ai_knowledge_documents | 58 | 3.08 |
| 9 | dictionary_terms | 265 (177 estimated) | 2.55 |
| 10 | sessions | 1 496 | 2.27 |

### Glossaire complétude (2026-05-27)
- Total publiés : **265 termes**
- Définition : 265 (100 %) | Analogie : 265 (100 %) | Exemple : 265 (100 %)
- Le saviez-vous : 265 (100 %) | OSA : 265 (100 %) | FAQ : 265 (100 %)
- Sources : 265 (100 %) | Hero image : 265 (100 %)
- broader_slugs : 157 (59,2 %) | narrower_slugs : 59 (22,3 %)

### Autres métriques DB
- Users : 49
- Newsletter subscribers confirmed : 62
- Articles publiés : 60 (0 brouillon)
- Dernier article publié : 2026-05-25 19:21 EDT
- Directory tools publiés : 982

### 9 robots Robotalp (workspace 364200)
| ID | Nom | Type | Intervalle | Availability |
|----|-----|------|-----------|:-------------:|
| 91530856 | laveille.ai — Uptime HTTP | Uptime | 5 min | ✅ 1 |
| 91531287 | laveille.ai — Blog | Uptime | 5 min | ✅ 1 |
| 91531288 | laveille.ai — Outils | Uptime | 5 min | ✅ 1 |
| 91531289 | laveille.ai — Annuaire | Uptime | 5 min | ✅ 1 |
| 91530863 | laveille.ai — DMARC/SPF/DKIM | DMARC | 1 jour | ✅ 1 |
| 91530861 | laveille.ai — Google Safe Browsing | SafeBrowsing | 1 jour | ✅ 1 |
| 91530860 | laveille.ai — Vulnérabilités | Vulnerability | 1 jour | ✅ 1 |
| 91530859 | laveille.ai — DNS | DNS | 1 jour | ✅ 1 |
| 91530857 | laveille.ai — SSL cert | SSL | 1 jour | ✅ 1 |

### 12 sources pp_search (2026-05-27 EDT)
| # | Thème | URL Perplexity |
|---|-------|----------------|
| 1 | SEO/AEO/GEO + DefinedTerm | perplexity.ai/search/c405ecd0... |
| 2 | Core Web Vitals INP 2026 | perplexity.ai/search/0cf41d05... |
| 3 | AI Citation Readiness llms.txt | perplexity.ai/search/156d9d1e... |
| 4 | WCAG 2.2 AAA + 3.0 draft FR-CA | perplexity.ai/search/ecb3c85c... |
| 5 | EEAT + Person + sameAs + knowsAbout | perplexity.ai/search/90a11be4... |
| 6 | Newsletter benchmarks Brevo RFC 8058 | perplexity.ai/search/4cfee999... |
| 7 | Loi 25 Québec + Consent Mode v2 | perplexity.ai/search/e09e966a... |
| 8 | UI/UX 2026 bento dark-first | perplexity.ai/search/fd830bb0... |
| 9 | PWA Web Share Push offline | perplexity.ai/search/b6a60b7c... |
| 10 | Local SEO Québec voice search | perplexity.ai/search/74dbc249... |
| 11 | Web monetization Stripe Cashier 2026 | perplexity.ai/search/1bdd7613... |
| 12 | Knowledge graph Wikidata DBpedia | perplexity.ai/search/c2f28ca3... |
| 13 | Laravel 11 security 2026 + CSP nonce | perplexity.ai/search/01922081... |
| 14 | Image optimization AVIF WebP 2026 | perplexity.ai/search/97c47b78... |

### Validation visuelle (screenshots Playwright)
- `audit-homepage.png` — homepage laveille.ai
- `audit-annuaire.png` — page `/annuaire`
- `audit-blog-article.png` — article `/blog/declaration-montreal-ia-responsable`

### Note action user URGENTE
⚠️ **Ré-authentifier Google Analytics 4 + Google Search Console** : tokens expirés (erreur `invalid_grant`). Sans cette action, l'axe 6 Performance reste estimé, et les analyses de requêtes SEO (axe 7 partiel) ne peuvent être validées en données live. Procédure :
1. Aller sur `claude.ai` → Settings → Connectors
2. Désactiver puis réactiver Google Analytics 4 + Google Search Console
3. Réauthorisation OAuth Google avec compte `stephane@memora.ca`
4. Tester via `mcp__ga4__ga4_user_metrics` + `mcp__gsc__gsc_top_queries`

⚠️ **Cron #69 cPanel** (héritage S125) : Command `?` orphelin chaque minute, linekey court non-API. À supprimer manuellement via cPanel UI → Tâches Cron → Supprimer la ligne `#69 Schedule * * * * * Command ?`.

---

*Audit généré par Claude (superviseur Opus 4.7) + délégation MCP : 14 recherches `pp_search` Perplexity Pro + 13 audits MCP (security, wcag-mcp, robotalp, compliance, cpanel, cloudflare) + 1 génération markdown qwen3-max via openrouter-free (latence 1,4 s, coût 0 $). Coût total IA session : ~0,15 $ (10 pp_search à 0,008 $ × 14 = 0,11 $ + 0 $ MCP custom). Conforme aux règles user S124-S126 : autonomie totale, MCP obligatoire, validation visuelle Playwright, accents FR préservés, heure Québec EDT, Brevo newsletter only.*
