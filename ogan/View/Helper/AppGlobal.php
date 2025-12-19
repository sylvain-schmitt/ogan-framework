<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🌐 APP GLOBAL - Variable globale pour les templates
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Cette classe fournit un accès unifié aux données globales de l'application
 * dans les templates, similaire à Symfony/Twig.
 * 
 * UTILISATION :
 * -------------
 * {% if app.user %}
 *     Bienvenue {{ app.user.name }}
 * {% endif %}
 * 
 * {% if app.session.get('panier') %}
 *     Panier : {{ app.session.get('panier')|count }} articles
 * {% endif %}
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View\Helper;

use Ogan\Session\SessionInterface;
use Ogan\Http\RequestInterface;

class AppGlobal
{
    private ?SessionInterface $session = null;
    private ?RequestInterface $request = null;
    private mixed $user = null;

    /**
     * Définit la session
     */
    public function setSession(?SessionInterface $session): void
    {
        $this->session = $session;
    }

    /**
     * Définit la requête
     */
    public function setRequest(?RequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Définit l'utilisateur courant
     */
    public function setUser(mixed $user): void
    {
        $this->user = $user;
    }

    /**
     * Récupère l'utilisateur connecté
     * 
     * Usage: app.user, app.user.name, app.user.email
     */
    public function getUser(): mixed
    {
        return $this->user;
    }

    /**
     * Récupère la session
     * 
     * Usage: app.session.get('key'), app.session.has('key')
     */
    public function getSession(): ?SessionInterface
    {
        return $this->session;
    }

    /**
     * Récupère la requête
     * 
     * Usage: app.request.getMethod(), app.request.getUri()
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * Récupère les messages flash
     * 
     * Usage: app.flashes
     */
    public function getFlashes(): array
    {
        if (!$this->session) {
            return [];
        }
        
        $flashes = [];
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $messages = $this->session->get('_flash.' . $type, []);
            if (!empty($messages)) {
                $flashes[$type] = $messages;
                $this->session->remove('_flash.' . $type);
            }
        }
        
        return $flashes;
    }

    /**
     * Vérifie si l'environnement est en debug
     */
    public function getDebug(): bool
    {
        return \Ogan\Config\Config::get('app.debug', false);
    }

    /**
     * Récupère l'environnement (dev, prod, test)
     */
    public function getEnvironment(): string
    {
        return \Ogan\Config\Config::get('app.env', 'dev');
    }
}
