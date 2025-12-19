<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎯 HTMX RESPONSE TRAIT - Helpers de réponse HTMX pour les contrôleurs
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce trait fournit des méthodes pratiques pour gérer les réponses HTMX
 * dans les contrôleurs.
 * 
 * UTILISATION :
 * -------------
 * class UserController extends AbstractController {
 *     use HtmxResponseTrait;
 *     
 *     public function delete(int $id) {
 *         User::find($id)->delete();
 *         
 *         if ($this->isHtmxRequest()) {
 *             return $this->htmxEmpty(); // Supprime l'élément côté client
 *         }
 *         return $this->redirect('/users');
 *     }
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Controller;

use Ogan\Http\Response;
use Ogan\Middleware\HtmxContext;

trait HtmxResponseTrait
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI C'EST UNE REQUÊTE HTMX
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function isHtmxRequest(): bool
    {
        return HtmxContext::isHtmxRequest();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LA CIBLE HTMX
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function getHtmxTarget(): ?string
    {
        return HtmxContext::getTarget();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * REDIRECTION HTMX
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Force une redirection côté client (page complète)
     */
    protected function htmxRedirect(string $url): Response
    {
        return new Response('', 200, ['HX-Redirect' => $url]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RAFRAÎCHIR LA PAGE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Force un rechargement complet de la page
     */
    protected function htmxRefresh(): Response
    {
        return new Response('', 200, ['HX-Refresh' => 'true']);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉCLENCHER UN ÉVÉNEMENT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Déclenche un événement JavaScript côté client
     */
    protected function htmxTrigger(string $event, array $data = []): Response
    {
        $header = empty($data) ? $event : json_encode([$event => $data]);
        return new Response('', 200, ['HX-Trigger' => $header]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉPONSE VIDE (SUPPRESSION)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne une réponse vide (l'élément cible sera supprimé)
     */
    protected function htmxEmpty(): Response
    {
        return new Response('', 200);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * METTRE À JOUR L'URL DU NAVIGATEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Met à jour l'URL dans la barre d'adresse sans recharger
     */
    protected function htmxPushUrl(string $url, string $content = ''): Response
    {
        return new Response($content, 200, ['HX-Push-Url' => $url]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * REMPLACER L'URL DU NAVIGATEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Remplace l'URL dans l'historique (sans ajouter une nouvelle entrée)
     */
    protected function htmxReplaceUrl(string $url, string $content = ''): Response
    {
        return new Response($content, 200, ['HX-Replace-Url' => $url]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RENDRE UN TEMPLATE PARTIEL OU COMPLET
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Si c'est une requête HTMX, rend seulement le fragment.
     * Sinon, rend la page complète.
     */
    protected function renderSmart(string $template, string $partial, array $data = []): Response
    {
        if ($this->isHtmxRequest()) {
            return $this->render($partial, $data);
        }
        return $this->render($template, $data);
    }
}
