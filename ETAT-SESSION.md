# État de session - La veille de Stef v2

Dernière mise à jour : 13 octobre 2026, 20h49 Québec (00:49 UTC le 14 oct). Production en **v1.350.0 LIVRÉE ET PROUVÉE EN PROD** - les trois bugs de #3098 de l'anonymiseur sont corrigés et servis (bug 1 confidentialité en v1.349.0, bugs 2 et 3 en v1.350.0). **#3098 est CLOS.**

## Où on en est (livré et prouvé en prod)

- **File /actu2 (sur « continue » du fondateur) : CLOSE.** Sur 3 tickets, 2 étaient déjà couverts, 1 était neuf :
  - **#3073 Mistral « visual grounding » → DOUBLON** : la fiche Mistral Large 4 / « le Chonk » est déjà publiée (slug mistral-devoile-le-chonk-...). Source primaire vérifiée au texte réel. Brouillon 75515 parké (hold 14 j), rien republié. La fiche LIVE ne couvre PAS l'angle grounding → enrichissement optionnel offert au fondateur (non fait, zéro-casse sur fiche live).
  - **#3077 « Unlimited OCR » → DOUBLON** : déjà publié (baidus-unlimited-ocr-...). Rien republié.
  - **#3076 « L'IA qui prédit l'avenir » → PUBLIÉE ET PROUVÉE** (fiche 75520) : https://laveille.ai/actualites/lia-qui-predit-lavenir-calcule-en-fait-des-probabilites-et-vient-de-devancer-des-humains-a-un-concours-de-pronostics (HTTP 200, og:image servie, dans le fil). Dépêche AFP (11 oct, via Boursorama car TVA 403), niveau relais, angle « probabilité, pas prophétie ». Procureur a corrigé une surgénéralisation. Réserve d'image notée (sûre mais pas explicitement « probabilité » pour les oracles).
- **#3095 Annuaire LucidNest « PWA sans magasins » → LIVRÉ ET PROUVÉ (v1.346.5)** : le « moins » devient « Offerte en application web progressive (PWA) : elle s'installe et s'utilise directement depuis le navigateur sur mobile comme sur ordinateur ». Prod : magasins=0, application native=0.
- **#3096 Fiche glossaire « PWA » → EN LIGNE ET PROUVÉE (v1.347.0)** : https://laveille.ai/glossaire/pwa (HTTP 200, image pwa.webp + pwa.jpg servies, définition/FAQ/service worker rendus). 3 sources vérifiées (MDN, web.dev, origine 2015). Auto-lien « PWA » se propagera sous ~1 h (cache linkifier).
- **#3097 QuizDock (outil type Wooclap) → ÉVALUÉ, reco NO-GO maintenant** : besoin non validé (~6 abonnés) + temps réel WebSocket non acquis sur cPanel partagé + QuizDock immature/licence non confirmée. Condition de réouverture : présentations réelles planifiées → alors étendre les sondages Laravel en polling HTTP, sans dépendance. Décision finale = fondateur.
- **#3099 Spec entête « Actualités » pour le pair Moodle → LIVRÉE** : déposée dans `_sso-academie/surfaces-partagees/SPEC-ENTETE-ACTUALITES.md` + transmise. Markup `.topbar`, CSS (pastille grise #616161), source `HeaderNavService::latestNewsArticle()`, et surtout la voie recommandée : l'API `GET /api/header-nav` → `utility_bar.latest_news {title,url}` (déjà en place #3030). Le pair a confirmé réplication en cours (il tirait déjà l'API, l'écart n'était que le rendu).
- **#3100 Exclusion cours privé CSSBE de la vitrine /cours → LIVRÉE ET DÉPLOYÉE (v1.348.0)** : MoodleCatalogService exclut (a) toute catégorie dont l'`idnumber` est dans `catalog.excluded_category_idnumbers` (défaut « prive »), (b) tout cours dont le champ Moodle `catalogue` vaut faux (opt-out défense en profondeur). Inerte tant qu'aucune catégorie `prive`/champ n'existe (zéro casse), 43 tests verts dont 3 neufs. Reco transmise au pair : créer la catégorie « Formations privées » visible=1 avec idnumber `prive`. Fuite de TITRE via l'index Moodle natif (`/formations/course/`) signalée au pair : l'inscription protège le CONTENU, pas le listing → verrouiller ou rediriger les pages de découverte natives côté Moodle (hors de mon périmètre).

## Ce qui est en cours

- Rien de bloquant côté développement. #3098 est clos (voir ci-dessous). Les items ouverts restants dépendent du fondateur ou sont datés dans le futur (section « Ce qui bloque »).

## Ce qui bloque et attend une réponse du fondateur

- **#3073** : enrichir ou non la fiche ML4 en ligne avec l'angle visual grounding (optionnel).
- **#3097** : go/no-go QuizDock (ma reco = non pour l'instant ; panel complet 6 oracles possible si reconsidération vers GO).
- Standing : EFVP Loi 25 SSO + vente de cours (#3004) → débloque #3002/#3003/#3086. Daté : #2924 FTC (dès le 14 oct), #2931 (15 oct), #2977 (20 oct), #3001 (~avril 2027).

## Prochaine action proposée

#3098 est CLOS et prouvé en prod (v1.350.0) à temps pour la formation CSSBE du jeudi 15 oct. Rien d'urgent ne reste. En attente des décisions du fondateur sur les items optionnels/datés (section « Ce qui bloque »). Pair Moodle : archi certificats FIGÉE et conforme à ma position (laveille seule surface, Moodle émet, WS lecture seule) ; 4 items pour moi APRÈS go-live SSO + EFVP, rien maintenant. Runner prod `_a2run.php` + payloads/images 75515/75520 + a2img_75520 dans storage/app à NETTOYER en fin de file /actu2 (storage/app ~786 items, housekeeping non exposé au web). HEAD = c072e0a07 (v1.350.0), poussé origin + forge.
