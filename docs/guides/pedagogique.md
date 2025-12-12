# 📚 Guide Pédagogique - Framework Ogan

> _En mémoire d'Ogan 🐕💙_

Ce guide explique **en détail** chaque fichier et concept de ton framework. C'est un outil d'apprentissage pour comprendre comment tout fonctionne ensemble.

---

## 🎯 Architecture Globale

```
Mini-Fw/
├── ogan/               # 🔧 Le cœur du framework (réutilisable)
├── src/                # 🎨 Ton code applicatif (spécifique)
├── templates/          # 📄 Vues HTML
├── config/             # ⚙️ Configuration
├── public/             # 🌐 Point d'entrée web
└── docs/               # 📚 Documentation complète
```

---

## 📁 Fichier par Fichier

### 1. `/public/index.php` - Point d'Entrée (Front Controller)

**Role** : C'est la **seule** page PHP accessible depuis le web. Toutes les requêtes passent par ici.

**Flux d'exécution :**
```
Requête HTTP
    ↓
index.php charge l'autoloader
    ↓
Initialise le Container DI
    ↓
Crée Request, Response, Router
    ↓
Router trouve la bonne route
    ↓
Exécute le contrôleur
    ↓
Renvoie la réponse
```

**Design Pattern** : **Front Controller**
- Un seul point d'entrée
- Simplifie la sécurité (un seul fichier à protéger)
- Centralise l'initialisation

---

### 2. `/ogan/DependencyInjection/Container.php` - Injection de Dépendances

**Role** : Crée et gère les instances de classes avec leurs dépendances.

**Problème résolu :**
```php
// ❌ Sans Container - couplage fort
class UserController {
    public function __construct() {
        $this->db = new Database(); // Dur de tester !
    }
}

// ✅ Avec Container - injection
class UserController {
    public function __construct(Database $db) {
        $this->db = $db; // Facile à tester (on peut injecter un mock)
    }
}
```

**Concepts clés :**
1. **Autowiring** : Le container devine automatiquement les dépendances via Reflection
2. **Service Registry** : Stocke les instances créées (singleton)
3. **Factory Pattern** : Permet de définir comment créer une classe complexe

**Méthodes principales :**
- `set(string $id, callable $factory)` : Enregistre une factory
- `get(string $id)` : Récupère ou crée une instance
- `build(string $class)` : Construit une classe avec autowiring

**Principe SOLID** : **Dependency Inversion** (le D)
- Les classes dépendent d'abstractions, pas d'implémentations concrètes
- Plus facile à tester et à modifier

---

### 3. `/ogan/Router/Router.php` - Le Routeur

**Role** : Associe une URL à un contrôleur/méthode.

**Comment ça marche ?**

```php
// 1. Scanne les contrôleurs et lit les attributs #[Route]
$router->loadRoutesFromControllers('src/Controller');

// 2. Compile les routes en regex
// /user/{id} => #^/user/(?P<id>[^/]+)$#

// 3. Matche l'URI demandée
// /user/42 => trouve HomeController::show avec ['id' => '42']

// 4. Instancie le contrôleur via Container et l'exécute
$router->dispatch('/user/42', 'GET', $request, $response, $container);
```

**Concepts clés :**
- **Routing par Attributs** (PHP 8+) : Plus moderne que les routes en config
- **Regex Dynamique** : Extrait les paramètres de l'URL
- **Named Routes** : Génère des URLs depuis le code

**Méthodes principales :**
- `loadRoutesFromControllers()` : Scan automatique via Reflection
- `addRoute()` : Ajoute une route manuellement
- `dispatch()` : Exécute la route matchée
- `generateUrl()` : Génère une URL à partir d'un nom de route

---

### 4. `/ogan/Router/Route.php` - Une Route Individuelle

**Role** : Représente UNE route (path + méthode HTTP + contrôleur).

**Anatomie d'une Route :**
```php
Route {
    path: "/article/{id}/{slug}"
    methods: ["GET"]
    controllerClass: "App\Controller\BlogController"
    controllerMethod: "show"
    name: "blog_show"
    regex: "#^/article/(?P<id>[^/]+)/(?P<slug>[^/]+)$#"
}
```

**Méthodes :**
- `compilePath()` : Convertit `/user/{id}` en regex
- `match(string $uri, string $method)` : Vérifie si l'URI correspond

