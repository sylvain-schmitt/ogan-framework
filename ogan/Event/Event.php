<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 EVENT - Classe de base pour les événements
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Tous les événements du framework héritent de cette classe.
 * 
 * USAGE :
 * -------
 * class UserCreatedEvent extends Event {
 *     public function __construct(public User $user) {}
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

class Event
{
    /**
     * Indique si la propagation de l'événement doit s'arrêter
     */
    private bool $propagationStopped = false;

    /**
     * Arrête la propagation de l'événement aux autres listeners
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Vérifie si la propagation est arrêtée
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
