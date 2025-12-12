<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📤 RESPONSEINTERFACE - Interface pour les Réponses HTTP
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE DE CETTE INTERFACE
 * -----------------------
 * Définit le CONTRAT pour toutes les classes qui représentent une réponse HTTP
 * envoyée au client (navigateur, API...).
 * 
 * Une réponse HTTP contient :
 * - Un code de statut (200 OK, 404 Not Found, 500 Error...)
 * - Des headers (Content-Type, Set-Cookie, Location...)
 * - Un corps (HTML, JSON, XML, fichier...)
 * 
 * POURQUOI UNE INTERFACE ?
 * ------------------------
 * 
 * 1. TESTABILITÉ :
 *    Dans les tests, tu peux vérifier ce que le contrôleur renvoie
 *    sans avoir besoin d'envoyer réellement des headers HTTP
 *    
 *    ```php
 *    // Test
 *    $response = $controller->handle($request);
 *    $this->assertEquals(200, $response->getStatusCode());
 *    $this->assertContains('Welcome', $response->getContent());
 *    ```
 * 
 * 2. PRINCIPE SOLID "D" (Dependency Inversion) :
 *    Les contrôleurs peuvent travailler avec n'importe quelle implémentation
 *    
 *    ```php
 *    class UserController {
 *        public function show(ResponseInterface $response) {
 *            return $response->json(['user' => 'Ogan']);
 *        }
 *    }
 *    ```
 * 
 * 3. FLEXIBILITÉ :
 *    Différentes implémentations possibles :
 *    - Response : Réponse HTTP standard
 *    - JsonResponse : Réponse JSON automatique
 *    - StreamedResponse : Streaming de gros fichiers
 *    - RedirectResponse : Redirections
 * 
 * INSPIRATION PSR-7
 * -----------------
 * PSR-7 définit aussi ResponseInterface, mais de manière IMMUTABLE.
 * Notre version est MUTABLE (plus simple pour débuter).
 * 
 * PSR-7 : $response = $response->withStatus(404);  // Nouvelle instance
 * Nous :  $response->setStatusCode(404);           // Modifie l'instance
 * 
 * CODES DE STATUT HTTP COURANTS
 * ------------------------------
 * 2xx - Succès :
 *   200 OK : Tout va bien
 *   201 Created : Ressource créée
 *   204 No Content : Succès sans corps de réponse
 * 
 * 3xx - Redirection :
 *   301 Moved Permanently : Redirection permanente
 *   302 Found : Redirection temporaire
 *   304 Not Modified : Cache valide
 * 
 * 4xx - Erreur client :
 *   400 Bad Request : Requête invalide
 *   401 Unauthorized : Authentification requise
 *   403 Forbidden : Accès interdit
 *   404 Not Found : Ressource introuvable
 *   422 Unprocessable Entity : Validation échouée
 * 
 * 5xx - Erreur serveur :
 *   500 Internal Server Error : Erreur interne
 *   503 Service Unavailable : Service indisponible
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Http;

/**
 * Interface pour les réponses HTTP
 * 
 * Inspirée de PSR-7 mais simplifiée
 */
