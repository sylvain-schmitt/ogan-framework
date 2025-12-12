<?php

namespace Ogan\Http;

class Response implements ResponseInterface
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';
    private array $cookies = [];
    
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Définit le code de statut HTTP
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;  // Pour le chaînage
    }

    /**
     * Récupère le code de statut HTTP
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Définit le contenu de la réponse
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Récupère le contenu de la réponse
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Ajoute un cookie à la réponse
     */
    public function setCookie(string $name, string $value = "", int $expires = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): self
    {
        $this->cookies[] = compact('name', 'value', 'expires', 'path', 'domain', 'secure', 'httponly');
        return $this;
    }

    /**
     * Envoie la réponse HTTP
     */
    public function send(?string $content = null): void
    {
        if ($content !== null) {
            $this->setContent($content);
        }

        // Vérifie si les headers n'ont pas déjà été envoyés
        if (!headers_sent()) {
            // Envoyer les headers
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }

            // Envoyer les cookies
            foreach ($this->cookies as $cookie) {
                setcookie(
                    $cookie['name'],
                    $cookie['value'],
                    $cookie['expires'],
                    $cookie['path'],
                    $cookie['domain'],
                    $cookie['secure'],
                    $cookie['httponly']
                );
            }
            
            http_response_code($this->statusCode);
        }

        echo $this->content;
    }

    /**
     * Prépare une réponse JSON
     */
    public function json(array $data, int $status = 200): self
    {
        $this->setStatusCode($status);
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        return $this;
    }

    /**
     * Prépare une redirection
     */
    public function redirect(string $url, int $status = 302): self
    {
        $this->setStatusCode($status);
        $this->setHeader('Location', $url);
        return $this;
    }

    /**
     * Définit un header HTTP
     * 
     * Stocke le header dans un tableau pour un envoi ultérieur.
     * Les headers seront envoyés lors de l'appel à send().
     * 
     * EXEMPLES :
     * $response->setHeader('Content-Type', 'application/json');
     * $response->setHeader('Access-Control-Allow-Origin', '*');
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;  // Pour le chaînage
    }

    /**
     * Récupère un header HTTP
     * 
     * Retourne la valeur d'un header précédemment défini,
     * ou null si le header n'existe pas.
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }
}
