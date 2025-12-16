<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👤 USER INTERFACE - Interface pour les utilisateurs authentifiables
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security;

interface UserInterface
{
    /**
     * Retourne l'identifiant unique de l'utilisateur
     */
    public function getId(): mixed;

    /**
     * Retourne les rôles de l'utilisateur
     * @return array<string>
     */
    public function getRoles(): array;
}
