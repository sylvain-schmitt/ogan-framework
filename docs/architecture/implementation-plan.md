# Plan d'Amélioration du Mini-Framework PHP

## 📊 Analyse de l'Existant

Votre codebase actuelle possède déjà une excellente base :

### ✅ Points Forts
- **Autoloader PSR-4 maison** fonctionnel
- **Router avec attributs PHP 8+** (moderne et élégant)
- **Container DI avec autowiring** (injection de dépendances automatique)
- **Système de vues** avec layouts et partials
- **Séparation MVC** claire
- **Architecture orientée objet** propre

### 🔧 Points à Améliorer
1. **Manque d'interfaces** : Les classes ne respectent pas encore les principes SOLID (Inversion de Dépendances)
2. **Gestion des erreurs** : Pas de système d'exceptions personnalisées
3. **Request/Response** : Fonctionnalités limitées (pas de gestion headers, cookies, files)
4. **Templates** : Pas d'héritage multi-niveaux ni de composants réutilisables
5. **Sécurité** : Échappement manuel dans les vues
6. **Configuration** : Système basique, pas de gestion d'environnements
7. **Middlewares** : Absents (pour authentification, CORS, etc.)

---

## 🎯 Objectifs Pédagogiques

Nous allons améliorer progressivement votre framework en expliquant **POURQUOI** et **COMMENT** chaque changement respecte les bonnes pratiques :

1. **SOLID** : Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
2. **Design Patterns** : Factory, Strategy, Dependency Injection, Front Controller
3. **PSR** : Standards PHP (PSR-4, PSR-7, PSR-11, PSR-3)
4. **Sécurité** : XSS, CSRF, injection SQL
5. **Testabilité** : Code facilement testable grâce aux interfaces

---

## 📋 Propositions d'Améliorations

### Phase 1 : Interfaces et Contrats (Principes SOLID) 🎓

> **Concept** : Le principe **D** de SOLID (Dependency Inversion) dit qu'on doit dépendre d'abstractions, pas d'implémentations concrètes.

#### Créer les Interfaces

