<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👥 USERPROVIDERINTERFACE - Interface pour charger les utilisateurs
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Interface pour le service qui charge les utilisateurs depuis la BDD.
 * Permet de découpler l'authentification de l'implémentation User.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Auth;

interface UserProviderInterface
{
    /**
     * Charge un utilisateur par son identifiant (email, username, etc.)
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface;

    /**
     * Charge un utilisateur par son ID
     */
    public function loadUserById(int $id): ?UserInterface;

    /**
     * Rafraîchit l'utilisateur (recharge depuis la BDD)
     */
    public function refreshUser(UserInterface $user): ?UserInterface;
}
