<?php

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Http\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $limit;
    private int $window;
    private string $storageDir;

    /**
     * @param int $limit Nombre max de requêtes
     * @param int $window Fenêtre de temps en secondes
     * @param string|null $storageDir Répertoire de stockage (défaut: temp dir)
     */
    public function __construct(int $limit = 60, int $window = 60, ?string $storageDir = null)
    {
        $this->limit = $limit;
        $this->window = $window;
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/ogan_ratelimit';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $file = $this->storageDir . '/' . md5($ip);
        
        $data = ['start_time' => time(), 'count' => 0];

        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content) {
                $data = json_decode($content, true) ?: $data;
            }
        }

        // Vérifier si la fenêtre est écoulée/reset
        if (time() - $data['start_time'] > $this->window) {
            $data['start_time'] = time();
            $data['count'] = 0;
        }

        // Incrémenter (la requête courante compte)
        $data['count']++;

        // Sauvegarder
        file_put_contents($file, json_encode($data));

        $remaining = max(0, $this->limit - $data['count']);
        $reset = $data['start_time'] + $this->window;

        // Si limite dépassée
        if ($data['count'] > $this->limit) {
            $response = new Response("Too Many Requests", 429);
            $response->setHeader('X-RateLimit-Limit', (string)$this->limit);
            $response->setHeader('X-RateLimit-Remaining', '0');
            $response->setHeader('X-RateLimit-Reset', (string)$reset);
            $response->setHeader('Retry-After', (string)($reset - time()));
            return $response;
        }

        // Continuer
        $response = $next($request);

        // Ajouter headers
        if ($response instanceof Response) {
            $response->setHeader('X-RateLimit-Limit', (string)$this->limit);
            $response->setHeader('X-RateLimit-Remaining', (string)$remaining);
            $response->setHeader('X-RateLimit-Reset', (string)$reset);
        }

        return $response;
    }
}
