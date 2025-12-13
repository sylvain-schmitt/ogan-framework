<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 REMEMBER ME SERVICE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Handles persistent login via "Remember Me" tokens.
 * Tokens are stored in DB and as HTTP cookies.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security;

use Ogan\Database\Database;

class RememberMeService
{
    private const COOKIE_NAME = 'remember_me';
    private const COOKIE_LIFETIME = 60 * 60 * 24 * 30; // 30 days
    private const TOKEN_LENGTH = 64;

    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Create a remember me token for a user
     */
    public function createToken(int $userId): string
    {
        // Generate secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
        $hashedToken = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::COOKIE_LIFETIME);

        // Store hashed token in database
        $stmt = $this->pdo->prepare(
            'INSERT INTO remember_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $hashedToken, $expiresAt]);

        return $token;
    }

    /**
     * Set the remember me cookie
     */
    public function setCookie(string $token): void
    {
        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires' => time() + self::COOKIE_LIFETIME,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Get the remember me token from cookie
     */
    public function getTokenFromCookie(): ?string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }

    /**
     * Validate a token and return the user ID if valid
     */
    public function validateToken(string $token): ?int
    {
        $hashedToken = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()'
        );
        $stmt->execute([$hashedToken]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result) {
            return (int) $result['user_id'];
        }

        return null;
    }

    /**
     * Delete a specific token
     */
    public function deleteToken(string $token): void
    {
        $hashedToken = hash('sha256', $token);

        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE token = ?');
        $stmt->execute([$hashedToken]);
    }

    /**
     * Delete all tokens for a user
     */
    public function deleteAllUserTokens(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    /**
     * Clear the remember me cookie
     */
    public function clearCookie(): void
    {
        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE expires_at < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }
}
