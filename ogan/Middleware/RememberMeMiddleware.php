<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 REMEMBER ME MIDDLEWARE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Checks for remember me cookie and auto-logs in the user if valid.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Session\Session;
use Ogan\Security\RememberMeService;
use Ogan\Database\Database;

class RememberMeMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request - check for remember me cookie and auto-login
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Start session if not already started
        $session = new Session();
        
        // Skip if user is already logged in
        if ($session->has('_auth_user_id')) {
            return $next($request);
        }

        // Check for remember me cookie
        try {
            $rememberMeService = new RememberMeService();
            $token = $rememberMeService->getTokenFromCookie();

            if ($token) {
                $userId = $rememberMeService->validateToken($token);

                if ($userId) {
                    // Auto-login the user
                    $session->regenerate();
                    $session->set('_auth_user_id', $userId);

                    // Get user roles from database
                    try {
                        $pdo = Database::getConnection();
                        $stmt = $pdo->prepare('SELECT roles FROM users WHERE id = ?');
                        $stmt->execute([$userId]);
                        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

                        if ($result) {
                            $roles = json_decode($result['roles'], true) ?? ['ROLE_USER'];
                            $session->set('_auth_user_roles', $roles);
                        }
                    } catch (\Exception $e) {
                        // If we can't get roles, set default
                        $session->set('_auth_user_roles', ['ROLE_USER']);
                    }
                } else {
                    // Invalid or expired token, clear the cookie
                    $rememberMeService->clearCookie();
                }
            }
        } catch (\Exception $e) {
            // Silently fail if DB not ready (initial setup, etc.)
        }

        return $next($request);
    }
}
