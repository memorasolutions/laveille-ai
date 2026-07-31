# Audit complet — Constructeur de prompts

**laveille.ai/outils/constructeur-prompts · 18 juin 2026 · version 1.65.260**

## Note globale : 88/100 — Outil solide, complet et à jour

3 bugs trouvés et **corrigés** pendant l'audit, 2 améliorations ajoutées, **sans aucune régression** (test E2E 5/5 PASS).
Méthode : recherche `pp_search` (best practices 2026) + cartographie du code (agent Explore) + test E2E live (Playwright).

## 1 — Ce qui a été testé et FONCTIONNE ✅

Assistant 4 étapes (persona, tâche + verbe, audience, options avancées) ; format / longueur / ton / langue ; technique (zero-shot, zero-shot + CoT, few-shot, few-shot + CoT, itératif) ; délimiteurs ### ; contraintes (anti-IA, typographie FR, Canvas/artefact multi-IA ChatGPT/Claude/Gemini/Mistral, raisonnement étape par étape, poser des questions, libres) ; aperçu temps réel + compteur ; sauvegarde/historique (BDD connecté / localStorage non connecté) ; export .txt ; anonymiseur intégré (100 % local) ; aides « ? » par section.

## 2 — Bugs trouvés ET corrigés (v1.65.259, vérifiés 5/5 PASS)

- ❌→✅ **« Ouvrir dans » ne transmettait presque jamais le prompt** : garde-fou de longueur (1800 car.) déclenché sur la plupart des prompts → ouvrait l'IA sans le texte. Corrigé : seuil relevé à 4000 (formats `?q=` corrects, vérifiés) + message « copié ».
- ❌→✅ **Double article** « Tu es un(e) un vulgarisateur… » (persona custom débutant par un article) → détection d'article.
- ⚠️→✅ **Confirmation de copie peu visible** → toast « Prompt copié ! ».

## 3 — Améliorations ajoutées

- ✅ **Encadré « ✦ En bref » repliable** (accordéon natif, accessible, fermable) — zéro régression sur les articles (composant partagé, prop `collapsible` défaut false).
- ✅ **Déroulants enrichis** (enseignants/PME) : +5 formats (QCM avec corrigé, grille d'évaluation, fiche pratique, gabarit, FAQ), +3 tons (neutre/factuel, empathique, motivant), +5 personas (concepteur pédagogique, gestionnaire de médias sociaux, rédacteur publicitaire, formateur, adjoint administratif).

## 4 — Conformité aux best practices 2026 (/100)

| Critère | Note |
|---|---|
| Blocs de prompt (rôle/contexte/tâche/format/contraintes/few-shot) | 95 |
| Support multi-modèles (Canvas par IA) | 90 |
| Deep-links « Ouvrir dans l'IA » | 92 (réparé) |
| Aide « ? » pour novices | 88 |
| Structure GEO/AEO + Schema.org | 90 |
| Accessibilité (WCAG) | 78 |
| Boucle de test (éditer→tester→comparer) | 70 |
| Bibliothèque de modèles par métier | 40 (manquant) |
| Comparaison multi-modèles / variantes | 30 (manquant) |

## 5 — Recommandations restantes (/100)

1. **Bibliothèque de modèles prêts par métier** (enseignant, PME, RH…) — **95** : plus grand gain restant, surtout pour novices.
2. **Couche pédagogique « pourquoi ce prompt marche »** + avant/après — **88**.
3. **Corriger le piège de focus de la modale d'aide** (aria-hidden + focus à la fermeture) — **85**.
4. **Réactiver le bouton Partager sur mobile** + cohérence — **75**.
5. **Comparaison multi-modèles / variantes** — **70** (plus complexe).

## 6 — Verdict

Outil mature et conforme aux pratiques 2026, désormais **sans bug majeur** et enrichi pour le public enseignant/PME. Prochain saut de valeur = **bibliothèque de modèles par métier**.

**Chantiers connexes notés et en file** (hors de cet outil) : (1) admin « scroll infini » sur `/admin/users` ; (2) annuaire — tutoriels étiquetés « FR » mais en anglais (détection de langue à corriger).

---
*Audit en supervision : recherche pp_search, code délégué à Hermes, tests Playwright. Correctifs déployés en production (v1.65.259-260) et vérifiés. laveille.ai — 18 juin 2026.*
