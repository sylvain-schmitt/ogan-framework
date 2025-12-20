# 📢 Event Dispatcher - Ogan Framework

> Système d'événements pour l'extensibilité de l'application

## 📖 Introduction

L'Event Dispatcher permet de créer des hooks dans votre application pour :
- Exécuter du code à des moments précis (login, création d'entité, etc.)
- Modifier le comportement du framework sans toucher au code source
- Découpler les fonctionnalités (notifications, logs, etc.)

## 🚀 Usage de base

### Écouter un événement

```php
use Ogan\Event\EventDispatcher;

$dispatcher = EventDispatcher::getInstance();

// Écouter avec une closure
$dispatcher->listen('user.created', function($event) {
    // Envoyer un email de bienvenue
    $user = $event->user;
    mail($user->getEmail(), 'Bienvenue !', 'Contenu...');
});

// Alias : on()
$dispatcher->on('user.deleted', fn($e) => logger()->info('User deleted'));
```

### Dispatcher un événement

```php
use Ogan\Event\Event;

// Créer un événement custom
class UserCreatedEvent extends Event
{
    public function __construct(
        public User $user
    ) {}
}

// Dispatcher
$event = new UserCreatedEvent($user);
EventDispatcher::getInstance()->dispatch('user.created', $event);
```

## 🎯 Événements Kernel

Des événements sont dispatchés automatiquement par le framework :

| Événement | Classe | Moment |
|-----------|--------|--------|
| `kernel.request` | `RequestEvent` | Début de la requête |
| `kernel.controller` | `ControllerEvent` | Avant le controller |
| `kernel.response` | `ResponseEvent` | Après le controller |
| `kernel.exception` | `ExceptionEvent` | Lors d'une exception |
| `kernel.terminate` | `TerminateEvent` | Après envoi réponse |

### Exemple : Middleware custom

```php
// Bloquer certaines routes
$dispatcher->listen('kernel.request', function(RequestEvent $event) {
    $path = $event->getRequest()->getUri();
    
    if (str_starts_with($path, '/admin')) {
        // Vérifier l'authentification
        if (!isAdmin()) {
            $event->setResponse(new Response('Forbidden', 403));
            // Arrête la propagation et court-circuite le controller
        }
    }
});
```

### Exemple : Modifier la réponse

```php
$dispatcher->listen('kernel.response', function(ResponseEvent $event) {
    // Ajouter un header à toutes les réponses
    $event->getResponse()->setHeader('X-Powered-By', 'Ogan Framework');
});
```

### Exemple : Gérer les exceptions

```php
$dispatcher->listen('kernel.exception', function(ExceptionEvent $event) {
    $exception = $event->getException();
    
    if ($exception instanceof NotFoundException) {
        $event->setResponse(new Response('Page non trouvée', 404));
    }
});
```

## ⚡ Priorités

Les listeners avec une priorité plus élevée s'exécutent en premier :

```php
$dispatcher->listen('kernel.request', $authMiddleware, 100);  // Exécuté en 1er
$dispatcher->listen('kernel.request', $logMiddleware, 50);    // Exécuté en 2ème
$dispatcher->listen('kernel.request', $otherMiddleware, 0);   // Exécuté en 3ème
```

## 🛑 Arrêter la propagation

```php
$dispatcher->listen('kernel.request', function($event) {
    if ($condition) {
        $event->stopPropagation();
        // Les listeners suivants ne seront pas exécutés
    }
});
```

## 📋 Méthodes du Dispatcher

| Méthode | Description |
|---------|-------------|
| `listen($event, $callback, $priority)` | Enregistre un listener |
| `on($event, $callback, $priority)` | Alias de listen |
| `dispatch($event, $eventObject)` | Dispatch un événement |
| `hasListeners($event)` | Vérifie s'il y a des listeners |
| `getListeners($event)` | Retourne les listeners |
| `removeListeners($event)` | Supprime les listeners |
| `clearListeners()` | Supprime tous les listeners |

## 💡 Créer ses propres événements

```php
<?php

namespace App\Event;

use Ogan\Event\Event;
use App\Model\Order;

class OrderCreatedEvent extends Event
{
    public function __construct(
        private Order $order
    ) {}
    
    public function getOrder(): Order
    {
        return $this->order;
    }
}
```

```php
// Dans le controller
$order->save();
EventDispatcher::getInstance()->dispatch(
    'order.created',
    new OrderCreatedEvent($order)
);
```

```php
// Dans un listener (ex: services.php ou bootstrap)
EventDispatcher::getInstance()
    ->listen('order.created', fn($e) => sendOrderConfirmation($e->getOrder()))
    ->listen('order.created', fn($e) => notifyWarehouse($e->getOrder()));
```

## 📁 Configuration YAML (optionnel)

Si vous souhaitez configurer les listeners en YAML :

```yaml
# config/listeners.yaml
listeners:
  kernel.request:
    - { class: App\Listener\AuthListener, method: onRequest, priority: 100 }
    - { class: App\Listener\LogListener, method: onRequest, priority: 50 }
  
  user.created:
    - { class: App\Listener\WelcomeEmailListener, method: onUserCreated }
```