##### [NEW] [ContainerInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/src/DependencyInjection/ContainerInterface.php)
Interface PSR-11 pour le container de services avec méthodes [get()](file:///home/ogan/projets/PHP/Mini-Fw/ogan/DependencyInjection/Container.php#99-150) et `has()`.

##### [NEW] [RequestInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Http/RequestInterface.php)
Contrat pour les requêtes HTTP avec méthodes standardisées.

##### [NEW] [ResponseInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Http/ResponseInterface.php)
Contrat pour les réponses HTTP.

##### [NEW] [RouterInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Router/RouterInterface.php)
Interface pour le routeur avec méthodes [addRoute()](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Router/Router.php#58-70), `match()`, [generateUrl()](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Router/Router.php#71-92).

##### [NEW] [ViewInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/src/View/ViewInterface.php)
Interface pour le moteur de templates.

#### Modifier les Classes Existantes

##### [MODIFY] [Container.php](file:///home/ogan/projets/PHP/Mini-Fw/src/DependencyInjection/Container.php)
Implémenter `ContainerInterface` et ajouter la méthode `has()`.

##### [MODIFY] [Request.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Http/Request.php)
Implémenter `RequestInterface` et enrichir avec headers, files, session.

##### [MODIFY] [Response.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Http/Response.php)
Implémenter `ResponseInterface` et ajouter headers, cookies, redirects.

##### [MODIFY] [Router.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Router/Router.php)
Implémenter `RouterInterface`.

##### [MODIFY] [View.php](file:///home/ogan/projets/PHP/Mini-Fw/src/View/View.php)
Implémenter `ViewInterface` et améliorer le système de blocs.

---

### Phase 2 : Gestion des Erreurs et Exceptions

> **Concept** : Créer des exceptions personnalisées pour mieux gérer les erreurs spécifiques au framework.

##### [NEW] [FrameworkException.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Exception/FrameworkException.php)
Exception de base du framework.

##### [NEW] [NotFoundException.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Exception/NotFoundException.php)
Pour les routes/ressources introuvables (404).

##### [NEW] [ContainerException.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Exception/ContainerException.php)
Pour les erreurs du container DI.

##### [NEW] [RoutingException.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Exception/RoutingException.php)
Pour les erreurs de routing.

##### [NEW] [ErrorHandler.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Error/ErrorHandler.php)
Gestionnaire global d'erreurs avec pages d'erreur personnalisées.

---

### Phase 3 : Amélioration du Moteur de Templates

> **Concept** : Système d'héritage de templates (layout → page → section) avec composants réutilisables.

##### [MODIFY] [View.php](file:///home/ogan/projets/PHP/Mini-Fw/src/View/View.php)
Ajouter :
- `extend(string $layout)` : Indiquer le layout parent
- `component(string $name, array $props)` : Inclure un composant
- `escape(string $value)` : Échappement automatique (sécurité XSS)
- Gestion de l'héritage multi-niveaux

##### [NEW] Fichiers de Templates
- `templates/components/alert.html.php` : Composant d'alerte réutilisable
- `templates/components/card.html.php` : Composant carte
- `templates/components/button.html.php` : Composant bouton

##### [MODIFY] [base.html.php](file:///home/ogan/projets/PHP/Mini-Fw/templates/layouts/base.html.php)
Améliorer avec :
- Blocs multiples (head, scripts, styles, body)
- Assets (CSS/JS)
- Meta tags

---

### Phase 4 : Middlewares et Pipeline

> **Concept** : Les middlewares permettent d'exécuter du code avant/après le contrôleur (authentification, CORS, logging, etc.)

##### [NEW] [MiddlewareInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Middleware/MiddlewareInterface.php)
Interface avec méthode `handle(Request $request, callable $next)`.

##### [NEW] [middlewares exemples](file:///home/ogan/projets/PHP/Mini-Fw/src/Middleware)
- `AuthMiddleware.php` : Vérification authentification
- `CorsMiddleware.php` : Headers CORS
- `CsrfMiddleware.php` : Protection CSRF
- `LoggerMiddleware.php` : Logs des requêtes

##### [NEW] [MiddlewarePipeline.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Middleware/MiddlewarePipeline.php)
Gestion de la chaîne de middlewares.

##### [MODIFY] [Router.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Router/Router.php)
Ajout de la gestion des middlewares par route ou globaux.

---

### Phase 5 : Enrichissement Request/Response

##### [MODIFY] [Request.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Http/Request.php)
Ajouter :
- `getHeaders()` : Récupérer tous les headers
- `getHeader(string $name)` : Header spécifique
- `getFiles()` : Fichiers uploadés
- `isJson()` : Détection requête JSON
- `isAjax()` : Détection AJAX
- `getClientIp()` : IP du client
- Session management

##### [MODIFY] [Response.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Http/Response.php)
Ajouter :
- `setHeader(string $name, string $value)`
- `setCookie(...)`
- [json(array $data, int $status = 200)](file:///home/ogan/projets/PHP/Mini-Fw/src/Controller/BaseController.php#34-43)
- [redirect(string $url, int $status = 302)](file:///home/ogan/projets/PHP/Mini-Fw/src/Controller/BaseController.php#52-60)
- `download(string $file, string $name)`

---

### Phase 6 : Router Avancé

##### [MODIFY] [Route.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Router/Route.php)
Ajouter :
- Contraintes de paramètres : `{id:\d+}`, `{slug:[a-z-]+}`
- Paramètres optionnels : `{category?}`
- Valeurs par défaut

##### [MODIFY] [Router.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Router/Router.php)
Ajouter :
- Groupes de routes avec préfixes : `$router->group('/admin', ...)`
- Middlewares par groupe
- Sous-domaines

##### [NEW] [RouteCollection.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Router/RouteCollection.php)
Collection de routes pour mieux organiser.

---

### Phase 7 : Services Utilitaires

##### [NEW] [Validator.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Validation/Validator.php)
Service de validation de données avec règles (required, email, min, max, etc.)

##### [NEW] [Database.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Database/Database.php)
Abstraction PDO avec query builder basique.

##### [NEW] [Logger.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Logger/Logger.php)
Logger PSR-3 (info, warning, error, debug).

##### [NEW] [Session.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Session/Session.php)
Gestionnaire de session avec flash messages.

##### [NEW] [Config.php](file:///home/ogan/projets/PHP/Mini-Fw/src/Config/Config.php)
Gestionnaire de configuration avec support .env.

---

### Phase 7.5 : ORM Maison (Object-Relational Mapping) 🗄️

> **Concept** : Un ORM transforme les tables de base de données en objets PHP et vice-versa. C'est comme un traducteur entre le monde relationnel (SQL) et le monde objet (PHP).

**Pourquoi créer un ORM ?**
- Comprendre comment Doctrine et Eloquent fonctionnent
- Apprendre les design patterns : Active Record, Data Mapper, Repository
- Maîtriser PDO et les requêtes préparées
- Sécuriser contre les injections SQL

#### 1. Couche Database de Base

##### [NEW] [Database.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/Database.php)
Gestion de la connexion PDO avec :
- Connexion singleton
- Configuration depuis parameters.php
- Transactions
- Gestion des erreurs

```php
class Database {
    private static ?PDO $pdo = null;
    
    public static function getConnection(): PDO {
        // Singleton pattern
    }
    
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}
```

#### 2. Query Builder

##### [NEW] [QueryBuilder.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/QueryBuilder.php)
Construction de requêtes SQL de manière orientée objet :

```php
$query = new QueryBuilder();
$query->select(['id', 'name', 'email'])
      ->from('users')
      ->where('age', '>', 18)
      ->orderBy('name', 'ASC')
      ->limit(10);

// Génère : SELECT id, name, email FROM users WHERE age > ? ORDER BY name ASC LIMIT 10
```

**Méthodes** :
- `select(array $columns)` : Colonnes à récupérer
- `from(string $table)` : Table source
- `where(string $column, string $operator, $value)` : Condition
- `andWhere()`, `orWhere()` : Conditions multiples
- `join()`, `leftJoin()` : Jointures
- `orderBy()`, `groupBy()` : Tri et regroupement
- `limit()`, `offset()` : Pagination
- `insert()`, `update()`, `delete()` : Opérations CRUD

#### 3. Entity/Model de Base

##### [NEW] [Model.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/Model.php)
Classe de base pour tous les modèles (Active Record Pattern) :

```php
abstract class Model {
    protected static string $table;    // Nom de la table
    protected array $attributes = [];  // Données de l'entité
    protected bool $exists = false;    // Nouvelle vs. existante
    
    // CRUD Methods
    public static function find(int $id): ?static;
    public static function all(): array;
    public static function where(...): array;
    public function save(): bool;
    public function delete(): bool;
    
    // Magic methods
    public function __get(string $name);
    public function __set(string $name, $value);
}
```

**Exemple d'utilisation** :
```php
class User extends Model {
    protected static string $table = 'users';
}

// Créer
$user = new User();
$user->name = 'Ogan';
$user->email = 'ogan@example.com';
$user->save();

// Lire
$user = User::find(1);
$users = User::where('age', '>', 18);

// Mettre à jour
$user->name = 'Ogan Updated';
$user->save();

// Supprimer
$user->delete();
```

#### 4. Repository Pattern

##### [NEW] [RepositoryInterface.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/RepositoryInterface.php)
Interface pour les repositories :

```php
interface RepositoryInterface {
    public function find(int $id): ?object;
    public function findAll(): array;
    public function findBy(array $criteria): array;
    public function save(object $entity): bool;
    public function delete(object $entity): bool;
}
```

##### [NEW] [AbstractRepository.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/AbstractRepository.php)
Implémentation de base séparant la logique métier de la persistance (Data Mapper Pattern).

**Exemple** :
```php
class UserRepository extends AbstractRepository {
    public function findByEmail(string $email): ?User {
        return $this->findOneBy(['email' => $email]);
    }
    
    public function findActive(): array {
        return $this->findBy(['active' => true]);
    }
}
```

#### 5. Relations

##### [NEW] [Relation.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/Relation.php)
Gestion des relations entre entités :

**Types de relations** :
- **OneToMany** : Un utilisateur a plusieurs articles
- **ManyToOne** : Plusieurs articles appartiennent à un utilisateur
- **ManyToMany** : Un article a plusieurs tags, un tag a plusieurs articles

```php
class User extends Model {
    public function posts(): OneToMany {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class Post extends Model {
    public function user(): ManyToOne {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function tags(): ManyToMany {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }
}

// Utilisation
$user = User::find(1);
$posts = $user->posts()->get(); // Lazy loading
```

#### 6. Migrations Basiques

##### [NEW] [Migration.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/Migration.php)
Système de migrations pour créer/modifier les tables :

```php
class CreateUsersTable extends Migration {
    public function up(): void {
        $this->schema->create('users', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }
    
    public function down(): void {
        $this->schema->drop('users');
    }
}
```

##### [NEW] [Schema.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/Schema.php)
Constructeur de schémas SQL.

#### 7. Hydratation Automatique

##### [NEW] [Hydrator.php](file:///home/ogan/projets/PHP/Mini-Fw/ogan/Database/Hydrator.php)
Transforme les résultats SQL en objets :

```php
// Résultat PDO (array)
['id' => 1, 'name' => 'Ogan', 'email' => 'ogan@example.com']

// Devient (objet User)
User {
    id: 1,
    name: 'Ogan',
    email: 'ogan@example.com'
}
```

#### Structure ORM Complète

```
ogan/Database/
├── Database.php           # Connexion PDO
├── QueryBuilder.php       # Construction de requêtes
├── Model.php              # Classe de base (Active Record)
├── RepositoryInterface.php
├── AbstractRepository.php # Data Mapper
├── Relation.php           # Gestion des relations
├── Relations/
│   ├── OneToMany.php
│   ├── ManyToOne.php
│   └── ManyToMany.php
├── Migration.php          # Système de migrations
├── Schema.php             # Constructeur de schémas
└── Hydrator.php           # Transformation array → objet
```

---

### Phase 8 : Intégration Composer

##### [NEW] [composer.json](file:///home/ogan/projets/PHP/Mini-Fw/composer.json)
Configuration Composer avec autoload PSR-4 et possibilité d'ajouter des packages.

```json
{
  "name": "mini-fw/framework",
  "description": "Mini Framework PHP MVC pédagogique",
  "type": "project",
  "require": {
    "php": ">=8.1"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

##### [MODIFY] [autoload.php](file:///home/ogan/projets/PHP/Mini-Fw/autoload.php)
Détecter si Composer est installé, sinon utiliser l'autoloader maison.

---

### Phase 9 : Documentation et Exemples

##### [NEW] [README.md](file:///home/ogan/projets/PHP/Mini-Fw/README.md)
Documentation complète du framework.

##### [NEW] [docs/](file:///home/ogan/projets/PHP/Mini-Fw/docs)
- `01-installation.md` : Installation et configuration
- `02-routing.md` : Utilisation du router
- `03-controllers.md` : Création de contrôleurs
- `04-views.md` : Système de templates
- `05-container.md` : Injection de dépendances
- `06-middlewares.md` : Création de middlewares
- `07-database.md` : Accès à la base de données
- `08-solid-principles.md` : Explication des principes SOLID appliqués

##### [NEW] Exemples d'application
- CRUD complet (Blog, Todo List, etc.)
- Authentification simple
- Upload de fichiers
- API REST

---

## 🎓 Approche Pédagogique Recommandée

### Étape par Étape (ordre suggéré)

1. **Semaine 1** : Interfaces et principes SOLID
   - Comprendre POURQUOI utiliser des interfaces
   - Implémenter les interfaces de base
   - Modifier les classes existantes

2. **Semaine 2** : Exceptions et gestion d'erreurs
   - Créer les exceptions personnalisées
   - Implémenter le gestionnaire d'erreurs global
   - Pages d'erreur jolies

3. **Semaine 3** : Enrichissement HTTP
   - Améliorer Request (headers, files, session)
   - Améliorer Response (cookies, JSON, downloads)
   - Tests avec Postman/curl

4. **Semaine 4** : Templates avancés
   - Système d'héritage multi-niveaux
   - Composants réutilisables
   - Helpers et sécurité (échappement)

5. **Semaine 5** : Middlewares
   - Comprendre le pattern Pipeline
   - Créer des middlewares simples
   - Intégrer au router

6. **Semaine 6** : Router avancé
   - Contraintes de paramètres
   - Groupes de routes
   - Génération d'URLs

7. **Semaine 7** : Services utilitaires
   - Validator
   - Database (PDO)
   - Logger
   - Session

8. **Semaine 8** : Composer et finalisation
   - Configuration Composer
   - Documentation complète
   - Exemple d'application

---

## ✅ Verification Plan

### Tests Manuels
- Tester toutes les routes (paramètres, contraintes, 404)
- Vérifier l'injection de dépendances
- Tester les middlewares (authentification mock, CORS)
- Valider les templates (héritage, composants, échappement)
- Uploader un fichier
- Tester les redirections et cookies

### Vérification SOLID
- **S** : Chaque classe a UNE seule responsabilité
- **O** : Extensions possibles sans modifier le code existant (interfaces)
- **L** : Les implémentations respectent leurs interfaces
- **I** : Interfaces petites et ciblées
- **D** : Dépendance sur abstractions (interfaces), pas implémentations

### Documentation
- README clair avec quick start
- Exemples de code commentés
- Explication des design patterns utilisés

---

## 📚 Ressources Recommandées

- **PSR Standards** : [PHP-FIG](https://www.php-fig.org/)
- **SOLID** : [Uncle Bob's SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- **Design Patterns PHP** : [DesignPatternsPHP](https://designpatternsphp.readthedocs.io/)
- **Symfony Components** : Pour inspiration (pas pour copier, mais comprendre)
