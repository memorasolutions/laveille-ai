# État de session - la-veille-de-stef-v2

> Mis à jour le **2026-09-30 (Québec)**. Fichier réécrit, jamais empilé.

## ✅ Fait et prouvé ce cycle (EN PROD)

### #2915 - Formulaire de contact durci contre le spam - DÉPLOYÉ (v1.312.2 + v1.312.3)
- **Ce qui a été corrigé** : le piège temporel (`time-trap`) de `ContactController::spamSignals()` ne se
  déclenchait que si `form_ts` était présent ET récent. Un robot qui poste EN DIRECT sur la route
  (sans charger la page) n'envoie aucun `form_ts` et passait à travers. Désormais, `form_ts` absent /
  vide / non numérique = signal FORT -> quarantaine silencieuse (status='spam', aucun courriel; le
  message reste conservé et consultable en admin, purgé automatiquement après 60 jours seulement).
- **Zéro faux positif prouvé** : la seule vue du formulaire (`contact.blade.php:48`) rend toujours
  `<input hidden name="form_ts">`, c'est un POST HTML classique (aucun JS n'intercepte, aucun fetch),
  donc une vraie soumission porte toujours le jeton. Un `form_ts` ANCIEN (page en cache) reste ACCEPTÉ.
  Vérifié en prod : `curl https://laveille.ai/contact` -> `name="form_ts" value="..."` + honeypot présents.
- **Passe adversariale /100 (sous-agent frais, indépendant)** : verdict `faux_positif_possible: false`.
  Il a lu le code réel (formulaire unique, route unique, aucune interception JS, `/contact` non caché)
  et n'a trouvé AUCUN chemin de faux positif ni de contournement nouveau. Deux points mineurs relevés,
  tous deux traités : (1) couverture de test des cas vide/non numérique -> test paramétré ajouté;
  (2) mon affirmation « jamais supprimé » était fausse -> corrigée en « purgé après 60 jours ».
- **Tests** : `ContactSpamTest` = 12 passent (63 assertions), dont 5 neufs ce cycle (form_ts absent,
  vide, `abc`, `NaN` -> quarantaine; form_ts vieux de 7200 s -> accepté sans faux positif) +
  non-régression du cas légitime.
- **Défense en profondeur déjà en place** : honeypot + url_in_name (v1.311.0, qui attrapait déjà le
  pourriel reçu) + >=4 liens + mots-clés + tout-majuscules + time-trap (renforcé ici).
- **Livraison** : commit dd43a1a2d (fix, v1.312.2) puis le lot de tests (v1.312.3), poussés origin +
  forge, CI verte, déploiement cPanel vert, Cloudflare purgé.

### #2919 - Article de blogue « Gemini skills / Opal » (cycle précédent, en prod)
- En ligne : https://laveille.ai/blog/gemini-skills-c-est-quoi-pourquoi-google-ferme-opal (id 76,
  catégorie intelligence-artificielle). Faits vérifiés aux sources primaires Google, image validée par
  2 familles d'oracles, one-shot auto-supprimé, Cloudflare purgé.

## 🔄 En cours / à faire
- **Turnstile (#2368)** : protection ANTI-SPAM SUPPLÉMENTAIRE au formulaire. Le code est prêt et testé;
  il MANQUE la clé secrète. Le MCP Cloudflare N'EXPOSE PAS d'outil de création de widget Turnstile ->
  le widget se crée dans le tableau de bord Cloudflare (par le fondateur), et la clé secrète va dans
  1Password (règle 15 : Claude ne reçoit jamais un secret en clair). DÉCISION/ACTION DU FONDATEUR.
  À noter : le formulaire est DÉJÀ protégé en profondeur sans Turnstile - c'est un renfort, pas un manque.
- **#2798** rapport GA4 « ce qui amène des visiteurs » : relancer après le 7 oct (publications balisées).
- **#2919 suite optionnelle** : promo sociale (FB/LinkedIn/GMB) de l'article Gemini skills - actu chaude.
  NON faite (non demandée); à préparer sur signal, le fondateur voit le texte exact avant tout envoi.

## ⏸️ Décisions / actions du fondateur
- **Turnstile** : créer le widget au tableau de bord Cloudflare + déposer la clé secrète dans 1Password
  (coffre AI-Claude, étiquette projet:laveille), OU dire qu'on s'en tient à la défense actuelle.
- **AdSense (#2907)** : verdict d'examen (humain, Google), en attente.
- **Promo sociale de l'article Gemini skills** : à faire ou non?

## Prochaine action (au retour)
#2915 est clos (déployé, prouvé, adversaire /100 revenu propre). Les items restants dépendent du
fondateur (clé Turnstile, verdict AdSense) ou d'une date (#2798, après le 7 oct). Sur ton signal :
préparer la promo sociale de l'article Gemini skills.
