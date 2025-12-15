<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 CSRF TOKEN MANAGER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Manages CSRF tokens for form protection.
 * Tokens are stored in the session and validated on form submission.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security;

use Ogan\Session\Session;

class CsrfTokenManager
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = '_csrf_tokens';

    private Session $session;

    public function __construct(?Session $session = null)
    {
        $this->session = $session ?? new Session();
    }

    /**
     * Generate a CSRF token for a form
     * 
     * @param string $tokenId Unique identifier for the form
     * @return string The generated token
     */
    public function generateToken(string $tokenId): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        $tokens = $this->session->get(self::SESSION_KEY, []);
        $tokens[$tokenId] = [
            'value' => $token,
            'created_at' => time()
        ];
        $this->session->set(self::SESSION_KEY, $tokens);
        
        return $token;
    }

    /**
     * Get an existing token or generate a new one
     * 
     * @param string $tokenId Unique identifier for the form
     * @return string The token
     */
    public function getToken(string $tokenId): string
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);
        
        if (isset($tokens[$tokenId])) {
            return $tokens[$tokenId]['value'];
        }
        
        return $this->generateToken($tokenId);
    }

    /**
     * Validate a CSRF token
     * 
     * @param string $tokenId The form identifier
     * @param string $token The token to validate
     * @return bool Whether the token is valid
     */
    public function isTokenValid(string $tokenId, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $tokens = $this->session->get(self::SESSION_KEY, []);
        
        if (!isset($tokens[$tokenId])) {
            return false;
        }

        $storedToken = $tokens[$tokenId]['value'];
        
        // Use hash_equals for timing-safe comparison
        return hash_equals($storedToken, $token);
    }

    /**
     * Remove a token after use (optional, for single-use tokens)
     * 
     * @param string $tokenId The form identifier
     */
    public function removeToken(string $tokenId): void
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);
        unset($tokens[$tokenId]);
        $this->session->set(self::SESSION_KEY, $tokens);
    }

    /**
     * Clean up expired tokens (older than 1 hour)
     */
    public function cleanupExpiredTokens(): void
    {
        $tokens = $this->session->get(self::SESSION_KEY, []);
        $now = time();
        $maxAge = 3600; // 1 hour

        foreach ($tokens as $tokenId => $data) {
            if (($now - $data['created_at']) > $maxAge) {
                unset($tokens[$tokenId]);
            }
        }

        $this->session->set(self::SESSION_KEY, $tokens);
    }
}
