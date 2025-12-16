<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗳️ VOTER INTERFACE - Interface pour les Voters d'autorisation
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Un Voter décide si un utilisateur a le droit d'effectuer une action
 * sur un sujet donné.
 * 
 * EXEMPLE:
 * --------
 * - attribute: 'edit' ou 'ROLE_ADMIN'
 * - subject: Post, User, null (pour les rôles)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Authorization;

use Ogan\Security\UserInterface;

interface VoterInterface
{
    // Constantes de vote
    public const ACCESS_GRANTED = 1;
    public const ACCESS_ABSTAIN = 0;
    public const ACCESS_DENIED = -1;

    /**
     * Détermine si ce Voter supporte l'attribut et le sujet donnés
     */
    public function supports(string $attribute, mixed $subject): bool;

    /**
     * Vote pour ou contre l'accès
     * 
     * @param UserInterface|null $user L'utilisateur courant
     * @param string $attribute L'attribut à vérifier (ex: 'edit', 'ROLE_ADMIN')
     * @param mixed $subject Le sujet (ex: Post, null)
     * @return int ACCESS_GRANTED, ACCESS_ABSTAIN, ou ACCESS_DENIED
     */
    public function vote(?UserInterface $user, string $attribute, mixed $subject): int;
}
