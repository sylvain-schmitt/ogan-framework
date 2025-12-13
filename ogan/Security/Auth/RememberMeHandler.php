<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔄 REMEMBERMEHANDLER - Gestion "Se souvenir de moi"
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Gère les tokens de connexion persistante via cookies.
 * Permet aux utilisateurs de rester connectés entre les sessions.
 * 
 * SÉCURITÉ :
 * ----------
 * - Token hashé en BDD (pas en clair)
 * - Rotation du token après usage (évite le vol de token)
 * - Expiration configurable
 * - Cookie HttpOnly + Secure (en prod)
 * 
 * TABLE REQUISE :
 * ---------------
 * CREATE TABLE remember_tokens (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     user_id INT NOT NULL,
 *     token VARCHAR(255) NOT NULL,
 *     expires_at DATETIME NOT NULL,
 *     created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 * );
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Auth;

use PDO;

class RememberMeHandler
{
    private const COOKIE_NAME = 'remember_me';
    private const TOKEN_LENGTH = 64;
    private const DEFAULT_EXPIRY_DAYS = 30;

    public function __construct(
        private PDO $pdo,
        private UserProviderInterface $userProvider,
        private int $expiryDays = self::DEFAULT_EXPIRY_DAYS,
        private bool $secure = false // true en production (HTTPS)
    ) {}

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UN TOKEN REMEMBER ME
     * ═══════════════════════════════════════════════════════════════════
     */
    public function createRememberMeToken(UserInterface $user): void
    {
        // Générer un token aléatoire
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
        $hashedToken = hash('sha256', $token);
        
        $expiresAt = new \DateTime("+{$this->expiryDays} days");

        // Supprimer les anciens tokens de cet utilisateur
        $this->clearUserTokens($user->getId());

        // Stocker le token hashé en BDD
        $stmt = $this->pdo->prepare(
            "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $user->getId(),
            $hashedToken,
            $expiresAt->format('Y-m-d H:i:s')
        ]);

        // Créer le cookie avec le token en clair (sera hashé pour comparaison)
        $cookieValue = $user->getId() . ':' . $token;
        
        setcookie(
            self::COOKIE_NAME,
            $cookieValue,
            [
                'expires' => $expiresAt->getTimestamp(),
                'path' => '/',
                'domain' => '',
                'secure' => $this->secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AUTO-LOGIN VIA TOKEN
     * ═══════════════════════════════════════════════════════════════════
     */
    public function autoLogin(): ?UserInterface
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return null;
        }

        $cookieValue = $_COOKIE[self::COOKIE_NAME];
        $parts = explode(':', $cookieValue, 2);
        
        if (count($parts) !== 2) {
            $this->clearRememberMeToken();
            return null;
        }

        [$userId, $token] = $parts;
        $hashedToken = hash('sha256', $token);

        // Chercher le token en BDD
        $stmt = $this->pdo->prepare(
            "SELECT * FROM remember_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()"
        );
        $stmt->execute([(int)$userId, $hashedToken]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            $this->clearRememberMeToken();
            return null;
        }

        // Charger l'utilisateur
        $user = $this->userProvider->loadUserById((int)$userId);
        
        if (!$user) {
            $this->clearRememberMeToken();
            return null;
        }

        // Rotation du token (sécurité)
        $this->createRememberMeToken($user);

        return $user;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUPPRIMER LE TOKEN REMEMBER ME
     * ═══════════════════════════════════════════════════════════════════
     */
    public function clearRememberMeToken(): void
    {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $cookieValue = $_COOKIE[self::COOKIE_NAME];
            $parts = explode(':', $cookieValue, 2);
            
            if (count($parts) === 2) {
                $userId = (int)$parts[0];
                $this->clearUserTokens($userId);
            }
        }

        // Supprimer le cookie
        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $this->secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUPPRIMER TOUS LES TOKENS D'UN UTILISATEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    private function clearUserTokens(?int $userId): void
    {
        if ($userId === null) {
            return;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * NETTOYER LES TOKENS EXPIRÉS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * À appeler périodiquement (cron job)
     */
    public function purgeExpiredTokens(): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
