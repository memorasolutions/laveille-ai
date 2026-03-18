# Audit architecture modules — 2026-03-18

## Resume

- 38 modules nwidart actifs
- 431 imports cross-modules
- **0 dependance circulaire**
- 30+ guards class_exists() verifies
- 4 problemes identifies, 2 corriges cette session

## Problemes corriges

### 1. Duplication notifications (CORRIGE)
- **26 notifications** dupliquaient le pattern EmailTemplateService fallback (~40 LOC chacune)
- **Fix** : `TemplatedNotification` base class dans `Modules/Core/app/Notifications/`
- 4 notifications Ecommerce refactorises pour l'utiliser
- Reste a faire : migrer les notifications AI (5), SaaS (4), Auth (3), Newsletter (3), Team (1), Booking (2)

### 2. class_exists() disperse (CORRIGE)
- 30+ checks identiques eparpilles dans le codebase
- **Fix** : `ModuleChecker` service dans `Modules/Core/app/Services/`
- Methodes : `isAvailable()`, `classExists()`, `resolve()`, `when()`
- Reste a faire : migrer les appels existants vers ModuleChecker

## Problemes restants

### 3. AI sans interface (A FAIRE)
- 8 modules dependent de AiService sans contrat
- **Recommandation** : creer `AiServiceInterface` dans Core/Contracts

### 4. Search hub pattern (BASSE PRIORITE)
- SearchService hardcode 4 modules dans sa config
- **Recommandation** : utiliser tag-based discovery (comme MetricProviderInterface)

## Dependances cross-modules (matrice)

| Module | Depend de | Protege |
|--------|-----------|---------|
| Blog | - | Oui (base) |
| AI | Blog, Pages, Faq | Oui (class_exists + observers) |
| Ecommerce | Notifications, Webhooks | Oui (class_exists) |
| Newsletter | Blog (digest) | Oui |
| Search | Blog, Pages, SaaS, Categories | Oui |
| Menu | Pages, Blog | Oui |
| Backoffice | Tous (sidebar) | Oui (Module::has) |

## Modules independants (desactivables sans impact)
Booking, Team, Roadmap, FAQ, Testimonials, FormBuilder, ShortUrl, ABTest, CustomFields, Privacy, Health, Widget, Media, Editor, Publication

## Shared code dans Core
- 7 traits (HasRevisions, HasScheduledPublishing, HasPreviewToken, HasBulkActions, HasTableSorting, HasUuid, HasApiResponse)
- 4 interfaces (MetricProviderInterface, SettingsReaderInterface, UserInterface, ServiceInterface)
- 3 services (MetricAggregatorService, ModuleChecker, CrudService)
- 1 base notification (TemplatedNotification)
- 1 DTO (MetricWidget)
