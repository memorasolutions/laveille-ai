# Audit navigation - Backoffice admin

Date : 2026-03-15
Outil : Playwright (3 breakpoints)
Fichier audité : `Modules/Backoffice/resources/views/themes/backend/partials/sidebar.blade.php`

---

## 1. Structure actuelle

| Métrique | Valeur | Standard industrie | Verdict |
|---|---|---|---|
| Catégories (niveau 0) | **12** | 5-7 max | FAIL |
| Dropdowns (niveau 1) | **11** | - | - |
| Items totaux (nav-item) | **~102** | 30-50 | FAIL |
| Profondeur max | **2** (catégorie → dropdown → item) | 2 max | OK |
| Liens directs niveau 0 | **3** (Dashboard, Statistiques, Documentation) | - | OK |

### Décompte par catégorie

| Catégorie | Dropdown | Sous-items | Commentaire |
|---|---|---|---|
| Principal | Non | 2 (Dashboard, Statistiques) | OK - liens directs |
| Contenu | Oui | **10** (Articles, Commentaires, Catégories, Tags, Pages, Médias, Menus, FAQ, Témoignages, Formulaires) | Trop dense |
| Utilisateurs | Oui | **8** (Membres, Rôles, Équipes, Newsletter, Campagnes, Templates marketing, Workflows, Messages) | Mélange utilisateurs + marketing |
| Boutique | Oui | 5 (Dashboard, Produits, Catégories, Commandes, Coupons) | OK |
| Business | Oui | **14** (Plans, Revenus, Conversations IA, Agent, Analytics IA, Base KB, Sources URLs, Réponses prédéfinies, Tickets, SLA, Canaux, Boîte de réception, Déclencheurs proactifs, CSAT) | CRITIQUE - beaucoup trop |
| Configuration | Oui | 7 (Personnalisation, SEO, Traductions, Emails, Onboarding, Widgets, Annonces) | Limite acceptable |
| Réservations | Oui | **11** (Dashboard, RDV, Calendrier, Services, Paramètres, Coupons, Forfaits, Cartes-cadeaux, Disponibilités, Statistiques, Clients, Webhooks) | Trop dense |
| Roadmap | Oui | 3 (Tableaux, Idées, Statistiques) | OK |
| Avancé | Oui | **9** (Feature Flags, Tests A/B, Plugins, Webhooks, Shortcodes, Short URLs, Cookies GDPR, Champs perso., Import) | Trop dense |
| Sécurité | Oui | 3 (Dashboard, IPs bloquées, Connexions) | OK |
| Outils | Oui | **7** (Sauvegardes, Journaux activité, Journaux app, Jobs échoués, Corbeille, Notifications, Push notifications) | Limite acceptable |
| Système | Oui | **10** (Santé, Incidents, Scheduler, Horizon, Emails envoyés, Aperçu courriels, Cache, Infos système, Stockage, Rétention données) | Trop dense |
| (hors catégorie) | Non | 1 (Documentation) | OK |

---

## 2. Diagnostic par breakpoint

### Desktop 1280x800

![Desktop](/tmp/nav_audit_desktop_1280.png)

