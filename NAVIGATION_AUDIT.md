# Audit navigation backend — 2026-03-15

## 1. Vue d'ensemble

| Indicateur | Valeur | Cible |
|------------|--------|-------|
| Catégories niveau 1 | **9** (+Documentation) | 5-7 |
| Total liens nav | **102** | ~40-50 |
| Profondeur max | **3 niveaux** | 2 niveaux |
| Fichier sidebar | **605 lignes** (monolithique) | Modulaire |
| Taille cibles tactiles | **44px** | 48px (WCAG 2.2) |
| Largeur sidebar | 240px fixe | Responsive |

## 2. Diagnostic par breakpoint

### Desktop (1280x800)
- Sidebar **persistante**, thème sombre, scrollable (159px overflow)
- 12 items collapsed visibles, "Support IA" et "Roadmap" coupés en bas
- Scroll nécessaire pour atteindre les dernières sections
- **Screenshot** : `/tmp/nav_audit_desktop.png`

### Tablet (768x1024)
- Sidebar **masquée par défaut**, overlay plein écran au toggle
- Hamburger en haut à droite, aucune navigation visible sans action
- Pas de bottom tab bar
- **Screenshot** : `/tmp/nav_audit_tablet.png`

### Mobile (375x812)
- Sidebar **masquée**, slide-over overlay depuis la droite
- ~7 catégories visibles avant scroll dans la sidebar
- Cibles tactiles trop petites pour utilisation confortable
- Pas de navigation en zone pouce
- **Screenshot** : `/tmp/nav_audit_mobile_open.png`

## 3. Clics vers fonctionnalités critiques

| Fonctionnalité | Clics desktop | Clics mobile | Cible |
|----------------|--------------|-------------|-------|
| Dashboard | 1 | 2 (toggle + clic) | 1 |
| Articles | 2 | 3 (toggle + expand + clic) | 1-2 |
| Utilisateurs | 2 | 3 | 1-2 |
| Paramètres | 2 | 3 | 1-2 |
| Sauvegardes | 2 | 3 | 2 |

## 4. Catégories actuelles (par taille)

| Catégorie | Items | Problème |
|-----------|-------|----------|
| Système | **24** | Beaucoup trop, mélange monitoring + ops + rétention |
| Réservations | **12** | Module conditionnel, trop proéminent |
| Contenu | **9** | Correct mais mélange blog + CMS + formulaires |
| Support IA | **9** | Module conditionnel, trop d'items exposés |
| Utilisateurs | **7** | Mélange users + newsletter + équipes |
| Configuration | **7** | OK |
| Ventes | **6** | Module conditionnel |
| Roadmap | **3** | OK |
| Principal | **2** | OK |

## 5. Violations

| # | Violation | Sévérité | Norme |
|---|-----------|----------|-------|
| V1 | Cibles tactiles 44px < 48px | Haute | WCAG 2.2 SC 2.5.8 |
| V2 | 3 niveaux de profondeur | Haute | UX best practice (max 2) |
| V3 | 102 items (7±2 rule) | Haute | Miller's Law |
| V4 | Pas de bottom tab bar mobile | Haute | Material Design 3 |
| V5 | Pas de Priority+ pattern | Moyenne | UX 2025-2026 |
| V6 | Système = 24 items | Moyenne | Surcharge cognitive |
| V7 | Pas de hide-on-scroll-down | Moyenne | Mobile UX pattern |
| V8 | Sidebar monolithique 605 lignes | Moyenne | Maintenabilité |
| V9 | Catégories techniques vs intention | Moyenne | UX naming |
| V10 | Pas de CSS env() safe areas | Basse | iOS notch/barre |
