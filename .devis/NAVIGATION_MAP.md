# Carte navigation admin - Post-refonte

Date : 2026-03-15
Fichier : `Modules/Backoffice/resources/views/themes/backend/partials/sidebar.blade.php`

---

## Structure (7+2 catégories, Priority+ pattern)

| # | Catégorie | Icône | Items principaux | Priority+ ("Plus...") |
|---|---|---|---|---|
| 1 | Principal | home, bar-chart-2 | Dashboard, Statistiques | - |
| 2 | Contenu | file-text | Articles, Pages, Médias, Catégories, FAQ, Témoignages, Formulaires (7) | Commentaires, Tags |
| 3 | Utilisateurs | users | Membres, Rôles, Équipes, Newsletter, Campagnes, Workflows, Messages (7) | - |
| 4 | Ventes | shopping-cart | Boutique, Produits, Commandes, Coupons, Plans, Revenus (6) | - |
| 5 | Réservations* | calendar-check | Dashboard, RDV, Calendrier, Services, Forfaits, Clients, Paramètres (7) | Coupons, Cartes-cadeaux, Disponibilités, Stats, Webhooks |
| 6 | Configuration | settings | Personnalisation, SEO, Traductions, Emails, Menus, Widgets, Annonces (7) | - |
| 7 | Système | shield | Santé, Sécurité, Sauvegardes, Journaux, Cache, Notifications, Corbeille (7) | 23 items (Feature Flags, Plugins, Webhooks, etc.) |
| 8 | Support IA* | bot | Boîte réception, Tickets, Conversations, Agent, KB, Analytics (6) | Sources URLs, Réponses prédéfinies, SLA, Canaux, Déclencheurs, CSAT |
| 9 | Roadmap* | map | Tableaux, Idées, Stats (3) | - |
| - | Documentation | book-open | Lien direct | - |

\* = conditionnel (affiché si module actif)

## Bottom tab bar mobile (d-lg-none)

| Icône | Label | Cible |
|---|---|---|
| home | Accueil | admin.dashboard |
| file-text | Contenu | admin.blog.articles.index |
| shopping-cart | Ventes | admin.ecommerce.dashboard |
| bell | Notifs | admin.notifications.index |
| menu | Plus | Ouvre sidebar complète |

## Améliorations WCAG appliquées

- Cibles tactiles sidebar : min-height 44px + padding = 48px effective
- Bottom bar items : min-height 48px
- aria-current="page" sur items actifs
- aria-label sur tous les boutons bottom bar
- safe-area-inset-bottom pour iPhone
- Hide-on-scroll-down / show-on-scroll-up

## Avant / Après

| Métrique | Avant | Après | Gain |
|---|---|---|---|
| Catégories | 12 | 7 (+2 conditionnels) | -42% |
| Items visibles (7 max/dropdown) | illimité (14 max) | 7 max | Miller 7±2 |
| Cibles tactiles | 32px | 44-48px | WCAG conforme |
| Bottom tab bar | Absente | 5 icônes | Mobile first |
| Clics mobile (top feature) | 3-4 | 1 (via bottom bar) | -75% |
