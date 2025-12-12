<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 CONTAINER D'INJECTION DE DÉPENDANCES (Dependency Injection Container)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Le Container est le CŒUR du framework. Il gère la création et l'injection
 * automatique des dépendances de toutes les classes de l'application.
 * 
 * PROBLÈME RÉSOLU :
 * -----------------
 * Sans Container :
 *   class UserController {
 *       public function __construct() {
 *           $this->db = new Database();  // ❌ Couplage fort, difficile à tester
 *       }
 *   }
 * 
 * Avec Container :
 *   class UserController {
 *       public function __construct(Database $db) {
 *           $this->db = $db;  // ✅ Injection, facile à tester (mock)
 *       }
 *   }
 * 
 * CONCEPTS CLÉS :
 * ---------------
 * 1. AUTOWIRING : Détecte automatiquement les dépendances via Reflection
 * 2. SINGLETON : Une seule instance par classe (économie mémoire)
 * 3. FACTORY : Permet de définir manuellement la création d'objets complexes
 * 
 * EXEMPLE D'UTILISATION :
 * -----------------------
 * $container = new Container();
 * 
 * // Enregistrer une factory pour un service complexe
 * $container->set(Database::class, function() {
 *     return new Database('localhost', 'user', 'pass');
 * });
 * 
 * // Récupérer automatiquement avec autowiring
 * $controller = $container->get(UserController::class);
 * // Le Container va :
 * // 1. Voir que UserController a besoin de Database
 * // 2. Créer/récupérer Database
 * // 3. Injecter dans UserController
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\DependencyInjection;

use ReflectionClass;
use ReflectionParameter;
use Ogan\Exception\ContainerException;
use Ogan\Exception\NotFoundException;

class Container implements ContainerInterface
{
    /**
     * @var array<string, callable> 
     * Stocke les factories : des fonctions qui savent créer un service
     * 
     * Exemple : ['Database' => fn() => new Database(...)]
     */
    private array $services = [];

    /**
     * @var array<string, object>
     * Stocke les instances déjà créées (pattern Singleton)
     * 
     * Une fois créé, on le réutilise au lieu de le recréer
     * Économise mémoire et assure qu'on a toujours la même instance
     */
    private array $instances = [];

    /**
     * @var array<string, string>
     * Stocke les aliases : plusieurs noms pour le même service
     * 
     * Exemple : ['db' => Database::class, DatabaseInterface::class => Database::class]
     * Permet d'utiliser $container->get('db') ou $container->get(DatabaseInterface::class)
     * et obtenir la même instance de Database
     */
    private array $aliases = [];

    /**
     * @var array<string, array<string>>
     * Stocke les tags : groupes de services
     * 
     * Exemple : ['logger' => [FileLogger::class, DatabaseLogger::class]]
     * Permet de récupérer tous les services taguées 'logger' d'un coup
     */
    private array $tags = [];

