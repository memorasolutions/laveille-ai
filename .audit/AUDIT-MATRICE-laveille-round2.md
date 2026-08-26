# Matrice - audit round 2 de laveille.ai (zones jamais lues)

Lancé le 2026-08-26 (America/Toronto). Portée : **complet sur le périmètre restant**.
Ce round ne recommence pas l'audit du 25 août : il attaque exactement les zones que sa
section 8 déclarait non couvertes. Le rapport du 25 reste la référence pour tout le reste.

| Zone / dimension | Statut | Preuve |
|---|---|---|
| Modules/Academy | complété | **Aucune faille confirmée** sur les 4 axes (accès sans inscription, IDOR, triche au quiz, fuite Loi 25). 20 fichiers ouverts. Points forts vérifiés : accès recalculé serveur à chaque requête, réponses de quiz en session serveur, limite de tentatives revérifiée DANS la transaction (anti-course), slug de certificat non énumérable. 1 signal basse : `ExportController:30,70,111` a une permission globale non scopée par cours, aujourd'hui réservée à admin/super_admin. Non couvert : Forum, Wiki, Workshop, SCORM, LTI. |
| 368 occurrences `{!! !!}` | complété | Triées par PROVENANCE de la donnée. Trouvé et corrigé : **XSS stocké sur page publique** dans Journal (`payload['html']` brut, lisible par un visiteur anonyme dès publication). Vérifié sûr : `renderRichText()` d'Academy applique `html_input => strip` (couvre des dizaines d'appels), régie publicitaire, JSON-LD, gabarits de courriel système, `strip_tags` du constructeur de journal. |
| Jobs de file + commandes planifiées | à faire | |
| Newsletter / Media / Notifications | complété | **Rien trouvé** sur les 5 axes. Jetons d'infolettre en `Str::random(64)`, honeypot + `throttle:5,1` à l'inscription, webhook Brevo en `hash_equals`, aperçu de courriel sur données factices. Ajout d'un `throttle:30,1` sur confirm/unsubscribe par cohérence défensive. **Faux positif écarté par test** : la règle `image` de Laravel 12 REFUSE le SVG (`allow_svg` non passé) - vérifié mécaniquement, deux agents se contredisaient. |
| Decido / Journal | complété | **Decido : rien trouvé**, module remarquablement durci (jeton admin haché SHA-256 jamais stocké en clair, `hash_equals`, `lockForUpdate` anti-course, `public_id` non énumérable). **Journal : policy correcte mais RENDU fautif** - XSS corrigé (voir ligne précédente). Books/Menu/Widget : agent en cours. |
| accessibilite (gabarits supplémentaires) | complété | Fiche glossaire testée. **3 vrais défauts** confirmés par calcul du fond effectif : badge `.diff-advanced` 3,76:1, `#9ca3af` 2,54:1 (20 occurrences / 14 fichiers), `.aab-feedback` 2,54:1. La charte du projet vise AAA (7:1). Incohérence interne : `Dictionary/index` est conforme, `Dictionary/show` ne l'est pas. |
| performance (gabarits supplémentaires) | complété | **Finding majeur mesuré** : première visite d'une fiche outil = 4,4 à 10,6 s de TTFB, seconde visite 0,5 s (facteur 10 à 16). Reproduit sur 5 pages. Cache de réponse de 7 jours, mais 3 599 pages pour un trafic faible : la majorité reste froide. Diagnostic du goulot délégué. |
| securite-infra (SSL) | complété | **Grade A+** (SSL Labs), certificat Let's Encrypt valide jusqu'au 2026-11-15. Le point resté en suspens le 25 est clos. |