- Sidebar : **240px** fixe, fond sombre (#0c1427)
- Items visibles sans scroll : **~8** (Principal + Contenu fermé + début Utilisateurs)
- Hauteur cibles clics : **~32px** (mesuré via Playwright)
- Scroll nécessaire pour atteindre : Sécurité, Outils, Système, Documentation
- `data-bs-parent="#sidebarNav"` : un seul dropdown ouvert à la fois (accordion)
- Pas de séparateurs visuels entre catégories (juste le label gris)

### Tablette 768x1024

![Tablette](/tmp/nav_audit_tablet_768.png)

- Sidebar : **identique au desktop** (240px), masquée par défaut (`visibility:hidden`)
- Hamburger en haut à droite
- Comportement : slide-in par dessus le contenu (pas d'overlay)
- Aucune adaptation responsive du contenu sidebar
- Items identiques au desktop - pas de simplification mobile

### Mobile 375x812

![Mobile](/tmp/nav_audit_mobile_375.png)

- Sidebar : masquée par défaut (`visibility:hidden`)
- Hamburger menu en haut à droite
- **Pas de bottom tab bar**
- **Pas de drawer offcanvas** (même mécanisme que tablette)
- Actions rapides visibles dans le contenu principal (6 boutons)
- Header réduit : logo "M" + recherche + dark mode + langue + notifications + avatar + hamburger

---

## 3. Chemins vers le top 5 des fonctionnalités

| Fonctionnalité | Desktop (clics) | Mobile (clics) | Chemin |
|---|---|---|---|
| Dashboard | **1** | **2** (hamburger + lien) | Direct niveau 0 |
| Articles | **2** | **3** (hamburger + Contenu + Articles) | Catégorie → Dropdown → Item |
| Utilisateurs | **2** | **3** (hamburger + Utilisateurs + Membres) | Catégorie → Dropdown → Item |
| Commandes | **2** | **3** + scroll (hamburger + scroll + Boutique + Commandes) | Catégorie → Dropdown → Item |
| Paramètres | **2** | **3** + scroll (hamburger + scroll + Configuration + Personnalisation) | Catégorie → Dropdown → Item |

**Problème mobile** : toute fonctionnalité au-delà de "Contenu" nécessite hamburger + scroll dans la sidebar + clic dropdown + clic item = **3-4 taps minimum**.

---

## 4. Violations identifiées

### WCAG 2.2 AA

| Violation | Détail | Critère |
|---|---|---|
| Cibles tactiles trop petites | **32px** hauteur < **48px** minimum | WCAG 2.5.8 Target Size (Minimum) |
| Pas d'indicateur de focus visible distinct | Focus outline par défaut du navigateur seulement | WCAG 2.4.7 Focus Visible |
| `aria-current=page` conditionnel | Implémenté correctement | OK |
| `aria-expanded` sur les dropdowns | Implémenté correctement | OK |
| `aria-label` sur nav | Implémenté correctement | OK |

### UX / Architecture de l'information

| Problème | Impact | Sévérité |
|---|---|---|
| 12 catégories | Surcharge cognitive, scroll obligatoire | Haute |
| Business : 14 sous-items | Impossible de trouver rapidement un item | Haute |
| Réservations : 11 sous-items | Idem | Haute |
| Mélange logique dans "Utilisateurs" | Newsletter/Campagnes ne sont pas des "utilisateurs" | Moyenne |
| Mélange logique dans "Avancé" | Fourre-tout : Feature Flags + Cookies + Import | Moyenne |
| Pas de bottom tab bar mobile | Accès aux fonctions fréquentes = 3+ taps | Haute |
| Sidebar identique desktop/mobile | Aucune adaptation responsive | Haute |
| Pas d'indicateur visuel des items actifs au scroll | L'item actif peut être hors de vue | Moyenne |
| Pas de recherche dans la sidebar | Avec 102 items, la recherche est essentielle | Moyenne |

### Performance perçue

| Métrique | Valeur |
|---|---|
| Temps pour atteindre "Système" (desktop) | ~3s de scroll |
| Temps pour atteindre "Système" (mobile) | ~5s (hamburger + scroll) |
| Items visibles sans interaction | 8/102 (7.8%) |

---

## 5. Recommandations pour la phase 3

1. **Réduire à 6-7 catégories max** en fusionnant les catégories similaires
2. **Max 7 sous-items par dropdown** (règle de Miller 7±2)
3. **Bottom tab bar mobile** avec les 4-5 actions les plus fréquentes
4. **Augmenter les cibles tactiles à 48px** minimum
5. **Ajouter une recherche/filtre dans la sidebar** (déjà présent dans le header, mais pas dans la sidebar)
6. **Regrouper intelligemment** : Business + IA en un seul groupe, Sécurité + Outils + Système en un seul groupe
7. **Priority+ pattern** : afficher les items les plus utilisés, masquer le reste dans "Plus..."
