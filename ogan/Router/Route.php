<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🛣️ ROUTE CLASS (Enhanced with Constraints & Middlewares)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Représente une route HTTP avec :
 * - Pattern d'URL avec paramètres (dynamiques, contraints, optionnels)
 * - Méthodes HTTP acceptées (GET, POST, etc.)
 * - Contrôleur et méthode à appeler
 * - Middlewares spécifiques
 * - Sous-domaine (optionnel)
 * 
 * NOUVEAUTÉS PHASE 3 :
 * --------------------
 * ✨ Contraintes de paramètres : {id:\d+}, {slug:[a-z-]+}
 * ✨ Paramètres optionnels : {category?}
 * ✨ Middlewares par route
 * ✨ Support des sous-domaines : admin.example.com
 * 
 * EXEMPLES :
 * ----------
 * // Route simple
 * new Route('/users', ['GET'], UserController::class, 'index');
 * 
 * // Avec contrainte numérique
 * new Route('/users/{id:\d+}', ['GET'], UserController::class, 'show');
 * 
 * // Avec paramètre optionnel
 * new Route('/search/{query?}', ['GET'], SearchController::class, 'index');
 * 
 * // Avec middleware
 * $route->middleware(new AuthMiddleware());
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Router;

use Ogan\Middleware\MiddlewareInterface;
use Ogan\Router\Constraint\ConstraintInterface;
use Ogan\Router\Constraint\RegexConstraint;

class Route
{
    /** @var string Pattern original de la route (ex: /users/{id:\d+}) */
    public string $path;
    
    /** @var string Expression régulière compilée pour le matching */
    public string $regex;
    
    /** @var array<string> Noms des paramètres extraits ({id} → 'id') */
    public array $params = [];
    
    /** @var array<string, bool> Paramètres optionnels ({query?} → ['query' => true]) */
    public array $optionalParams = [];
    
    /** @var array<string> Méthodes HTTP acceptées (GET, POST, ...) */
    public array $httpMethods;
    
    /** @var string Nom complet de la classe du contrôleur */
    public string $controllerClass;
    
    /** @var string Nom de la méthode du contrôleur à appeler */
    public string $controllerMethod;
    
    /** @var string|null Nom de la route (pour generateUrl) */
    public ?string $name;
    
    /** @var array<string|MiddlewareInterface> Middlewares attachés à cette route */
    private array $middlewares = [];
    
    /** @var array<string, ConstraintInterface> Contraintes par paramètre */
    private array $constraints = [];
    
