<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 SECURITY MIDDLEWARE - Vérifie les autorisations IsGranted
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Middleware qui intercepte les requêtes et vérifie les attributs
 * #[IsGranted] sur les contrôleurs/méthodes.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Http\Response;
use Ogan\Security\Authorization\AuthorizationChecker;
use Ogan\Security\Authorization\AccessDeniedException;
use Ogan\Security\Attribute\IsGranted;
use Ogan\Security\UserInterface;
use Ogan\Config\Config;

class SecurityMiddleware implements MiddlewareInterface
{
    private ?UserInterface $user = null;
    private AuthorizationChecker $authChecker;

    public function __construct(?UserInterface $user = null)
    {
        $this->user = $user;
        $this->authChecker = new AuthorizationChecker($user);
    }

    /**
     * Définir l'utilisateur courant
     */
    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;
        $this->authChecker->setUser($user);
        return $this;
    }

    /**
     * Vérifie les attributs IsGranted pour la route courante
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Ce middleware vérifie uniquement au niveau global
        // La vérification spécifique aux routes est faite via checkAccess()
        
        return $next($request);
    }

    /**
     * Vérifie l'accès pour une méthode de contrôleur
     * 
     * @param string $controllerClass FQCN du contrôleur
     * @param string $methodName Nom de la méthode
     * @param array $params Paramètres de la route (pour résoudre le sujet)
     * @return bool true si autorisé
     * @throws AccessDeniedException si accès refusé
     */
    public function checkAccess(string $controllerClass, string $methodName, array $params = []): bool
    {
        $refClass = new \ReflectionClass($controllerClass);
        $refMethod = $refClass->getMethod($methodName);

        // Collecter les attributs IsGranted de la classe
        $classGrants = $refClass->getAttributes(IsGranted::class);
        
        // Collecter les attributs IsGranted de la méthode
        $methodGrants = $refMethod->getAttributes(IsGranted::class);

        // Vérifier tous les attributs
        foreach (array_merge($classGrants, $methodGrants) as $grantAttribute) {
            /** @var IsGranted $grant */
            $grant = $grantAttribute->newInstance();
            
            // Résoudre le sujet si spécifié
            $subject = null;
            if ($grant->subject !== null && isset($params[$grant->subject])) {
                $subject = $params[$grant->subject];
            }

            // Vérifier l'autorisation
            if (!$this->authChecker->isGranted($grant->attribute, $subject)) {
                throw new AccessDeniedException($grant->message);
            }
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * Vérifie une permission
     */
    public function isGranted(string $attribute, mixed $subject = null): bool
    {
        return $this->authChecker->isGranted($attribute, $subject);
    }

    /**
     * Refuse l'accès si non autorisé
     * 
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGranted(string $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->isGranted($attribute, $subject)) {
            throw new AccessDeniedException($message);
        }
    }
}
