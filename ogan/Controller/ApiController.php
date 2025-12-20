<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔌 API CONTROLLER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Classe de base pour les contrôleurs API REST.
 * Fournit des helpers pour les réponses JSON standardisées.
 * 
 * USAGE :
 * -------
 * class UserApiController extends ApiController
 * {
 *     #[Route(path: '/api/users', methods: ['GET'])]
 *     public function index(): Response
 *     {
 *         return $this->json(User::all());
 *     }
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Controller;

use Ogan\Http\Response;

abstract class ApiController extends AbstractController
{
    /**
     * Retourne une réponse JSON
     * 
     * @param mixed $data Données à encoder (modèle, array, etc.)
     * @param int $status Code HTTP
     * @param array $headers Headers additionnels
     * @return Response
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        // Si c'est un modèle, le convertir en array
        if (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }
        
        // Si c'est une collection de modèles
        if (is_array($data) && isset($data[0]) && is_object($data[0]) && method_exists($data[0], 'toArray')) {
            $data = array_map(fn($item) => $item->toArray(), $data);
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $response = new Response($json, $status);
        $response->setHeader('Content-Type', 'application/json');
        
        foreach ($headers as $key => $value) {
            $response->setHeader($key, $value);
        }
        
        return $response;
    }

    /**
     * Réponse de succès standardisée
     * 
     * @param mixed $data Données
     * @param string|null $message Message optionnel
     * @param int $status Code HTTP
     * @return Response
     */
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): Response
    {
        $response = [
            'success' => true,
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            // Convertir les modèles
            if (is_object($data) && method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } elseif (is_array($data) && isset($data[0]) && is_object($data[0]) && method_exists($data[0], 'toArray')) {
                $data = array_map(fn($item) => $item->toArray(), $data);
            }
            $response['data'] = $data;
        }
        
        return $this->json($response, $status);
    }

    /**
     * Réponse d'erreur standardisée
     * 
     * @param string $message Message d'erreur
     * @param int $status Code HTTP
     * @param array|null $errors Détails des erreurs
     * @return Response
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $status);
    }

    /**
     * Réponse 404 Not Found
     * 
     * @param string $message Message d'erreur
     * @return Response
     */
    protected function notFound(string $message = 'Resource not found'): Response
    {
        return $this->error($message, 404);
    }

    /**
     * Réponse 401 Unauthorized
     * 
     * @param string $message Message d'erreur
     * @return Response
     */
    protected function unauthorized(string $message = 'Unauthorized'): Response
    {
        return $this->error($message, 401);
    }

    /**
     * Réponse 403 Forbidden
     * 
     * @param string $message Message d'erreur
     * @return Response
     */
    protected function forbidden(string $message = 'Forbidden'): Response
    {
        return $this->error($message, 403);
    }

    /**
     * Réponse d'erreur de validation (422)
     * 
     * @param array $errors Erreurs de validation par champ
     * @param string $message Message principal
     * @return Response
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): Response
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Réponse 201 Created (pour POST)
     * 
     * @param mixed $data Données créées
     * @param string|null $message Message optionnel
     * @return Response
     */
    protected function created(mixed $data = null, ?string $message = 'Created successfully'): Response
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Réponse 204 No Content (pour DELETE)
     * 
     * @return Response
     */
    protected function noContent(): Response
    {
        return new Response('', 204);
    }

    /**
     * Récupère les données JSON du body de la requête
     * 
     * @return array
     */
    protected function getJsonBody(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        return is_array($data) ? $data : [];
    }

    /**
     * Vérifie si la requête est de type API (Accept: application/json)
     * 
     * @return bool
     */
    protected function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
