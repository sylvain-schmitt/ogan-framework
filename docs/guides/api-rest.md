# 🔌 API REST - Ogan Framework

> Guide pour créer des APIs RESTful avec le framework Ogan

## 📖 Introduction

Le framework Ogan fournit un support complet pour la création d'APIs REST :
- `ApiController` avec des méthodes d'aide pour les réponses JSON
- Sérialisation des modèles avec `toArray()` et `toJson()`
- Commande `make:api` pour générer des controllers CRUD
- Propriétés `$hidden` et `$visible` pour contrôler la sérialisation

## 🚀 Génération rapide

```bash
# Générer un controller API pour User
php bin/console make:api User

# Écraser si existant
php bin/console make:api User --force
```

**Endpoints générés :**
- `GET /api/users` → Liste
- `GET /api/users/{id}` → Afficher
- `POST /api/users` → Créer
- `PUT /api/users/{id}` → Modifier
- `DELETE /api/users/{id}` → Supprimer

## 📦 ApiController

Votre controller API doit hériter de `ApiController` :

```php
<?php

namespace App\Controller\Api;

use App\Model\Article;
use Ogan\Controller\ApiController;
use Ogan\Http\Response;
use Ogan\Router\Attributes\Route;

class ArticleController extends ApiController
{
    #[Route(path: '/api/articles', methods: ['GET'])]
    public function index(): Response
    {
        return $this->success(Article::all());
    }
    
    #[Route(path: '/api/articles/{id}', methods: ['GET'])]
    public function show(int $id): Response
    {
        $article = Article::find($id);
        
        if (!$article) {
            return $this->notFound('Article not found');
        }
        
        return $this->success($article);
    }
}
```

## 📋 Méthodes disponibles

| Méthode | Description | Code HTTP |
|---------|-------------|-----------|
| `json($data, $status)` | Réponse JSON brute | Custom |
| `success($data, $message)` | Réponse de succès | 200 |
| `created($data, $message)` | Création réussie | 201 |
| `noContent()` | Pas de contenu | 204 |
| `error($message, $status, $errors)` | Erreur générique | Custom |
| `notFound($message)` | Ressource non trouvée | 404 |
| `unauthorized($message)` | Non authentifié | 401 |
| `forbidden($message)` | Accès refusé | 403 |
| `validationError($errors, $message)` | Erreurs de validation | 422 |
| `getJsonBody()` | Récupère le body JSON | - |

### Format de réponse

```json
// success()
{
    "success": true,
    "message": "Optional message",
    "data": { ... }
}

// error()
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}
```

## 🔒 Sérialisation des modèles

### Propriétés $hidden et $visible

```php
class User extends Model
{
    // Ces attributs ne seront jamais inclus
    protected array $hidden = ['password', 'remember_token'];
    
    // OU : seuls ces attributs seront inclus
    protected array $visible = ['id', 'name', 'email'];
}
```

### Méthodes de sérialisation

```php
$user = User::find(1);

// Convertir en tableau
$array = $user->toArray();

// Convertir en JSON
$json = $user->toJson();
$jsonPretty = $user->toJson(JSON_PRETTY_PRINT);

// Modifier temporairement les attributs visibles
$user->makeHidden('email')->toArray();
$user->makeVisible('password')->toArray();
```

### Relations

Les relations chargées sont automatiquement incluses :

```php
class Article extends Model
{
    public function getAuthor(): User
    {
        return $this->belongsTo(User::class);
    }
}

$article = Article::find(1);
$article->author = $article->getAuthor(); // Charger la relation

$article->toArray();
// Inclura automatiquement 'author' => [...]
```

## 💡 Exemples

### Validation et création

```php
#[Route(path: '/api/articles', methods: ['POST'])]
public function store(): Response
{
    $data = $this->getJsonBody();
    
    // Validation manuelle
    $errors = [];
    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    }
    
    if (!empty($errors)) {
        return $this->validationError($errors);
    }
    
    $article = new Article($data);
    if ($article->save()) {
        return $this->created($article);
    }
    
    return $this->error('Failed to create article');
}
```

### Mise à jour

```php
#[Route(path: '/api/articles/{id}', methods: ['PUT'])]
public function update(int $id): Response
{
    $article = Article::find($id);
    
    if (!$article) {
        return $this->notFound();
    }
    
    $data = $this->getJsonBody();
    
    foreach ($data as $key => $value) {
        $setter = 'set' . ucfirst($key);
        if (method_exists($article, $setter)) {
            $article->$setter($value);
        }
    }
    
    if ($article->save()) {
        return $this->success($article, 'Updated');
    }
    
    return $this->error('Failed to update');
}
```

### Suppression (avec Soft Delete)

```php
#[Route(path: '/api/articles/{id}', methods: ['DELETE'])]
public function destroy(int $id): Response
{
    $article = Article::find($id);
    
    if (!$article) {
        return $this->notFound();
    }
    
    // Utilise soft delete si le trait est activé
    if ($article->delete()) {
        return $this->noContent();
    }
    
    return $this->error('Failed to delete');
}
```

## 🔗 Tester l'API

```bash
# GET - Liste
curl http://localhost:8000/api/users

# GET - Un élément
curl http://localhost:8000/api/users/1

# POST - Créer
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com"}'

# PUT - Modifier
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated"}'

# DELETE - Supprimer
curl -X DELETE http://localhost:8000/api/users/1
```
