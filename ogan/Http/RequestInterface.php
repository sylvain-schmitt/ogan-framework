<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🌐 REQUESTINTERFACE - Interface pour les Requêtes HTTP
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE DE CETTE INTERFACE
 * -----------------------
 * Définit le CONTRAT pour toutes les classes qui représentent une requête HTTP.
 * 
 * Une requête HTTP contient :
 * - La méthode (GET, POST, PUT, DELETE...)
 * - L'URI demandée (/blog/article/42)
 * - Les paramètres (query string, POST data...)
 * - Les headers (User-Agent, Accept...)
 * - Les cookies
 * - Les fichiers uploadés
 * 
 * POURQUOI UNE INTERFACE ?
 * ------------------------
 * 
 * 1. TESTABILITÉ :
 *    Dans les tests, tu peux créer une "fake" Request sans avoir besoin
 *    des vraies superglobales PHP ($_GET, $_POST, $_SERVER...)
 *    
 *    ```php
 *    // Production : vraie requête HTTP
 *    $request = new Request($_GET, $_POST, $_SERVER);
 *    
 *    // Tests : requête mockée
 *    $request = new FakeRequest([
 *        'method' => 'GET',
 *        'uri' => '/test'
 *    ]);
 *    
 *    // Les deux implémentent RequestInterface !
 *    $controller->handle($request);  // Fonctionne dans les deux cas
 *    ```
 * 
 * 2. PRINCIPE SOLID "D" (Dependency Inversion) :
 *    Les contrôleurs dépendent de l'interface, pas de la classe concrète
 *    
 *    ```php
 *    class UserController {
 *        public function show(RequestInterface $request) {
 *            // Fonctionne avec N'IMPORTE quelle implémentation
 *        }
 *    }
 *    ```
 * 
 * 3. FLEXIBILITÉ :
 *    On pourrait créer différentes implémentations :
 *    - Request : Requête HTTP classique
 *    - JsonRequest : Requête API JSON
 *    - CliRequest : Requête ligne de commande
 *    - TestRequest : Requête de test
 *    
 *    Toutes respectent le même contrat !
 * 
 * INSPIRATION PSR-7
 * -----------------
 * PSR-7 est le standard officiel pour les messages HTTP en PHP.
 * Notre interface s'en inspire mais en version simplifiée et pédagogique.
 * 
 * Différences avec PSR-7 :
 * - PSR-7 : Objets IMMUTABLES (withMethod() retourne une nouvelle instance)
 * - Nous : Objets MUTABLES (plus simple à comprendre pour débuter)
 * 
 * Tu pourras évoluer vers PSR-7 plus tard !
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Http;

/**
 * Interface pour les requêtes HTTP
 * 
 * Inspirée de PSR-7 mais simplifiée pour être pédagogique
 */