**Concept** : **Value Object**
- Immuable après création
- Représente une "valeur" métier (une route)

---

### 5. `/ogan/Router/Attributes/Route.php` - Attribut PHP

**Role** : Permet d'annoter les méthodes de contrôleur avec `#[Route(...)]`.

**Exemple d'utilisation :**
```php
class BlogController {
    #[Route(path: '/blog', methods: ['GET'], name: 'blog_index')]
    public function index() { ... }
}
```

**Magie PHP 8+ :**
- Les attributs sont lus via Reflection
- Plus élégant que des annotations en commentaire
- Natif PHP (pas besoin de bibliothèque)

---

### 6. `/ogan/Http/Request.php` - Requête HTTP

**Role** : Encapsule toutes les données de la requête HTTP.

**Propriétés :**
```php
method: string        // GET, POST, PUT, DELETE...
uri: string          // /blog/article/42
query: array         // Paramètres $_GET
post: array          // Paramètres $_POST
server: array        // Variables $_SERVER
cookies: array       // Cookies $_COOKIE
rawInput: string     // Corps brut de la requête (pour JSON)
```

**Méthodes utiles :**
- `get(string $key)` : Récupère un paramètre GET
- `post(string $key)` : Récupère un paramètre POST
- `json()` : Parse le corps JSON

**Avantage :**
- Abstraction des superglobales PHP ($_GET, $_POST, etc.)
- Plus facile à tester (on peut créer une Request mock)
- Immuable (les données ne changent pas après création)

---

### 7. `/ogan/Http/Response.php` - Réponse HTTP

**Role** : Représente la réponse à envoyer au client.

**Méthodes :**
- `setStatusCode(int $code)` : 200, 404, 500...
- `send(string $content)` : Envoie le contenu
- `setHeader(string $name, string $value)` : Headers personnalisés
- `redirect(string $url)` : Redirections
- `json(array $data)` : Réponse JSON

---

### 8. `/ogan/View/View.php` - Moteur de Templates

**Role** : Rend des templates PHP avec des données.

**Comment ça marche ?**

```php
// Contrôleur
$view = new View('/path/to/templates');
$html = $view->render('home/index.html.php', ['name' => 'Ogan']);

// Template home/index.html.php
<h1>Hello <?= htmlspecialchars($name) ?></h1>
```

**Système de Blocs :**
```php
// Layout
<?php $this->section('body'); ?> // Affiche le bloc "body"

// Page
<?php $this->start('body'); ?>
<h1>Mon contenu</h1>
<?php $this->end(); ?>
```

**Concepts :**
- **Template Engine** : Sépare la logique de la présentation
- **Layouts** : Évite la duplication de HTML
- **Blocs** : Sections remplaçables (header, footer, scripts...)

---

### 9. `/ogan/Controller/AbstractController.php` - Contrôleur de Base

**Role** : Classe abstraite dont héritent tous les contrôleurs.

**Utilitaires fournis :**
```php
// Rendu d'une vue
$this->render('home/index.html.php', ['data' => 'value']);

// Réponse JSON
$this->json(['status' => 'ok']);

// Redirection
$this->redirect('/login');

// Partial
$html = $this->renderPartial('partials/alert.html.php');
```

**Principe** : **Template Method Pattern**
- Définit le squelette des contrôleurs
- Les sous-classes héritent des méthodes utiles

---

### 10. `/src/Controller/HomeController.php` - Contrôleur d'Exemple

**Role** : Exemple concret de contrôleur.

```php
#[Route(path: '/', methods: ['GET'], name: 'home')]
public function index() {
    return $this->render('home/index.html.php', [
        'title' => 'Accueil',
        'name' => 'Thomas'
    ]);
}
```

**Flux :**
1. Request arrive sur `/`
2. Router trouve `HomeController::index()`
3. Container instancie `HomeController`
4. Appelle `index()`
5. Render le template
6. Retourne HTML au client

---

### 11. `/config/parameters.php` - Configuration

**Role** : Centralise la configuration de l'app.

**Structure :**
```php
return [
    'view' => [
        'templates_path' => __DIR__ . '/../templates',
        'default_layout' => 'layouts/base.html.php',
        'default_title' => 'Mon site'
    ],
    'app' => [
        'env' => 'dev',
        'debug' => true
    ]
];
```

