<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 AUTHENTICATORINTERFACE - Interface du service d'authentification
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Auth;

interface AuthenticatorInterface
{
    /**
     * Authentifie un utilisateur avec ses identifiants
     * 
     * @param string $identifier Email ou username
     * @param string $password Mot de passe en clair
     * @param bool $rememberMe Activer "Se souvenir de moi"
     * @return UserInterface|null L'utilisateur authentifié ou null
     */
    public function login(string $identifier, string $password, bool $rememberMe = false): ?UserInterface;

    /**
     * Déconnecte l'utilisateur actuel
     */
    public function logout(): void;

    /**
     * Vérifie si un utilisateur est authentifié
     */
    public function isAuthenticated(): bool;

    /**
     * Retourne l'utilisateur actuellement authentifié
     */
    public function getUser(): ?UserInterface;

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function isGranted(string $role): bool;
}
