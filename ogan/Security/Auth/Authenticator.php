<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 AUTHENTICATOR - Service principal d'authentification
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Gère l'authentification des utilisateurs : login, logout, vérification
 * de session, et intégration avec RememberMe et CSRF.
 * 
 * USAGE :
 * -------
 * $auth = new Authenticator($session, $userProvider, $passwordHasher, $csrfManager);
 * 
 * // Login
 * $user = $auth->login('email@example.com', 'password', $rememberMe = true);
 * 
 * // Vérifier si authentifié
 * if ($auth->isAuthenticated()) {
 *     $user = $auth->getUser();
 * }
 * 
 * // Vérifier un rôle
 * if ($auth->isGranted('ROLE_ADMIN')) { ... }
 * 
 * // Logout
 * $auth->logout();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Auth;

use Ogan\Session\SessionInterface;
use Ogan\Security\PasswordHasher;
use Ogan\Security\CsrfManager;

class Authenticator implements AuthenticatorInterface
{
    private const SESSION_USER_ID = '_auth_user_id';
    private const SESSION_USER_ROLES = '_auth_user_roles';

    private ?UserInterface $user = null;
    private bool $initialized = false;

    public function __construct(
        private SessionInterface $session,
        private UserProviderInterface $userProvider,
        private PasswordHasher $passwordHasher,
        private ?CsrfManager $csrfManager = null,
        private ?RememberMeHandler $rememberMeHandler = null
    ) {}

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AUTHENTIFIER UN UTILISATEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function login(string $identifier, string $password, bool $rememberMe = false): ?UserInterface
    {
        // Charger l'utilisateur
        $user = $this->userProvider->loadUserByIdentifier($identifier);
        
        if (!$user) {
            return null;
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->verify($password, $user->getPassword() ?? '')) {
            return null;
        }

        // Authentification réussie
        $this->authenticateUser($user);

        // Remember Me
        if ($rememberMe && $this->rememberMeHandler) {
            $this->rememberMeHandler->createRememberMeToken($user);
        }

        // Régénérer le token CSRF après login (sécurité)
        if ($this->csrfManager) {
            $this->csrfManager->refreshToken();
        }

        return $user;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉCONNECTER L'UTILISATEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function logout(): void
    {
        // Supprimer le token Remember Me
        if ($this->rememberMeHandler) {
            $this->rememberMeHandler->clearRememberMeToken();
        }

        // Nettoyer la session
        $this->session->remove(self::SESSION_USER_ID);
        $this->session->remove(self::SESSION_USER_ROLES);
        
        // Régénérer l'ID de session (sécurité contre session fixation)
        $this->session->regenerate();

        // Régénérer le token CSRF
        if ($this->csrfManager) {
            $this->csrfManager->refreshToken();
        }

        $this->user = null;
        $this->initialized = false;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UN UTILISATEUR EST AUTHENTIFIÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    public function isAuthenticated(): bool
    {
        $this->initializeUser();
        return $this->user !== null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER L'UTILISATEUR ACTUEL
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getUser(): ?UserInterface
    {
        $this->initializeUser();
        return $this->user;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI L'UTILISATEUR A UN RÔLE
     * ═══════════════════════════════════════════════════════════════════
     */
    public function isGranted(string $role): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $roles = $this->user->getRoles();
        
        // Hiérarchie des rôles : ROLE_ADMIN inclut ROLE_USER
        if ($role === 'ROLE_USER' && in_array('ROLE_ADMIN', $roles, true)) {
            return true;
        }

        return in_array($role, $roles, true);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * INITIALISER L'UTILISATEUR DEPUIS LA SESSION
     * ═══════════════════════════════════════════════════════════════════
     */
    private function initializeUser(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // Vérifier la session
        $userId = $this->session->get(self::SESSION_USER_ID);
        
        if ($userId) {
            $this->user = $this->userProvider->loadUserById((int)$userId);
            return;
        }

        // Vérifier Remember Me
        if ($this->rememberMeHandler) {
            $user = $this->rememberMeHandler->autoLogin();
            if ($user) {
                $this->authenticateUser($user);
            }
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * STOCKER L'UTILISATEUR EN SESSION
     * ═══════════════════════════════════════════════════════════════════
     */
    private function authenticateUser(UserInterface $user): void
    {
        // Régénérer l'ID de session (sécurité contre session fixation)
        $this->session->regenerate();

        // Stocker les infos en session
        $this->session->set(self::SESSION_USER_ID, $user->getId());
        $this->session->set(self::SESSION_USER_ROLES, $user->getRoles());

        $this->user = $user;
        $this->initialized = true;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AUTHENTIFIER DIRECTEMENT UN UTILISATEUR (sans mot de passe)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utile après inscription ou reset password
     */
    public function loginUser(UserInterface $user): void
    {
        $this->authenticateUser($user);

        if ($this->csrfManager) {
            $this->csrfManager->refreshToken();
        }
    }
}