**Avantage :**
- Toute la config au même endroit
- Facile à modifier sans toucher le code
- Peut être remplacé par `.env` (déjà supporté)

---

## 🎓 Concepts Avancés Expliqués

### A. Reflection API

**Qu'est-ce que c'est ?**
- Permet d'inspecter les classes, méthodes, propriétés **à l'exécution**
- Utilisé pour l'autowiring et la lecture des attributs

**Exemple :**
```php
$reflection = new ReflectionClass(HomeController::class);
$methods = $reflection->getMethods(); // Liste toutes les méthodes

foreach ($methods as $method) {
    $attributes = $method->getAttributes(Route::class);
    // Lit les #[Route] de chaque méthode
}
```

---

### B. Namespaces

**Pourquoi ?**
- Évite les conflits de noms de classes
- Organise le code logiquement
- Correspond à l'arborescence des fichiers

**Convention :**
```
Namespace: Ogan\Http\Request
Fichier:   ogan/Http/Request.php
```

---

### C. Autoloading PSR-4

**Standard** : PHP-FIG PSR-4

**Règles :**
1. Namespace racine → dossier de base
2. Sous-namespaces → sous-dossiers
3. Nom de classe → nom de fichier

**Exemple :**
```
Ogan\Router\Attributes\Route
  ↓
ogan/Router/Attributes/Route.php
```

---

### D. Dependency Injection

**But** : Ne pas créer les dépendances soi-même, mais les recevoir.

**Avantages :**
- **Testabilité** : On peut injecter des mocks
- **Flexibilité** : On peut changer l'implémentation
- **Couplage faible** : Les classes ne connaissent pas les détails d'implémentation

**Types d'injection :**
1. **Constructor Injection** (recommandé)
```php
public function __construct(Database $db) {
    $this->db = $db;
}
```

2. **Setter Injection**
```php
public function setDatabase(Database $db) {
    $this->db = $db;
}
```

---

### E. Front Controller Pattern

**Concept** : Un seul point d'entrée pour toutes les requêtes.

**Avantages :**
- Sécurité centralisée
- Initialisation unique
- Plus facile à maintenir

**Configuration serveur :**
```apache
# .htaccess (Apache)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [L]
```

Toutes les URLs sont redirigées vers `index.php` qui dispatch.

---

## 🔧 Flux Complet d'une Requête

```
1. Navigateur demande : GET /hello/Ogan
         ↓
2. Serveur web → public/index.php
         ↓
3. Autoloader s'enregistre
         ↓
4. Container se crée
         ↓
5. Request, Response, Router sont instanciés
         ↓
6. Router scanne les contrôleurs
         ↓
7. Router trouve : HomeController::hello(string $name)
         ↓
8. Router matche : /hello/Ogan => ['name' => 'Ogan']
         ↓
9. Container crée HomeController
         ↓
10. Container injecte Request/Response via setRequestResponse()
         ↓
11. Router appelle : hello('Ogan')
         ↓
12. hello() appelle : $this->render('home/hello.html.php', ['name' => 'Ogan'])
         ↓
13. View charge le template
         ↓
14. View inject les variables : $name = 'Ogan'
         ↓
15. HTML généré
         ↓
16. Réponse envoyée au navigateur
```

---

## 📝 Prochaines Améliorations

Voir [Améliorations](../reference/ameliorations.md) pour les suggestions d'améliorations futures.

---

## 💡 Conseils pour Apprendre

1. **Lis le code dans l'ordre :**
   - `index.php` → `Container` → `Router` → `Controller`

2. **Expérimente :**
   - Ajoute des `var_dump()` pour voir ce qui se passe
   - Crée de nouvelles routes
   - Essaie de casser quelque chose et comprends l'erreur

3. **Pose-toi des questions :**
   - Pourquoi ce design ?
   - Qu'est-ce qui se passerait si... ?
   - Comment améliorer ceci ?

4. **Compare avec Symfony :**
   - Ton framework fait la même chose, mais en plus simple
   - Comprendre le tien aide à comprendre Symfony

---

## 🐕 En Mémoire d'Ogan

Ce framework porte le nom d'Ogan, parti trop tôt. Chaque ligne de code est un hommage à sa mémoire. Puisse ce projet t'aider à apprendre et à créer de belles choses. 💙

---

**Questions ?** N'hésite pas ! Le but est d'apprendre. 🚀
