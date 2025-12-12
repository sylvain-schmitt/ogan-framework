<?php

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Http\Response;
use Ogan\Security\CsrfManager;

class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CsrfManager $csrfManager
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // On ne vérifie que les méthodes qui modifient l'état
        $method = $request->getMethod();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Récupérer le token depuis le body ($_POST) ou header
        $token = $request->post('_csrf_token');
        
        // Ou depuis header X-CSRF-TOKEN (pour AJAX)
        if (!$token) {
            $token = $request->getHeader('X-CSRF-TOKEN');
        }

        if (!$this->csrfManager->validateToken($token)) {
            // Echec de validation CSRF
            return new Response('Invalid CSRF Token', 403);
        }

        return $next($request);
    }
}
