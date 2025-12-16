<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗳️ ROLE VOTER - Vérifie les rôles simples
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Vérifie si l'utilisateur possède un rôle donné.
 * Supporte uniquement les attributs commençant par "ROLE_".
 * 
 * EXEMPLE:
 * --------
 * $voter->vote($user, 'ROLE_ADMIN', null);
 * // Vérifie si $user->getRoles() contient 'ROLE_ADMIN'
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Authorization\Voter;

use Ogan\Security\Authorization\VoterInterface;
use Ogan\Security\UserInterface;

class RoleVoter implements VoterInterface
{
    private string $prefix;

    public function __construct(string $prefix = 'ROLE_')
    {
        $this->prefix = $prefix;
    }

    /**
     * Supporte uniquement les attributs commençant par le préfixe (ROLE_)
     */
    public function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, $this->prefix);
    }

    /**
     * Vote pour l'accès basé sur les rôles de l'utilisateur
     */
    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        if (!$this->supports($attribute, $subject)) {
            return self::ACCESS_ABSTAIN;
        }

        if ($user === null) {
            return self::ACCESS_DENIED;
        }

        $roles = $user->getRoles();

        // Vérifier si l'utilisateur a le rôle demandé
        if (in_array($attribute, $roles, true)) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }
}