    /** @var string|null Pattern du sous-domaine (ex: 'admin', '{tenant}') */
    private ?string $domain = null;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(
        string $path,
        array $httpMethods,
        string $controllerClass,
        string $controllerMethod,
        ?string $name = null
    ) {
        $this->path = $path;
        $this->httpMethods = array_map('strtoupper', $httpMethods);
        $this->controllerClass = $controllerClass;
        $this->controllerMethod = $controllerMethod;
        $this->name = $name;

        $this->compilePath();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COMPILER LE CHEMIN EN REGEX (Parsing avancé)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Analyse le pattern et extrait :
     * 1. Paramètres simples : {id}
     * 2. Paramètres avec contrainte : {id:\d+}
     * 3. Contraintes prédéfinies : {id:}, {slug:}, {uuid:}
     * 4. Paramètres optionnels : {category?}
     * 5. Paramètres optionnels avec contrainte : {page:\d+?}
     * 
     * DÉTECTION AUTOMATIQUE :
     * -----------------------
     * - {id} → applique automatiquement \d+ (numérique)
     * - {slug} → applique automatiquement [a-z0-9-]+ (slug)
     * - {query} → paramètre optionnel automatique
     * 
     * TRANSFORMATION :
     * ----------------
     * /users/{id}                → /users/(?P<id>\d+)       (auto-numérique)
     * /posts/{slug}              → /posts/(?P<slug>[a-z0-9-]+) (auto-slug)
     * /search/{query}            → /search(?:/(?P<query>[^/]+))? (auto-optionnel)
     * /users/{id:\d+}            → /users/(?P<id>\d+)
     * /search/{query?}           → /search(?:/(?P<query>[^/]+))?
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function compilePath(): void
    {
        // ─────────────────────────────────────────────────────────────
        // CONTRAINTES PRÉDÉFINIES (appliquées automatiquement)
        // ─────────────────────────────────────────────────────────────
        $autoConstraints = [
            'id' => '\d+',                                      // {id} → nombres
            'slug' => '[a-z0-9-]+',                            // {slug} → slug URL-friendly
            'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}', // {uuid}
            'num' => '\d+',                                    // {num} → nombres
            'page' => '\d+',                                   // {page} → nombres
        ];
        
        // ─────────────────────────────────────────────────────────────
        // PARAMÈTRES OPTIONNELS AUTOMATIQUES
        // ─────────────────────────────────────────────────────────────
        $autoOptional = ['query', 'search', 'filter'];
        
        // ─────────────────────────────────────────────────────────────
        // Pattern regex pour détecter tous les types de paramètres
        // ─────────────────────────────────────────────────────────────
        // Format : /{nom:contrainte?} ou {nom:contrainte} 
        $pattern = '/(\\/)?\\{(\\w+)(?::([^}?]*))?(\\?)?\\}/';
        
        $regex = preg_replace_callback($pattern, function ($matches) use ($autoConstraints, $autoOptional) {
            $leadingSlash = $matches[1] ?? '';               // / avant le {param}
            $paramName = $matches[2];                        // Nom du paramètre
            $constraint = $matches[3] ?? null;               // Contrainte (regex) ou vide
            $hasQuestionMark = isset($matches[4]);           // ? présent ?
            
            // ─────────────────────────────────────────────────────────
            // Déterminer si le paramètre est optionnel
            // ─────────────────────────────────────────────────────────
            $optional = $hasQuestionMark || in_array($paramName, $autoOptional);
            
            // Enregistrer le paramètre
            $this->params[] = $paramName;
            
            // Enregistrer si optionnel
            if ($optional) {
                $this->optionalParams[$paramName] = true;
            }
            
            // ─────────────────────────────────────────────────────────
            // Gestion des contraintes
            // ─────────────────────────────────────────────────────────
            if ($constraint !== null && $constraint !== '') {
                // Contrainte explicite : {id:\d+}
                $regexPart = $constraint;
                $this->constraints[$paramName] = new RegexConstraint($regexPart);
            } elseif (isset($autoConstraints[$paramName])) {
                // Contrainte automatique : {id} ou {slug} (sans les :)
                $regexPart = $autoConstraints[$paramName];
                $this->constraints[$paramName] = new RegexConstraint($regexPart);
            } else {
                // Pas de contrainte : [^/]+ (tout sauf /)
                $regexPart = '[^/]+';
            }
            
            // Création du groupe de capture nommé
            $namedGroup = '(?P<' . $paramName . '>' . $regexPart . ')';
            
            // Si optionnel, on rend le slash ET le paramètre optionnels
            if ($optional) {
                // Groupe optionnel non-capturant incluant le slash
                return '(?:/' . $namedGroup . ')?';
            }
            
            // Si obligatoire, on garde le slash
            return $leadingSlash . $namedGroup;
        }, $this->path);

        // Compile en regex finale
        $this->regex = '#^' . $regex . '$#';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LA ROUTE CORRESPOND À LA REQUÊTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Vérifie :
     * 1. Méthode HTTP
     * 2. Sous-domaine (si défini)
     * 3. Pattern de l'URI
     * 4. Contraintes de paramètres
     * 
     * @param string $uri URI demandée (ex: /users/123)
     * @param string $method Méthode HTTP (GET, POST, etc.)
     * @param string|null $host Host de la requête (ex: admin.example.com)
     * @return array|false Paramètres extraits si match, false sinon
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function match(string $uri, string $method, ?string $host = null)
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Vérifier la méthode HTTP
        // ─────────────────────────────────────────────────────────────
        if (!in_array(strtoupper($method), $this->httpMethods)) {
            return false;
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Vérifier le sous-domaine (si défini)
        // ─────────────────────────────────────────────────────────────
        if ($this->domain && $host) {
            // Vérification du sous-domaine
            // Si le domaine contient des paramètres {param}, on utilise une regex
            if (str_contains($this->domain, '{')) {
                // On transforme le pattern de domaine en regex (similaire à compilePath)
                $domainPattern = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^.]+)', $this->domain) . '$#';
                
                if (preg_match($domainPattern, $host, $domainMatches)) {
                    // On fusionne les paramètres du domaine avec ceux de la route
                    $matches = array_merge($matches ?? [], $domainMatches);
                } else {
                    return false;
                }
            } else {
                // Comparaison simple (ex: admin.example.com)
                // On vérifie si le host commence par le domaine (ou est égal)
                // Pour simplifier, on compare strictement
                if ($host !== $this->domain) {
                    return false;
                }
            }
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Vérifier le pattern de l'URI
        // ─────────────────────────────────────────────────────────────
        if (!preg_match($this->regex, $uri, $matches)) {           return false;
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 4 : Extraire les paramètres et valider les contraintes
        // ─────────────────────────────────────────────────────────────
        $params = [];
        foreach ($this->params as $name) {
            // Récupère la valeur du paramètre (si présent)
            $value = $matches[$name] ?? null;
            
            // Si le paramètre est optionnel et absent, on continue
            if ($value === null || $value === '') {
                if (isset($this->optionalParams[$name])) {
                    continue; // Paramètre optionnel absent, c'est OK
                }
                // Paramètre obligatoire absent, échec
                return false;
            }
            
            // Si une contrainte existe pour ce paramètre, la vérifier
            if (isset($this->constraints[$name])) {
                if (!$this->constraints[$name]->matches($value)) {
                    return false; // Contrainte non respectée
                }
            }
            
            $params[$name] = $value;
        }

        return $params;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UN MIDDLEWARE À LA ROUTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * UTILISATION :
     * -------------
     * $route->middleware(new AuthMiddleware());
     * $route->middleware(CsrfMiddleware::class); // Supporte DI via string
     * 
     * @param string|MiddlewareInterface $middleware
     * @return self Pour chaînage (fluent interface)
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function middleware(string|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES MIDDLEWARES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array<MiddlewareInterface>
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }



    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR UNE CONTRAINTE POUR UN PARAMÈTRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Alternative à la syntaxe inline {id:\d+}.
     * 
     * UTILISATION :
     * -------------
     * $route = new Route('/users/{id}', ['GET'], ...);
     * $route->constraint('id', new RegexConstraint('\d+'));
     * 
     * @param string $param Nom du paramètre
     * @param ConstraintInterface $constraint Contrainte à appliquer
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function constraint(string $param, ConstraintInterface $constraint): self
    {
        $this->constraints[$param] = $constraint;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR LE SOUS-DOMAINE DE LA ROUTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * UTILISATION :
     * -------------
     * $route->setDomain('admin'); 
     * // Matche admin.example.com
     * 
     * $route->setDomain('{tenant}'); 
     * // Matche {tenant}.example.com
     * 
     * @param string|null $domain Pattern du sous-domaine
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR LE PATTERN DE LA ROUTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utilisé par RouteGroup pour ajouter des préfixes.
     * 
     * @param string $pattern Nouveau pattern
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function setPattern(string $pattern): self
    {
        $this->path = $pattern;
        $this->compilePath(); // Recompiler
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OBTENIR LE PATTERN DE LA ROUTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return string
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getPattern(): string
    {
        return $this->path;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * REGEX NOMMÉES (Named Capture Groups) :
 * ---------------------------------------
 * (?P<name>pattern) crée un groupe de capture nommé.
 * 
 * Exemple :
 * Pattern : /users/(?P<id>\d+)
 * URI : /users/123
 * Résultat : $matches['id'] = '123'
 * 
 * PARAMÈTRES OPTIONNELS :
 * -----------------------
 * On enveloppe le paramètre dans un groupe optionnel avec (...)?
 * 
 * Exemple :
 * Pattern original : /search/{query?}
 * Pattern compilé : /search(/(?P<query>[^/]+))?
 * 
 * Matche :
 * - /search → query absent
 * - /search/test → query = 'test'
 * 
 * CONTRAINTES INLINE vs API :
 * ---------------------------
 * 1. INLINE (recommandé pour les patterns simples) :
 *    /users/{id:\d+}
 * 
 * 2. API (pour les contraintes complexes) :
 *    $route->constraint('id', new RegexConstraint('\d+'));
 *    $route->constraint('lang', new EnumConstraint(['fr', 'en']));
 * 
 * FLUENT INTERFACE :
 * ------------------
 * Les méthodes retournent $this pour permettre le chaînage :
 * 
 * $route->middleware(new AuthMiddleware())
 *       ->middleware(new LoggerMiddleware())
 *       ->constraint('id', new RegexConstraint('\d+'));
 * 
 * ORDRE D'EXÉCUTION DES MIDDLEWARES :
 * ------------------------------------
 * Les middlewares sont exécutés dans l'ordre d'ajout :
 * 
 * $route->middleware($mw1)->middleware($mw2);
 * 
 * Exécution : Request → $mw1 → $mw2 → Controller → $mw2 → $mw1 → Response
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

