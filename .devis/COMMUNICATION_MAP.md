# Communication inter-modules — 2026-03-18

## Patterns utilisés

| Pattern | Usage | Exemple |
|---------|-------|---------|
| **Events/Listeners** | Notifications, webhooks, side effects | OrderPaid → SendOrderConfirmation |
| **Contracts/Interfaces** | Services partagés | AiServiceInterface, MetricProviderInterface |
| **Tagged Services** | Discovery automatique | metric_providers tag |
| **class_exists() Guards** | Dépendances optionnelles | DispatchEcommerceWebhooks |
| **Base Classes** | Code partagé | TemplatedNotification |
| **Helpers statiques** | Vérification modules | ModuleChecker |

## Événements applicatifs (12)

### Core
| Événement | Émetteur | Listeners |
|-----------|----------|-----------|
| UserCreated | User model | Auth::SendWelcomeNotification |
| UserUpdated | User model | (prêt pour extension) |
| UserDeleted | User model | (prêt pour extension) |

### Ecommerce
| Événement | Émetteur | Listeners |
|-----------|----------|-----------|
| OrderCreated | CheckoutService | DispatchEcommerceWebhooks |
| OrderPaid | CheckoutService (Stripe webhook) | **SendOrderConfirmation**, DispatchEcommerceWebhooks |
| OrderShipped | OrderController (admin) | **SendOrderShippedNotification**, DispatchEcommerceWebhooks |
| LowStockDetected | InventoryService | NotifyAdminsLowStock, DispatchEcommerceWebhooks |
| **CartAbandoned** | ProcessAbandonedCarts job | **SendAbandonedCartReminder** |

### AI
| Événement | Émetteur | Listeners |
|-----------|----------|-----------|
| HumanTakeoverRequested | ChatBot Livewire | NotifyAgentsOfTakeover (broadcast) |
| AgentMessageReceived | Agent chat | (broadcast only) |

### Notifications
| Événement | Émetteur | Listeners |
|-----------|----------|-----------|
| RealTimeNotification | Generic | (broadcast only) |

## Contrats partagés (Core/Contracts/)

| Interface | Implémenteurs | Modules utilisateurs |
|-----------|--------------|---------------------|
| MetricProviderInterface | 6 providers | Backoffice (dashboard) |
| AiServiceInterface | AiService | Blog, Roadmap |
| SettingsReaderInterface | SettingsFacade | Tous |
| UserInterface | User model | Tous |

## Flux cross-modules

```
User s'inscrit → Core::UserCreated
  ├→ Auth::SendWelcomeNotification
  └→ Newsletter::WorkflowTriggerListener

Commande payée → Ecommerce::OrderPaid
  ├→ Ecommerce::SendOrderConfirmation (queued)
  └→ Ecommerce::DispatchEcommerceWebhooks → Webhooks::WebhookService

Commande expédiée → Ecommerce::OrderShipped
  ├→ Ecommerce::SendOrderShippedNotification (queued)
  └→ Ecommerce::DispatchEcommerceWebhooks

Stock bas → Ecommerce::LowStockDetected
  ├→ Ecommerce::NotifyAdminsLowStock
  └→ Ecommerce::DispatchEcommerceWebhooks

Panier abandonné → Ecommerce::CartAbandoned
  └→ Ecommerce::SendAbandonedCartReminder (queued)

Agent requis → AI::HumanTakeoverRequested
  ├→ Broadcast WebSocket
  └→ AI::NotifyAgentsOfTakeover (queued)
```

## Règle : zéro appel direct

Toute notification est envoyée via un listener sur un event, jamais par `$user->notify()` direct dans un contrôleur ou service.
