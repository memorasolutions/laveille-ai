# État de session - la-veille-de-stef-v2

> Mis à jour le **2026-09-25 en soirée (Québec)**. Ce fichier est TOUJOURS le même : on le réécrit, on n'empile pas.

---

## ✅ Où on en est (terminé et prouvé)

- **v1.300.0 déployée et vérifiée en prod** (CI verte + déploiement rsync/SSH réussi) :
  - **Outil de signatures HTML de courriel** (module Signature) EN CONSTRUCTION : /outils/signature-courriel répond 200 en page de construction pour un visiteur anonyme (visible du fondateur seul). 68 tests verts. Une revue adversariale `fable` avait trouvé 5 bloquants + 10 majeurs (purge inversée, aucun filet, images énumérables Loi 25, chemin membre mort, mentions IA) : TOUS corrigés, dont une quarantaine opérateur de 30 jours (archive + déplacement avant toute suppression - la règle « jamais supprimer sans filet »).
  - **Fiche de glossaire « CVE »** (/glossaire/cve, 200, image servie).
  - **Colonne « Publication » de l'admin blogue** : badge Publiée/Planifiée + date-heure Québec, titres multi-lignes.
  - **Générateur de politique IA désactivé** sur laveille (/outils/politique-ia -> 404), module conservé pour un autre site.
  - **Nettoyage** des mentions IA dans les commentaires du code (41 fichiers).
  - `news:hold` ajoutée à la liste blanche du runner de prod.
- **Fiche d'actualité 59018 (Copilot autopilot + facturation à l'usage)** publiée et prouvée en prod (vraie image, crédit ChatGPT).
- **Standard veille.la réconcilié** : lien court dont la destination porte les UTM (propre + mesuré GA4 + stats ShortUrl). /publier + hook + 2 skills sociaux périmés corrigés.

## 🔄 En cours (agents en tâche de fond)

- **2 fiches de glossaire** issues du scan Copilot : « facturation à l'usage » et « routage automatique de modèles » (créées en local, iront dans un prochain déploiement groupé).
- **Outil tirage-présentations** : 4 ajouts de flexibilité + mise à jour de la description (en local).

## ⏸️ Ce qui BLOQUE sur le fondateur

- **Publications sociales à refaire avec veille.la (#2822)** : le fondateur doit d'abord SUPPRIMER dans le portail les publications en attente (350-359, et 346-349 s'il veut la cohérence) - l'API n'a aucune route DELETE. La refonte inclut la PARTIE 1 de la série (#2817). Rien n'est recréé avant sa suppression (sinon doublons).
- **10 publications d'actus (350-359) en `pending_approval`** : n'auto-envoient PAS, attendent son approbation dans le portail (ou sa suppression pour la refonte veille.la).
- **QC visuel** (exige sa connexion admin/2FA) : l'éditeur de signatures, la colonne admin du blogue.
- Items d'action fondateur : #2585 (approuver 300/refuser 298), #2735, #2788, #2368 (clé Turnstile), #2276/#2638 (prompts à coller), #2597, #2722.

## ➡️ Prochaine action proposée

Recevoir les 2 agents (glossaire + tirage) -> déploiement groupé suivant. Dès que le fondateur supprime les publications en attente : refonte veille.la de toutes + partie 1 de la série. Compactage de MEMORY.md (#2812) reste en attente basse (sous la limite de lecture, non urgent).
