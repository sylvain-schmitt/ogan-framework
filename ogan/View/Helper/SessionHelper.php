<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📦 SESSIONHELPER - Helpers de session pour les vues
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Fournit des helpers pour accéder à la session et aux flash messages.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View\Helper;

use Ogan\Session\SessionInterface;

class SessionHelper
{
    private ?SessionInterface $session = null;

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    public function getSession(): ?SessionInterface
    {
        return $this->session;
    }

    /**
     * Vérifie si un message flash existe
     */
    public function hasFlash(string $key): bool
    {
        if (!$this->session) {
            return false;
        }
        return $this->session->hasFlash($key);
    }

    /**
     * Récupère un message flash (et le supprime)
     */
    public function getFlash(string $key, ?string $default = null): ?string
    {
        if (!$this->session) {
            return $default;
        }
        return $this->session->getFlash($key, $default);
    }

    /**
     * Récupère une valeur de la session
     */
    public function get(string $key, $default = null)
    {
        if (!$this->session) {
            return $default;
        }
        return $this->session->get($key, $default);
    }

    /**
     * Définit une valeur dans la session
     */
    public function set(string $key, $value): void
    {
        if ($this->session) {
            $this->session->set($key, $value);
        }
    }

    /**
     * Vérifie si une clé existe dans la session
     */
    public function has(string $key): bool
    {
        if (!$this->session) {
            return false;
        }
        return $this->session->has($key);
    }

    /**
     * Récupère TOUS les messages flash de tous les types
     */
    public function getAllFlashes(): array
    {
        if (!$this->session) {
            return [];
        }
        return $this->session->getAllFlashes();
    }
}
