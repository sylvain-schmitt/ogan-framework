<?php

namespace Ogan\Http;

class Request implements RequestInterface
{
    public string $method;
    public string $uri;
    public array $query;
    public array $post;
    public array $server;
    public array $cookies;
    public array $files;
    public string $rawInput;

    public function __construct(array $query = [], array $post = [], array $server = [], array $cookies = [], array $files = [], string $rawInput = '')
    {
        $this->method = $server['REQUEST_METHOD'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($server['REQUEST_URI'] ?? $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $query ?: $_GET;
        $this->post = $post ?: $_POST;
        $this->server = $server ?: $_SERVER;
        $this->cookies = $cookies ?: $_COOKIE;
        $this->files = $files ?: $_FILES;
        $this->rawInput = $rawInput ?: file_get_contents('php://input');
    }

    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        // Si la requête est JSON, on regarde d'abord dans le body décodé
        if ($this->isJson()) {
            $data = $this->json();
            return $data[$key] ?? $default;
        }
        return $this->post[$key] ?? $default;
    }

    public function json(): array
    {
        $decoded = json_decode($this->rawInput, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    /**
     * Vérifie si la requête attend une réponse JSON
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type') ?? '';
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    /**
     * Vérifie si c'est une requête AJAX (XMLHttpRequest)
     */
    public function isAjax(): bool
    {
        return 'xmlhttprequest' === strtolower($this->getHeader('X-Requested-With') ?? '');
    }

    /**
     * Récupère l'IP du client
     */
    public function getClientIp(): string
    {
        $forwardedFor = $this->getHeader('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }
        
        $realIp = $this->getHeader('X-Real-IP');
        if ($realIp) {
            return $realIp;
        }

        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Récupère un fichier uploadé
     */
    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Récupère tous les fichiers
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Récupère un cookie
     */
    public function getCookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    // ... (rest of methods)

    /**
     * Retourne la méthode HTTP
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retourne l'URI demandée
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Vérifie si la requête utilise la méthode spécifiée
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Récupère un header HTTP spécifique
     * 
     * Les headers dans $_SERVER sont préfixés par HTTP_ et en majuscules.
     * Par exemple : "X-Forwarded-For" devient "HTTP_X_FORWARDED_FOR"
     */
    public function getHeader(string $name): ?string
    {
        // Convertir le nom du header au format $_SERVER
        // Ex: "X-Forwarded-For" -> "HTTP_X_FORWARDED_FOR"
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        
        // Certains headers ont des clés spéciales sans le préfixe HTTP_
        $specialHeaders = [
            'CONTENT_TYPE' => 'CONTENT_TYPE',
            'CONTENT_LENGTH' => 'CONTENT_LENGTH',
        ];
        
        $normalizedName = strtoupper(str_replace('-', '_', $name));
        if (isset($specialHeaders[$normalizedName])) {
            return $this->server[$specialHeaders[$normalizedName]] ?? null;
        }
        
        return $this->server[$serverKey] ?? null;
    }

    /**
     * Récupère tous les headers HTTP
     * 
     * Extrait tous les headers de $_SERVER (clés commençant par HTTP_)
     * et les retourne dans un format normalisé.
     */
    public function getHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            // Les headers HTTP commencent par HTTP_
            if (str_starts_with($key, 'HTTP_')) {
                // Convertir HTTP_X_FORWARDED_FOR -> X-Forwarded-For
                $headerName = str_replace('_', '-', substr($key, 5));
                // Formatter proprement (ex: Content-Type) pour l'affichage, 
                // mais attention la RFC dit que c'est case-insensitive.
                // Ici on garde tel quel ou on uniformise.
                $headers[$headerName] = $value;
            }
        }
        
        // Ajouter les headers spéciaux
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $this->server['CONTENT_LENGTH'];
        }
        
        return $headers;
    }

    // ─────────────────────────────────────────────────────────────
    // Session Management
    // ─────────────────────────────────────────────────────────────

    private ?\Ogan\Session\SessionInterface $session = null;

    public function setSession(\Ogan\Session\SessionInterface $session): void
    {
        $this->session = $session;
    }

    public function getSession(): \Ogan\Session\SessionInterface
    {
        if (!$this->session) {
            throw new \LogicException('La session n\'a pas été initialisée dans la requête.');
        }
        return $this->session;
    }

    public function hasSession(): bool
    {
        return $this->session !== null;
    }
}
