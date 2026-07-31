# Matrice de couverture - Audit ciblé : mécanisme Service Worker ("Update on reload")

Périmètre restreint par l'utilisateur (`/audit pour "Service Worker was updated because..."`).
Signalement récurrent (4e round : #906-909, #969, #972 concluent tous "artefact DevTools" -
l'utilisateur exige cette fois une correction réelle après audit en profondeur).

| Dimension | Statut | Preuve / justification |
|---|---|---|
| securite-applicative | non applicable | Aucune surface d'attaque liée au mécanisme SW (pas d'entrée utilisateur, pas de donnée sensible) - hors périmètre demandé |
| securite-infra | à faire | En-têtes HTTP/caching Cloudflare du fichier sw-source.js pertinents pour le mécanisme d'update |
| qualite-code-DRY | à faire | Revue du code d'enregistrement (pwa.js) et des déclencheurs possibles de ré-enregistrement |
| performance | à faire | Fréquence des vérifications d'update, coût des appels register() répétés |
| accessibilite | non applicable | Aucun impact UI/a11y - hors périmètre demandé |
| UX-UI | à faire | Impact du message console sur l'expérience développeur/admin (pas visiteur final) |
| SEO-GEO-AEO | non applicable | Aucun impact indexation/contenu - hors périmètre demandé |
| conformite-Loi25-RGPD | non applicable | Aucune donnée personnelle impliquée - hors périmètre demandé |
| tests-couverture | à faire | Existe-t-il un test qui garantit l'absence de double enregistrement du SW ? |
| dependances-CVE-licences | non applicable | Pas de vulnérabilité connue liée à vite-plugin-pwa pour ce symptôme - hors périmètre demandé |
| hygiene-serveur | à faire | Cache Cloudflare, cohérence du fichier servi entre requêtes |