    /**
     * @var array<string, mixed>
     * Stocke les bindings de paramètres scalaires
     * 
     * Permet d'injecter des valeurs string, int, array...
     * Exemple : ['app.env' => 'dev', 'app.debug' => true]
     */
    private array $bindings = [];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ENREGISTRER UN SERVICE (Factory Pattern)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de définir COMMENT créer un service complexe.
     * 
     * @param string $id Identifiant unique (généralement le nom de classe)
     * @param callable $factory Fonction qui retourne l'instance
     * 
     * EXEMPLE :
     * ---------
     * $container->set(Request::class, function(Container $c) {
     *     return new Request($_GET, $_POST, $_SERVER);
     * });
     */
    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UN SERVICE EXISTE (PSR-11)
     * ═══════════════════════════════════════════════════════════════════════
     * 
     * Vérifie si le container peut fournir le service demandé.
     * 
     * LOGIQUE :
     * ---------
     * Un service existe si :
     * 1. Il est déjà instancié (dans $instances)
     * 2. Une factory est définie (dans $services)
     * 3. C'est une classe qui existe (class_exists)
     * 
     * IMPORTANT :
     * -----------
     * Cette méthode ne lance JAMAIS d'exception (requis par PSR-11).
     * Elle retourne simplement true ou false.
     * 
     * EXEMPLE D'UTILISATION :
     * -----------------------
     * if ($container->has('mailer')) {
     *     $mailer = $container->get('mailer');
     *     $mailer->send(...);
     * } else {
     *     // Utiliser un mailer par défaut
     *     $mailer = new NullMailer();
     * }
     * 
     * @param string $id Identifiant du service
     * @return bool TRUE si le service peut être fourni, FALSE sinon
     */
    public function has(string $id): bool
    {
        // Cas 1 : Déjà instancié
        if (isset($this->instances[$id])) {
            return true;
        }

        // Cas 2 : Factory définie
        if (isset($this->services[$id])) {
            return true;
        }

        // Cas 3 : Classe existante (autowiring possible)
        if (class_exists($id)) {
            return true;
        }

        // Cas 4 : Alias existant
        if (isset($this->aliases[$id])) {
            return $this->has($this->aliases[$id]); // Récursif
        }

        return false;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ALIAS - Plusieurs noms pour le même service
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de créer un alias (nom alternatif) pour un service.
     * 
     * UTILITÉ :
     * ---------
     * 1. Utiliser un nom court : 'db' au lieu de Database::class
     * 2. Mapper une interface vers son implémentation
     * 3. Compatibilité avec ancien code (renommage de classes)
     * 
     * EXEMPLES :
     * ----------
     * // Nom court
     * $container->alias('db', Database::class);
     * $db = $container->get('db'); // Récupère Database
     * 
     * // Interface → Implémentation
     * $container->alias(LoggerInterface::class, FileLogger::class);
     * $logger = $container->get(LoggerInterface::class); // Récupère FileLogger
     * 
     * // Les deux retournent LA MÊME instance (singleton)
     * $db1 = $container->get('db');
     * $db2 = $container->get(Database::class);
     * // $db1 === $db2 → true
     * 
     * @param string $alias Nom de l'alias
     * @param string $service Nom du service réel
     * @return void
     */
    public function alias(string $alias, string $service): void
    {
        $this->aliases[$alias] = $service;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TAG - Grouper des services par catégorie
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de taguer (étiqueter) des services pour les regrouper.
     * 
     * UTILITÉ :
     * ---------
     * Récupérer tous les services d'un même type d'un coup.
     * 
     * EXEMPLES :
     * ----------
     * // Taguer plusieurs loggers
     * $container->tag([
     *     FileLogger::class,
     *     DatabaseLogger::class,
     *     SyslogLogger::class
     * ], 'logger');
     * 
     * // Récupérer tous les loggers
     * $loggers = $container->tagged('logger');
     * foreach ($loggers as $logger) {
     *     $logger->log('Message');
     * }
     * 
     * CAS D'USAGE RÉELS :
     * -------------------
     * - Tous les middlewares HTTP
     * - Tous les event listeners
     * - Tous les providers de cache
     * - Tous les drivers de base de données
     * 
     * @param array $services Liste des IDs de services
     * @param string $tag Nom du tag
     * @return void
     */
    public function tag(array $services, string $tag): void
    {
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = [];
        }

        foreach ($services as $service) {
            if (!in_array($service, $this->tags[$tag], true)) {
                $this->tags[$tag][] = $service;
            }
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TAGGED - Récupérer tous les services d'un tag
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne toutes les instances des services tagués.
     * 
     * @param string $tag Nom du tag
     * @return array Tableau d'instances
     */
    public function tagged(string $tag): array
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        $instances = [];
        foreach ($this->tags[$tag] as $serviceId) {
            $instances[] = $this->get($serviceId);
        }

        return $instances;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * BIND - Lier une valeur scalaire
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet d'enregistrer des valeurs simples (string, int, array...)
     * pour l'injection de dépendances.
     * 
     * UTILITÉ :
     * ---------
     * Injecter des paramètres de configuration dans les constructeurs.
     * 
     * EXEMPLES :
     * ----------
     * // Enregistrer des paramètres
     * $container->bind('app.env', 'dev');
     * $container->bind('app.debug', true);
     * $container->bind('db.host', 'localhost');
     * 
     * // Classe qui utilise ces paramètres
     * class DatabaseConnection {
     *     public function __construct(string $host, bool $debug) {
     *         // $host = 'localhost', $debug = true
     *     }
     * }
     * 
     * // Le container injectera automatiquement les valeurs
     * $db = $container->get(DatabaseConnection::class);
     * 
     * @param string $name Nom du paramètre
     * @param mixed $value Valeur du paramètre
     * @return void
     */
    public function bind(string $name, $value): void
    {
        $this->bindings[$name] = $value;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER UN SERVICE (Service Locator Pattern)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Récupère ou crée une instance de service.
     * 
     * ALGORITHME :
     * ------------
     * 1. Si déjà instancié → retourne l'instance (Singleton)
     * 2. Sinon, si une factory est définie → l'exécute
     * 3. Sinon, si c'est une classe → autowiring automatique
     * 4. Sinon → erreur
     * 
     * @param string $id Identifiant du service (nom de classe généralement)
     * @return mixed L'instance du service
     * @throws \Exception Si le service n'existe pas
     */
    public function get(string $id)
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 0 : Résolution des alias
        // ─────────────────────────────────────────────────────────────
        // Si l'ID est un alias, on résout vers le service réel
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Déjà instancié ? (Singleton)
        // ─────────────────────────────────────────────────────────────
        if (isset($this->instances[$id])) {
            return $this->instances[$id];  // Retourne l'existante
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Factory - Une fonction de création est définie ?
        // ─────────────────────────────────────────────────────────────
        if (isset($this->services[$id])) {
            // Exécute la factory en lui passant le container
            // (permet à la factory de récupérer d'autres services)
            $this->instances[$id] = ($this->services[$id])($this);
            return $this->instances[$id];
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Autowiring - Construction automatique
        // ─────────────────────────────────────────────────────────────
        if (class_exists($id)) {
            $instance = $this->build($id);  // Magic happens here!
            $this->instances[$id] = $instance;  // Stocke pour réutilisation
            return $instance;
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 4 : Service introuvable (PSR-11)
        // ─────────────────────────────────────────────────────────────
        throw new NotFoundException("Service '{$id}' not found in container");
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTION AUTOMATIQUE (Autowiring)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * C'est ici que la MAGIE opère !
     * 
     * Grâce à la Reflection API de PHP, on peut :
     * 1. Inspecter le constructeur d'une classe
     * 2. Voir quels paramètres il attend
     * 3. Les créer automatiquement
     * 4. Instancier la classe avec toutes ses dépendances
     * 
     * EXEMPLE :
     * ---------
     * class UserController {
     *     public function __construct(Database $db, Logger $logger) {
     *         // ...
     *     }
     * }
     * 
     * $controller = $container->build(UserController::class);
     * // Le Container va automatiquement :
     * // 1. Créer Database
     * // 2. Créer Logger
     * // 3. Les injecter dans UserController
     * 
     * @param string $class Nom complet de la classe (FQCN)
     * @return object Instance de la classe
     */
    private function build(string $class)
    {
        // ─────────────────────────────────────────────────────────────
        // Utilise la Reflection pour inspecter la classe
        // ─────────────────────────────────────────────────────────────
        $ref = new ReflectionClass($class);

        // ─────────────────────────────────────────────────────────────
        // Récupère le constructeur (peut être null si pas de __construct)
        // ─────────────────────────────────────────────────────────────
        $constructor = $ref->getConstructor();
        if (!$constructor) {
            // Pas de constructeur = pas de dépendances = instanciation simple
            return new $class();
        }

        // ─────────────────────────────────────────────────────────────
        // Liste tous les paramètres du constructeur
        // ─────────────────────────────────────────────────────────────
        $params = $constructor->getParameters();
        $dependencies = [];

        // ─────────────────────────────────────────────────────────────
        // Résout chaque paramètre (= dépendance)
        // ─────────────────────────────────────────────────────────────
        foreach ($params as $param) {
            $dependencies[] = $this->resolveParameter($param, $class);
        }

        // ─────────────────────────────────────────────────────────────
        // Instancie la classe avec toutes ses dépendances résolues
        // ─────────────────────────────────────────────────────────────
        return $ref->newInstanceArgs($dependencies);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉSOLUTION D'UN PARAMÈTRE (Dependency Resolution)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Détermine comment créer/récupérer une dépendance.
     * 
     * LOGIQUE :
     * ---------
     * 1. Si c'est un type classe (ex: Database) → récursion via get()
     * 2. Si c'est un type builtin (string, int...) et a une valeur par défaut → utilise la défaut
     * 3. Sinon → erreur (impossible de deviner la valeur)
     * 
     * @param ReflectionParameter $param Information sur le paramètre (via Reflection)
     * @return mixed La valeur à injecter
     * @throws \Exception Si impossible de résoudre
     */
    private function resolveParameter(ReflectionParameter $param, string $class)
    {
        // ─────────────────────────────────────────────────────────────
        // Récupère le type du paramètre (PHP 7.4+)
        // ─────────────────────────────────────────────────────────────
        $type = $param->getType();

        // ─────────────────────────────────────────────────────────────
        // CAS 1 : Pas de type défini
        // ─────────────────────────────────────────────────────────────
        if (!$type) {
            // Si le paramètre a une valeur par défaut, on l'utilise
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            // Sinon, on essaie de trouver par nom dans les bindings
            if (isset($this->bindings[$param->getName()])) {
                return $this->bindings[$param->getName()];
            }

            throw new ContainerException(
                "Cannot resolve parameter '\${$param->getName()}' in class {$class}: no type hint and no binding found"
            );
        }

        // ─────────────────────────────────────────────────────────────
        // CAS 2 : Type scalaire (string, int, bool, float, array)
        // ─────────────────────────────────────────────────────────────
        // Vérification importante : isBuiltin() existe uniquement sur ReflectionNamedType
        if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
            // Récupère le nom du type de manière compatible
            $typeName = $type->getName();
            $paramName = $param->getName();

            // Cherche dans les bindings
            if (isset($this->bindings[$paramName])) {
                return $this->bindings[$paramName];
            }

            // Si paramètre optionnel, utilise la valeur par défaut
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new ContainerException(
                "Cannot resolve scalar parameter '\${$paramName}' ({$typeName}) in class {$class}: no binding found"
            );
        }

        // ─────────────────────────────────────────────────────────────
        // CAS 3 : Type classe/interface → Autowiring
        // ─────────────────────────────────────────────────────────────
        // Vérification : seul ReflectionNamedType possède getName()
        if (!$type instanceof \ReflectionNamedType) {
            // Gestion des union/intersection types (PHP 8.0+)
            throw new ContainerException(
                "Cannot resolve parameter '\${$param->getName()}' in class {$class}: union/intersection types are not supported yet"
            );
        }
        
        $className = $type->getName();

        // Essaie de résoudre via le container
        try {
            return $this->get($className);
        } catch (NotFoundException $e) {
            // Si le service n'existe pas et que le paramètre est optionnel (nullable ou valeur par défaut)
            if ($param->allowsNull()) {
                return null;
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            // Sinon, on propage l'exception
            throw new ContainerException(
                "Cannot resolve dependency '\${$param->getName()}' ({$className}) in class {$class}: service not found",
                0,
                $e
            );
        }
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI UTILISER UN CONTAINER ?
 * ---------------------------------
 * 1. TESTABILITÉ : On peut injecter des mocks pour les tests
 * 2. FLEXIBILITÉ : On peut changer l'implémentation sans toucher le code
 * 3. COUPLAGE FAIBLE : Les classes ne créent pas leurs dépendances
 * 4. CONFIGURATION CENTRALISÉE : Toute la config au même endroit
 * 
 * PRINCIPE SOLID : DEPENDENCY INVERSION (le "D")
 * -----------------------------------------------
 * "Les modules de haut niveau ne doivent pas dépendre des modules de
 *  bas niveau. Les deux doivent dépendre d'abstractions."
 * 
 * Le Container permet d'injecter des interfaces plutôt que des classes
 * concrètes, respectant ainsi ce principe.
 * 
 * ALTERNATIVES :
 * --------------
 * - Symfony DependencyInjection Component (plus complexe, plus complet)
 * - PHP-DI (populaire, facile à utiliser)
 * - Pimple (très léger, de Symfony)
 * 
 * LIMITATIONS ACTUELLES :
 * -----------------------
 * - Pas de support des unions types (PHP 8+)
 * - Pas de cache de la résolution
 * - Pas de configuration YAML/XML
 * - Pas de tags/décorateurs
 * 
 * Ces fonctionnalités seront ajoutées dans les phases suivantes !
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
