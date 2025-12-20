<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 EVENT DISPATCHER - Système d'événements
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Permet d'enregistrer des listeners et de dispatcher des événements.
 * Le dispatcher est un singleton accessible partout dans l'application.
 * 
 * USAGE :
 * -------
 * // Enregistrer un listener
 * EventDispatcher::getInstance()->listen('user.created', function($event) {
 *     // Envoyer un email de bienvenue
 * });
 * 
 * // Dispatcher un événement
 * EventDispatcher::getInstance()->dispatch('user.created', new UserCreatedEvent($user));
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

class EventDispatcher
{
    /**
     * Instance singleton
     */
    private static ?self $instance = null;

    /**
     * Listeners enregistrés par nom d'événement
     * @var array<string, array<callable>>
     */
    private array $listeners = [];

    /**
     * Priorités des listeners
     * @var array<string, array<int>>
     */
    private array $priorities = [];

    /**
     * Constructeur privé (singleton)
     */
    private function __construct() {}

    /**
     * Récupère l'instance unique du dispatcher
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Réinitialise l'instance (utile pour les tests)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Enregistre un listener pour un événement
     * 
     * @param string $eventName Nom de l'événement
     * @param callable $listener Fonction ou méthode à appeler
     * @param int $priority Priorité (plus élevé = exécuté en premier, défaut: 0)
     */
    public function listen(string $eventName, callable $listener, int $priority = 0): self
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
            $this->priorities[$eventName] = [];
        }

        $this->listeners[$eventName][] = $listener;
        $this->priorities[$eventName][] = $priority;

        return $this;
    }

    /**
     * Alias pour listen()
     */
    public function on(string $eventName, callable $listener, int $priority = 0): self
    {
        return $this->listen($eventName, $listener, $priority);
    }

    /**
     * Dispatch un événement à tous ses listeners
     * 
     * @param string $eventName Nom de l'événement
     * @param Event|null $event Objet événement (créé automatiquement si null)
     * @return Event L'événement (potentiellement modifié par les listeners)
     */
    public function dispatch(string $eventName, ?Event $event = null): Event
    {
        $event = $event ?? new Event();

        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        // Trier par priorité (décroissante)
        $sortedListeners = $this->getSortedListeners($eventName);

        foreach ($sortedListeners as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }

    /**
     * Retourne les listeners triés par priorité
     */
    private function getSortedListeners(string $eventName): array
    {
        $listeners = $this->listeners[$eventName];
        $priorities = $this->priorities[$eventName];

        // Créer un tableau associatif index => priorité
        $indexed = [];
        foreach ($listeners as $index => $listener) {
            $indexed[] = [
                'listener' => $listener,
                'priority' => $priorities[$index]
            ];
        }

        // Trier par priorité décroissante
        usort($indexed, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return array_column($indexed, 'listener');
    }

    /**
     * Vérifie si un événement a des listeners
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Retourne tous les listeners pour un événement
     */
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * Supprime tous les listeners d'un événement
     */
    public function removeListeners(string $eventName): self
    {
        unset($this->listeners[$eventName]);
        unset($this->priorities[$eventName]);
        return $this;
    }

    /**
     * Supprime tous les listeners
     */
    public function clearListeners(): self
    {
        $this->listeners = [];
        $this->priorities = [];
        return $this;
    }
}
