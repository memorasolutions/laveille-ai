# Checklist E2E manuelle — Module Academy (M9)

> À exécuter sur l'instance réelle avec le module Academy **activé**.
> Environnement : navigateur Chrome + axe DevTools extension ou équivalent.

---

## Préconditions

- [ ] Module Academy activé (`"Academy": true` dans `modules_statuses.json`)
- [ ] Au moins 1 cours publié (`status=published`, `visibility=public`) avec 1 chapitre, 2 leçons (1 vidéo + 1 quiz)
- [ ] Course gratuit ET course payant disponibles (ou simuler via `access_type` en DB)
- [ ] Compte admin et compte utilisateur ordinaire créés

---

## Parcours 1 — Visiteur non-connecté

- [ ] Accéder à `/academie` : la page index s'affiche sans erreur
- [ ] La barre de recherche `?q=` fonctionne (renvoie résultats ou "Aucune formation")
- [ ] Les filtres Gratuit/Payant et niveau fonctionnent
- [ ] Cliquer sur un cours : page `/academie/courses/{slug}` affiche titre, syllabus, CTA "Se connecter"
- [ ] L'accordéon du syllabus est accessible au clavier (Tab + Entrée ouvre/ferme)
- [ ] Accéder à `/academie/courses/{slug}/lessons/{id}` directement : la page affiche le panneau gating "Connexion requise" sans URL vidéo dans le DOM (inspecter source)
- [ ] **Sécurité** : vérifier dans l'inspecteur HTML qu'aucune `player_url` (ScreenPal) n'apparaît dans le DOM rendu
- [ ] Un certificat public `/academie/certificats/{slug}` est accessible sans connexion (vérifie JSON-LD EducationalOccupationalCredential)

---

## Parcours 2 — Inscription gratuite + leçon vidéo

- [ ] Créer un compte ou se connecter avec un compte ordinaire
- [ ] Aller sur un cours gratuit (`access_type=free`) → CTA "S'inscrire gratuitement" présent
- [ ] Cliquer "S'inscrire gratuitement" → redirection, statut `active` dans `enrollments`
- [ ] Accéder à une leçon vidéo : l'iframe ScreenPal se charge
- [ ] **Filigrane** : le filigrane (nom + horodatage) est visible en surimpression sur la vidéo
- [ ] **Filigrane** : `pointer-events: none` → cliquer sur la vidéo fonctionne normalement malgré le filigrane
- [ ] **Filigrane a11y** : l'inspecteur confirme `aria-hidden="true"` sur `.academy-watermark`
- [ ] Barre de progression affichée et mise à jour après chaque complétion
- [ ] Bouton "Marquer comme terminé" (video/doc) → progression incrementée

---

## Parcours 3 — Quiz interactif

- [ ] Accéder à une leçon de type quiz (item.type = 'quiz')
- [ ] Bouton "Commencer le quiz" → formulaire de quiz affiché
- [ ] **Sécurité session** : les questions ne sont PAS dans le DOM/JavaScript (stockées côté serveur)
- [ ] Répondre correctement à ≥ passing_score% → résultat "Quiz Réussi", item marqué `completed`
- [ ] Répondre incorrectement → résultat "Non réussi", bouton "Réessayer"
- [ ] Les 4 types de questions testés si disponibles : QCM, vrai/faux, réponse courte, appariement
- [ ] Appariement : les `<select>` ont des labels associés (visible ou `aria-label`)
- [ ] Test attempts_allowed : si limité, le quiz est bloqué après N tentatives

---

## Parcours 4 — Progression et certificat

- [ ] Compléter toutes les leçons requises d'un cours
- [ ] Barre de progression atteint 100%
- [ ] Le bloc "Félicitations" avec le bouton "Obtenir mon certificat" apparaît dans la leçon
- [ ] Cliquer sur "Obtenir mon certificat" → page `/academie/certificats/{slug}` s'affiche
- [ ] Le certificat affiche : nom de l'apprenant, titre du cours, date, numéro de série
- [ ] Le bouton "Imprimer / Exporter en PDF" déclenche `window.print()` (CSS @media print masque nav/boutons)
- [ ] JSON-LD `EducationalOccupationalCredential` présent dans le `<head>` (inspecter source)
- [ ] Un autre utilisateur ne peut PAS accéder au certificat avec l'ID d'un autre (tester avec slug deviné → 404)

---

## Parcours 5 — Cours payant (Stripe)

- [ ] Aller sur un cours payant (`access_type=paid_one_time`) → CTA "Acheter ce cours" présent
- [ ] Cliquer "Acheter ce cours" → redirection vers Stripe Checkout (mode test)
- [ ] Compléter le paiement en mode test → redirection vers le cours, inscription `active`
- [ ] Un cours gratuit ne présente PAS le CTA "Acheter" (vérifie la condition `!$isEnrolled && !$isFree`)

---

## Parcours 6 — IDOR et isolation

- [ ] Utilisateur A complète un item : Utilisateur B ne voit pas l'item marqué terminé
- [ ] Utilisateur B ne peut pas soumettre le quiz d'Utilisateur A via POST direct (vérifie que l'`Enrollment` est checké)
- [ ] Utilisateur B ne peut pas accéder à l'export CSV admin (doit avoir `academy.reports.view`)
- [ ] L'URL de la leçon d'un cours **non-publié** retourne 404

---

## Parcours 7 — Admin exports CSV

- [ ] Se connecter en tant qu'admin avec permission `academy.reports.view`
- [ ] Accéder à `/academie/admin/export/enrollments` → téléchargement CSV avec BOM UTF-8
- [ ] Accéder à `/academie/admin/export/completions` → téléchargement CSV
- [ ] Accéder à `/academie/admin/export/progress` → champ `percent` présent (pas `percentage`)
- [ ] Un utilisateur sans permission → 403

---

## Vérification a11y (axe)

- [ ] Ouvrir l'extension axe ou Lighthouse sur `/academie`, `/academie/courses/{slug}`, `/academie/courses/{slug}/lessons/{id}`
- [ ] Zéro violation critique (contrast, missing label, missing alt)
- [ ] Vérifier navigation clavier complète sur la page de leçon : Tab atteint sidebar, liens leçons, boutons quiz
- [ ] Le filigrane vidéo ne perturbe PAS les lecteurs d'écran (aria-hidden=true vérifié)
- [ ] Les `<select>` d'appariement ont des labels lisibles par lecteur d'écran

---

## CSP (Content Security Policy)

- [ ] Dans l'onglet Réseau, vérifier la présence du header `Content-Security-Policy` sur les pages `/academie/...`
- [ ] `frame-src` contient `screenpal.com`
- [ ] `frame-ancestors` présent (protection clickjacking)
- [ ] Aucune violation CSP dans la console navigateur lors de la lecture d'une vidéo

---

> Résultats à consigner dans `.outils/qa-academy-m9-{date}.md`.
