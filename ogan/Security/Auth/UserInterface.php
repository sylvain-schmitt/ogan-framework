<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👤 USERINTERFACE - Interface pour les utilisateurs authentifiables
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Interface que doit implémenter le modèle User pour être compatible
 * avec le système d'authentification.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Auth;

interface UserInterface
{
    /**
     * Retourne l'identifiant unique de l'utilisateur
     */
    public function getId(): ?int;

    /**
     * Retourne l'identifiant utilisé pour l'authentification (généralement l'email)
     */
    public function getUserIdentifier(): string;

    /**
     * Retourne le mot de passe hashé
     */
    public function getPassword(): ?string;

    /**
     * Retourne les rôles de l'utilisateur
     * @return array<string> Ex: ['ROLE_USER', 'ROLE_ADMIN']
     */
    public function getRoles(): array;
}
