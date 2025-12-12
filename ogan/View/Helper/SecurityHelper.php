<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 SECURITYHELPER - Helpers de sécurité pour les vues
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Fournit des helpers pour la protection CSRF dans les formulaires.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View\Helper;

use Ogan\Security\CsrfManager;

class SecurityHelper
{
    private ?CsrfManager $csrfManager = null;

    public function setCsrfManager(CsrfManager $manager): void
    {
        $this->csrfManager = $manager;
    }

    /**
     * Génère un token CSRF
     */
    public function csrfToken(): string
    {
        if (!$this->csrfManager) {
            return '';
        }
        return $this->csrfManager->getToken();
    }

    /**
     * Génère un champ hidden avec le token CSRF
     */
    public function csrfInput(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}