interface RequestInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * MÉTHODE HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Retourne la méthode HTTP de la requête.
     * 
     * VALEURS POSSIBLES :
     * - GET : Récupérer une ressource
     * - POST : Créer une ressource
     * - PUT : Mettre à jour une ressource (remplacement complet)
     * - PATCH : Mettre à jour une ressource (modification partielle)
     * - DELETE : Supprimer une ressource
     * - HEAD : Comme GET mais sans le body
     * - OPTIONS : Liste les méthodes supportées
     * 
     * @return string La méthode HTTP en MAJUSCULES (ex: "GET", "POST")
     */
    public function getMethod(): string;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * URI DEMANDÉE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Retourne l'URI (chemin) demandée, SANS le query string.
     * 
     * EXEMPLES :
     * - Requête : http://example.com/blog/article?id=42
     * - getUri() retourne : "/blog/article"
     * 
     * - Requête : http://example.com/users/123
     * - getUri() retourne : "/users/123"
     * 
     * @return string L'URI demandée (ex: "/blog/article")
     */
    public function getUri(): string;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * PARAMÈTRE QUERY STRING (GET)
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Récupère un paramètre du query string ($_GET).
     * 
     * EXEMPLES :
     * - URL : /search?q=ogan&limit=10
     * - get('q') retourne : "ogan"
     * - get('limit') retourne : "10"
     * - get('page', 1) retourne : 1 (valeur par défaut si absent)
     * 
     * @param string $key Nom du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed La valeur du paramètre ou la valeur par défaut
     */
    public function get(string $key, $default = null);

    /**
     * ───────────────────────────────────────────────────────────────────────
     * PARAMÈTRE POST
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Récupère un paramètre POST ($_POST).
     * 
     * EXEMPLE :
     * Formulaire HTML :
     * <form method="POST">
     *   <input name="email" value="ogan@example.com">
     *   <input name="password" value="secret">
     * </form>
     * 
     * Code PHP :
     * $email = $request->post('email');    // "ogan@example.com"
     * $pass = $request->post('password');  // "secret"
     * 
     * @param string $key Nom du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed La valeur du paramètre ou la valeur par défaut
     */
    public function post(string $key, $default = null);

    /**
     * ───────────────────────────────────────────────────────────────────────
     * DONNÉES JSON
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Parse le corps de la requête comme JSON.
     * 
     * Utile pour les API REST qui envoient du JSON au lieu de form data.
     * 
     * EXEMPLE :
     * Requête AJAX :
     * fetch('/api/users', {
     *     method: 'POST',
     *     headers: {'Content-Type': 'application/json'},
     *     body: JSON.stringify({name: 'Ogan', age: 5})
     * });
     * 
     * Code PHP :
     * $data = $request->json();
     * // ['name' => 'Ogan', 'age' => 5]
     * 
     * @return array Les données JSON parsées en tableau associatif
     */
    public function json(): array;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * VÉRIFIER LA MÉTHODE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Vérifie si la requête utilise la méthode HTTP spécifiée.
     * 
     * EXEMPLES :
     * if ($request->isMethod('POST')) {
     *     // Traiter le formulaire
     * }
     * 
     * if ($request->isMethod('DELETE')) {
     *     // Supprimer la ressource
     * }
     * 
     * @param string $method Méthode à vérifier (case-insensitive)
     * @return bool TRUE si la méthode correspond, FALSE sinon
     */
    public function isMethod(string $method): bool;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * RÉCUPÉRER UN HEADER HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Récupère un header HTTP spécifique.
     * 
     * EXEMPLES :
     * - getHeader('Content-Type') retourne : "application/json"
     * - getHeader('X-Forwarded-For') retourne : "192.168.1.100"
     * - getHeader('User-Agent') retourne : "Mozilla/5.0..."
     * - getHeader('Missing-Header') retourne : null
     * 
     * NOTE : Les noms de headers sont case-insensitive en HTTP.
     * 
     * @param string $name Nom du header (ex: "Content-Type", "X-Forwarded-For")
     * @return string|null La valeur du header ou null si absent
     */
    public function getHeader(string $name): ?string;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * RÉCUPÉRER TOUS LES HEADERS HTTP
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Retourne tous les headers HTTP de la requête.
     * 
     * EXEMPLE :
     * [
     *     'Host' => 'example.com',
     *     'Content-Type' => 'application/json',
     *     'User-Agent' => 'Mozilla/5.0...',
     *     'Accept' => 'text/html',
     * ]
     * 
     * @return array Tableau associatif [nom => valeur]
     */
    public function getHeaders(): array;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * MÉTHODES À AJOUTER PLUS TARD (Phase 4)
 * ---------------------------------------
 * 
 * Pour rendre l'interface encore plus complète, on ajoutera :
 * 
 * - getHeaders(): array          // Tous les headers HTTP
 * - getHeader(string $name)      // Un header spécifique
 * - hasHeader(string $name)      // Vérifier si header existe
 * - getFiles(): array            // Fichiers uploadés ($_FILES)
 * - getCookies(): array          // Tous les cookies
 * - getCookie(string $name)      // Un cookie spécifique
 * - getClientIp(): string        // IP du client
 * - isSecure(): bool             // HTTPS ?
 * - isAjax(): bool               // Requête AJAX ?
 * - isJson(): bool               // Content-Type JSON ?
 * 
 * Mais pour l'instant, on garde simple ! 😊
 * 
 * DIFFÉRENCE INTERFACE vs IMPLÉMENTATION
 * ---------------------------------------
 * 
 * Cette interface définit QUOI faire :
 * ✅ "Il DOIT y avoir une méthode getMethod()"
 * ✅ "Il DOIT y avoir une méthode get()"
 * 
 * La classe Request définira COMMENT le faire :
 * ✅ "getMethod() retourne $this->method"
 * ✅ "get() retourne $this->query[$key] ?? $default"
 * 
 * PROCHAINES ÉTAPES
 * -----------------
 * 1. Modifier Request.php pour implémenter cette interface
 * 2. S'assurer que toutes les méthodes sont présentes
 * 3. Tester que ça fonctionne toujours
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
