<?php

namespace Ogan\Security;

use Ogan\Session\SessionInterface;

class CsrfManager
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(
        private SessionInterface $session
    ) {}

    /**
     * Génère (ou récupère) le token CSRF
     */
    public function getToken(): string
    {
        if (!$this->session->has(self::SESSION_KEY)) {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $this->session->get(self::SESSION_KEY);
    }

    /**
     * Valide un token soumis
     */
    public function validateToken(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $storedToken = $this->session->get(self::SESSION_KEY);
        
        if (!$storedToken) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Régénère le token (après login par exemple)
     */
    public function refreshToken(): string
    {
        $this->session->remove(self::SESSION_KEY);
        return $this->getToken();
    }
}
