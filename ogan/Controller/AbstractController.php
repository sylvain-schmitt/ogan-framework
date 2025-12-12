<?php

namespace Ogan\Controller;

use Ogan\View\View;
use Ogan\Http\Request;
use Ogan\Http\Response;

abstract class AbstractController
{
    protected Request $request;
    protected Response $response;
    protected View $view;
    protected string $layout;

    protected array $config;
    protected ?\Ogan\Form\FormFactory $formFactory = null;
    protected ?\Ogan\Session\SessionInterface $session = null;
    protected ?\Ogan\DependencyInjection\ContainerInterface $container = null;

    /**
     * Appelé automatiquement par le Router lors du dispatch.
     */
    /**
     * Appelé automatiquement par le Router lors du dispatch.
     */
    public function setRequestResponse(Request $request, Response $response, \Ogan\DependencyInjection\ContainerInterface $container): void
    {
        $this->request = $request;
        $this->response = $response;

        // On charge toute la configuration depuis Config (qui gère .env)
        $this->config = \Ogan\Config\Config::all();

        // Initialisation du moteur de vue
        $useCompiler = $this->config['view']['use_compiler'] ?? false;
        $cacheDir = $this->config['view']['cache_dir'] ?? null;
        $this->view = new View($this->config['view']['templates_path'], $useCompiler, $cacheDir);
        $this->layout = $this->config['view']['default_layout'];

        // Injection du CsrfManager dans la vue si disponible
        if ($container->has(\Ogan\Security\CsrfManager::class)) {
            $this->view->setCsrfManager($container->get(\Ogan\Security\CsrfManager::class));
        }

        // Injection du FormFactory si disponible
        if ($container->has(\Ogan\Form\FormFactory::class)) {
            $this->formFactory = $container->get(\Ogan\Form\FormFactory::class);
        } else {
            // Créer un FormFactory avec le Validator du container
            $validator = $container->has(\Ogan\Validation\Validator::class)
                ? $container->get(\Ogan\Validation\Validator::class)
                : null;
            $this->formFactory = new \Ogan\Form\FormFactory($validator);
        }

        // Injection de la session dans le contrôleur et la vue
        if ($request->hasSession()) {
            $this->session = $request->getSession();
            $this->view->setSession($this->session);
        }

        // Injection du Router dans la vue (pour les helpers route() et url())
        if ($container->has(\Ogan\Router\Router::class)) {
            $this->view->setRouter($container->get(\Ogan\Router\Router::class));
        }

        // Stocker le container pour accès aux services
        $this->container = $container;
    }

    /**
     * Réponse JSON simple.
     */
    protected function json($data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Retourne du texte brut (debug ou simple output).
     */
    protected function renderText(string $text): void
    {
        $this->response->send($text);
    }

    /**
     * Redirection HTTP.
     */
    protected function redirect(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }

    /**
     * Rendu d’un partial ou d’un component réutilisable.
     */
    protected function renderPartial(string $template, array $params = []): string
    {
        return $this->view->render($template, $params);
    }

    /**
     * Rendu complet d’une page avec layout + bloc "body".
     */
    protected function render(string $template, array $params = []): void
    {
        // Gestion du titre (celui du contrôleur > celui de config)
        $params['title'] = $params['title']
            ?? $this->config['view']['default_title'];

        // Avec le moteur de template avancé, la vue gère elle-même son layout via extend()
        echo $this->view->render($template, $params);
    }
}