interface ResponseInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * DÉFINIR LE CODE DE STATUT HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Change le code de statut HTTP de la réponse.
     * 
     * EXEMPLES :
     * $response->setStatusCode(200);  // OK
     * $response->setStatusCode(404);  // Not Found
     * $response->setStatusCode(500);  // Internal Server Error
     * 
     * @param int $code Code de statut HTTP (100-599)
     * @return self Pour permettre le chaînage : $response->setStatusCode(200)->send()
     */
    public function setStatusCode(int $code): self;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * RÉCUPÉRER LE CODE DE STATUT HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Retourne le code de statut actuel de la réponse.
     * 
     * Utile pour les tests et le debugging.
     * 
     * @return int Le code de statut HTTP
     */
    public function getStatusCode(): int;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * GÉRER LE CONTENU DE LA RÉPONSE
     * ───────────────────────────────────────────────────────────────────────
     */

    /**
     * Définit le contenu de la réponse.
     * 
     * @param string $content Le corps de la réponse
     * @return self
     */
    public function setContent(string $content): self;

    /**
     * Récupère le contenu de la réponse.
     * 
     * @return string
     */
    public function getContent(): string;

    /**
     * Ajoute un cookie à la réponse
     */
    public function setCookie(string $name, string $value = "", int $expires = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): self;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * ENVOYER LA RÉPONSE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Envoie la réponse au client (navigateur).
     * 
     * Cette méthode :
     * 1. Envoie le code de statut HTTP
     * 2. Envoie les headers
     * 3. Affiche le contenu
     * 
     * EXEMPLE :
     * $response->setStatusCode(200);
     * $response->send('<h1>Hello Ogan!</h1>');
     * 
     * @param string|null $content Le contenu à envoyer (optionnel si setContent utilisé)
     * @return void
     */
    public function send(?string $content = null): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * ENVOYER UNE RÉPONSE JSON
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Convertit un tableau en JSON et l'envoie au client.
     * 
     * Cette méthode :
     * 1. Définit automatiquement Content-Type: application/json
     * 2. Encode le tableau en JSON
     * 3. Envoie la réponse
     * 
     * EXEMPLE :
     * $response->json([
     *     'status' => 'success',
     *     'data' => ['name' => 'Ogan', 'age' => 5]
     * ]);
     * 
     * Résultat envoyé au client :
     * {"status":"success","data":{"name":"Ogan","age":5}}
     * 
     * @param array $data Les données à encoder en JSON
     * @param int $status Code de statut HTTP (par défaut 200)
     * @return self
     */
    public function json(array $data, int $status = 200): self;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * REDIRIGER VERS UNE AUTRE URL
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Redirige le navigateur vers une autre URL.
     * 
     * Cette méthode :
     * 1. Définit le code de statut 302 (Found) ou 301 (Moved Permanently)
     * 2. Ajoute le header Location: <url>
     * 3. Arrête l'exécution
     * 
     * EXEMPLES :
     * // Redirection temporaire (302)
     * $response->redirect('/login');
     * 
     * // Redirection permanente (301)
     * $response->redirect('/new-url', 301);
     * 
     * APRÈS LA REDIRECTION :
     * Le navigateur va faire une nouvelle requête vers l'URL indiquée.
     * 
     * @param string $url URL de destination
     * @param int $status Code de statut (302 par défaut, ou 301)
     * @return self
     */
    public function redirect(string $url, int $status = 302): self;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * DÉFINIR UN HEADER HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Ajoute ou modifie un header HTTP dans la réponse.
     * 
     * Les headers HTTP permettent de transmettre des métadonnées :
     * - Content-Type : Type de contenu (HTML, JSON, PDF...)
     * - Cache-Control : Gestion du cache
     * - Set-Cookie : Définir un cookie
     * - Access-Control-Allow-Origin : CORS
     * 
     * EXEMPLES :
     * $response->setHeader('Content-Type', 'application/json');
     * $response->setHeader('X-Custom-Header', 'valeur');
     * $response->setHeader('Access-Control-Allow-Origin', '*');
     * 
     * @param string $name Nom du header
     * @param string $value Valeur du header
     * @return self Pour permettre le chaînage
     */
    public function setHeader(string $name, string $value): self;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * RÉCUPÉRER UN HEADER HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Récupère la valeur d'un header précédemment défini.
     * 
     * Utile pour :
     * - Les tests unitaires
     * - Le debugging
     * - Vérifier qu'un header a bien été défini
     * 
     * EXEMPLE :
     * $response->setHeader('Content-Type', 'application/json');
     * echo $response->getHeader('Content-Type'); // "application/json"
     * 
     * @param string $name Nom du header
     * @return string|null Valeur du header ou null s'il n'existe pas
     */
    public function getHeader(string $name): ?string;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * MÉTHODES À AJOUTER PLUS TARD (Phase 4)
 * ---------------------------------------
 * 
 * Pour enrichir l'interface, on ajoutera :
 * 
 * - setHeader(string $name, string $value)  // Définir un header
 * - getHeader(string $name)                 // Lire un header
 * - setCookie(...)                          // Définir un cookie
 * - download(string $file, string $name)    // Télécharger un fichier
 * - stream(callable $callback)              // Streaming de contenu
 * 
 * CHAÎNAGE DE MÉTHODES (Fluent Interface)
 * ----------------------------------------
 * 
 * En retournant `self` dans setStatusCode(), on permet le chaînage :
 * 
 * $response
 *     ->setStatusCode(404)
 *     ->setHeader('X-Custom', 'value')
 *     ->send('Not Found');
 * 
 * C'est ce qu'on appelle une "Fluent Interface" ou "API Fluide".
 * Ça rend le code plus lisible et élégant !
 * 
 * POURQUOI send() AFFICHE ET NE RETOURNE PAS ?
 * ---------------------------------------------
 * 
 * send() affiche directement le contenu (echo) au lieu de le retourner car :
 * 
 * 1. C'est le point final : après send(), il n'y a plus rien à faire
 * 2. Ça envoie les headers HTTP (impossible à faire si on retourne une string)
 * 3. Ça permet de streamer du contenu (echo fait moins de mémoire que return)
 * 
 * DIFFÉRENCE ENTRE send() ET json()
 * ----------------------------------
 * 
 * send(string $content) :
 * - Envoie du contenu brut
 * - Tu dois encoder toi-même si c'est du JSON
 * - Utilise le Content-Type actuel
 * 
 * json(array $data) :
 * - Encode automatiquement en JSON
 * - Définit Content-Type: application/json
 * - Plus simple pour les API
 * 
 * PROCHAINES ÉTAPES
 * -----------------
 * 1. Modifier Response.php pour implémenter cette interface
 * 2. Ajouter les méthodes manquantes
 * 3. Tester les redirections et le JSON
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
