# Navigation Map — Restructuration

## Principes
- 2 niveaux max (catégorie → items directs)
- Desktop : 7±2 items par niveau
- Mobile : 5 onglets bottom tab bar (zone pouce)
- Priority+ : items critiques visibles, reste dans "Plus"
- Cibles >= 48x48px, ARIA labels, dark mode
- Catégories par intention utilisateur

## Desktop — Sidebar restructurée (6 catégories + conditionnel)

```
PRINCIPAL (toujours visible, pas de collapse)
├── Tableau de bord
└── Statistiques

CONTENU (intention : créer et gérer du contenu)
├── Articles
├── Pages
├── Catégories
├── Médias
├── FAQ
├── Menus
└── Plus... → Tags, Commentaires, Témoignages, Widgets, Shortcodes, Custom Fields

MARKETING (intention : engager et convertir)
├── Newsletter
├── Campagnes
├── Workflows
├── Templates
├── Abonnés
└── Formulaires

UTILISATEURS (intention : gérer les personnes)
├── Membres
├── Rôles
├── Équipes
└── Messages contact

CONFIGURATION (intention : personnaliser le système)
├── Paramètres
├── SEO & Redirections
├── Branding
├── Traductions
├── Feature flags
├── Emails templates
└── Plus... → Thèmes, Cookies, Annonces

SYSTÈME (intention : surveiller et maintenir)
├── Santé
├── Sécurité
├── Sauvegardes
├── Journaux
├── Cache
└── Plus... → Notifications, Tâches, Jobs échoués, Historique connexions, Courriels, IP bloquées, Corbeille, Rétention, Infos système

MODULES CONDITIONNELS (si activés) :
├── Support IA → Base de connaissances, Conversations, Agent, Analytics
├── Roadmap → Tableaux, Idées, Statistiques
├── Ventes → Plans, Revenus, Tenants
├── Réservations → Dashboard, RDV, Services, Calendrier
└── Documentation
```

## Mobile — Bottom Tab Bar (5 onglets)

```
┌─────────────────────────────────────────────┐
│  [home]     [file-text]  [users]  [settings]  [menu]  │
│  Accueil    Contenu      Équipe   Config      Plus    │
└─────────────────────────────────────────────┘
```

### Comportement par onglet :
| Onglet | Icône | Action au tap | Contenu |
|--------|-------|--------------|---------|
| Accueil | home | Navigation directe | Dashboard |
| Contenu | file-text | Bottom sheet / slide-up | Articles, Pages, Catégories, Médias, FAQ, Menus |
| Équipe | users | Bottom sheet | Membres, Rôles, Équipes, Messages |
| Config | settings | Bottom sheet | Paramètres, SEO, Branding, Traductions |
| Plus | menu | Slide-up full menu | Marketing, Système, modules conditionnels |

### Patterns par breakpoint :

| Breakpoint | Pattern | Sidebar | Bottom bar | Détails |
|------------|---------|---------|------------|---------|
| >= 1200px | Desktop | Persistante, expandable | Non | Sidebar 240px, 6 catégories |
| 768-1199px | Tablet | Rail (icônes only, expand hover) | Non | Sidebar 64px → 240px hover |
| < 768px | Mobile | Masquée | Oui (5 tabs) | Bottom bar 56px, slide-up panels |

### Hide-on-scroll (mobile) :
- Scroll down : bottom bar slide-down (hidden)
- Scroll up : bottom bar slide-up (visible)
- Tap content area : bottom bar visible

## Réduction items

| Avant | Après | Réduction |
|-------|-------|-----------|
| 9 catégories | 6 (+conditionnel) | -33% |
| 102 liens | ~45 visibles (reste dans Plus) | -56% |
| 3 niveaux | 2 niveaux max | -33% |
| 605 lignes monolithique | ~300 lignes modulaire | -50% |
