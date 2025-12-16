<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗳️ ROLE HIERARCHY VOTER - Gère la hiérarchie des rôles
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Permet de définir une hiérarchie de rôles où un rôle parent
 * hérite automatiquement des permissions de ses rôles enfants.
 * 
 * CONFIGURATION (parameters.yaml):
 * ---------------------------------
 * security:
 *   role_hierarchy:
 *     ROLE_ADMIN: [ROLE_USER]
 *     ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
 * 
 * EXEMPLE:
 * --------
 * Un utilisateur avec ROLE_ADMIN aura aussi ROLE_USER.
 * Un utilisateur avec ROLE_SUPER_ADMIN aura ROLE_ADMIN et ROLE_USER.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Authorization\Voter;

use Ogan\Security\Authorization\VoterInterface;
use Ogan\Security\UserInterface;

class RoleHierarchyVoter implements VoterInterface
{
    private string $prefix;
    
    /**
     * @var array<string, array<string>> Hiérarchie des rôles
     */
    private array $hierarchy;

    /**
     * @var array<string, array<string>> Cache des rôles résolus
     */
    private array $resolvedCache = [];

    public function __construct(array $hierarchy, string $prefix = 'ROLE_')
    {
        $this->hierarchy = $hierarchy;
        $this->prefix = $prefix;
    }

    /**
     * Supporte uniquement les rôles
     */
    public function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, $this->prefix);
    }

    /**
     * Vote en tenant compte de la hiérarchie
     */
    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        if (!$this->supports($attribute, $subject)) {
            return self::ACCESS_ABSTAIN;
        }

        if ($user === null) {
            return self::ACCESS_DENIED;
        }

        // Résoudre tous les rôles de l'utilisateur avec la hiérarchie
        $reachableRoles = $this->getReachableRoles($user->getRoles());

        // Vérifier si le rôle demandé est accessible
        if (in_array($attribute, $reachableRoles, true)) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_ABSTAIN; // Laisser le RoleVoter décider
    }

    /**
     * Résout tous les rôles accessibles depuis les rôles donnés
     * 
     * @param array<string> $roles Les rôles de base
     * @return array<string> Tous les rôles accessibles
     */
    public function getReachableRoles(array $roles): array
    {
        // Créer une clé de cache
        $cacheKey = implode('|', $roles);
        
        if (isset($this->resolvedCache[$cacheKey])) {
            return $this->resolvedCache[$cacheKey];
        }

        $reachableRoles = $roles;
        $added = true;

        // Résolution transitive
        while ($added) {
            $added = false;
            foreach ($reachableRoles as $role) {
                if (isset($this->hierarchy[$role]) && is_array($this->hierarchy[$role])) {
                    foreach ($this->hierarchy[$role] as $inheritedRole) {
                        if (!in_array($inheritedRole, $reachableRoles, true)) {
                            $reachableRoles[] = $inheritedRole;
                            $added = true;
                        }
                    }
                }
            }
        }

        $this->resolvedCache[$cacheKey] = $reachableRoles;

        return $reachableRoles;
    }
}
