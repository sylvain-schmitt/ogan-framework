# 📘 Documentation Mini-Fw (Ogan)

Bienvenue dans la documentation de votre framework sur mesure.
Cette architecture sépare le **cœur du framework** (`ogan/`) de l'**application** (`src/`).

---

## 🚀 1. Routing

Le système de routing utilise les **Attributs PHP 8** pour définir les routes directement au dessus des contrôleurs.

### Utilisation de base
```php
use Ogan\Router\Attributes\Route;

#[Route(path: '/produits', methods: ['GET'], name: 'product_list')]
public function index() { ... }
```

### Paramètres et Contraintes
Vous pouvez capturer des variables dans l'URL. Le framework supporte des contraintes simplifiées :

| Syntaxe | Description | Regex équivalente |
| :--- | :--- | :--- |
| `{id}` | Paramètre simple (tout sauf /) | `[^/]+` |
| `{id:}` | **Entier uniquement** | `\d+` |
| `{slug:}` | **Slug** (lettres, chiffres, tiret) | `[a-z0-9-]+` |
| `{page:?}` | **Optionnel** (peut être null) | N/A |

#### Exemple complet
```php
// URL valide : /blog/mon-article/12
#[Route(path: '/blog/{slug:}/{id:}', methods: ['GET'])]
public function show(string $slug, string $id) 
{
    // $slug = "mon-article"
    // $id = "12"
}
```

---

## 🎮 2. Controllers

Tous vos contrôleurs héritent de `Ogan\Controller\AbstractController`. Cela vous donne accès à des méthodes utilitaires puissantes.

### Méthodes disponibles

#### `render(string $view, array $params = [])`
Affiche une vue HTML.
```php
public function index()
{
    return $this->render('home/index.html.php', [
        'user' => 'Jean',
        'items' => [1, 2, 3]
    ]);
}
```

#### `json(array|object $data)`
Renvoie une réponse JSON (API).
```php
public function api()
{
    return $this->json([
        'status' => 'success',
        'data' => ['id' => 1]
    ]);
}
```

#### `redirect(string $url, int $status = 302)`
Redirige l'utilisateur.
```php
public function save()
{
    // ... traitement ...
    $this->redirect('/success');
}
```

---

## 🎨 3. Views (Moteur de Template)

Le moteur de vue est situé dans `Ogan\View`. Il propose un système d'héritage, de composants et de sécurité (XSS via `$this->e()`).

### Layouts et Héritage
Un template (ex: `home/index.html.php`) peut étendre un layout parent.

**Fermez toujours vos blocs !**

```php
<?php $this->extend('layouts/base'); ?>

<?php $this->start('body'); ?>
    <h1>Mon Contenu</h1>
<?php $this->end(); ?>
```

### Components
Pour réutiliser du code (Navbar, Card, Alert), utilisez les **Composants** avec des props.

```php
<!-- Appel dans la vue -->
<?= $this->component('card', [
    'title' => 'Mon Titre',
    'content' => 'Description...'
]); ?>
```

Le fichier du composant (`templates/components/card.html.php`) reçoit les variables directement :
```php
<div class="card">
    <h3><?= $this->e($title) ?></h3>
    <p><?= $content ?></p>
</div>
```

### Assets Helpers
Pour inclure des fichiers CSS, JS ou Images depuis le dossier `public/`.

```php
<link rel="stylesheet" href="<?= $this->asset('assets/css/style.css') ?>">
<img src="<?= $this->asset('assets/img/logo.png') ?>">
```

---

## 📡 4. HTTP (Request & Response)

### Request (`Ogan\Http\Request`)
Injectée automatiquement dans vos contrôleurs si vous en avez besoin (via le constructeur ou `__construct`).

Propriétés accessibles :
- `$request->query` ($_GET)
- `$request->post` ($_POST)
- `$request->files` ($_FILES)
- `getMethod()`, `getUri()`, `isAjax()`...

```php
// Récupérer un paramètre GET ?page=2
$page = $this->request->get('page', 1);

// Récupérer un paramètre POST
$email = $this->request->post('email');

// Vérifier si c'est de l'AJAX
if ($this->request->isAjax()) { ... }
```

### 🪄 Gestion Automatique du JSON
Si vous recevez une requête API avec un header `Content-Type: application/json`, la méthode `$this->request->post()` ira **automatiquement** chercher les données dans le JSON !

```php
// Appel API : POST /api/users { "name": "Alice" }

public function create()
{
    // Fonctionne pour un formulaire classique OU du JSON !
    $name = $this->request->post('name');

    // Si vous voulez tout le tableau JSON explicitement
    $data = $this->request->json();
}
```

### Response (`Ogan\Http\Response`)
Gère l'envoi de la réponse au client (headers, contenu, code HTTP).
Utilisée en interne par `render()` et `json()`.

---

## 💉 5. Dependency Injection (Container)

Le framework utilise un **Container** (dans `Ogan\DependencyInjection`) qui gère vos services.
L'**Autowiring** est activé : si votre contrôleur ou service a besoin d'une classe dans son constructeur, le Container l'injectera automatiquement !

#### Exemple Service
```php
namespace App\Service;

class Mailer {
    public function send($to, $msg) { ... }
}
```

#### Exemple Contrôleur
```php
class ContactController extends AbstractController
{
    // Le Mailer est injecté automatiquement !
    public function __construct(private Mailer $mailer) {}

    public function send()
    {
        $this->mailer->send('admin@site.com', 'Coucou');
    }
}
```
Vous n'avez rien à configurer. Tant que la classe existe, elle est chargée. 🧙‍♂️

---

## 📁 Structure des Dossiers

- `ogan/` : **Cœur du Framework** (ne pas toucher sauf pour améliorer le moteur).
- `public/` : Point d'entrée (`index.php`) et assets (`css/`, `js/`).
- `src/` : **Votre Application**.
    - `Controller/` : Vos pages.
- `templates/` : Vos vues HTML.
    - `layouts/` : Gabarits principaux (base.html.php).
    - `components/` : Éléments réutilisables.
    - `home/`, `user/`... : Vues spécifiques.
- `config/` : Configuration (middlewares, paramètres).

---

*Documentation générée par votre Assistant IA - 2025*
