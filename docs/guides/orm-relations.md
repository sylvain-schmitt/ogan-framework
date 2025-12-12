# 🔗 Relations ORM - Ogan Framework

> Guide complet sur les relations entre modèles (style Symfony)

## 📋 Vue d'Ensemble

Ogan Framework supporte 4 types de relations entre modèles :
- **OneToMany** : Un-à-Plusieurs
- **ManyToOne** : Plusieurs-à-Un
- **OneToOne** : Un-à-Un
- **ManyToMany** : Plusieurs-à-Plusieurs

---

## 🔗 OneToMany (Un-à-Plusieurs)

### Concept

Un modèle parent peut avoir plusieurs modèles enfants.

**Exemple** : Un utilisateur peut avoir plusieurs posts.

### Structure de Base de Données

```sql
-- Table users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255)
);

-- Table posts
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    content TEXT,
    user_id INT,  -- Clé étrangère
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Définition dans le Modèle

```php
// src/Model/User.php
namespace App\Model;

use Ogan\Database\Model;

class User extends Model
{
    protected static string $table = 'users';

    /**
     * Relation OneToMany : Un utilisateur a plusieurs posts
     */
    public function getPosts(): \Ogan\Database\Relations\OneToMany
    {
        return $this->oneToMany(Post::class, 'user_id');
    }
}
```

### Utilisation

```php
// Récupérer tous les posts d'un utilisateur
$user = User::find(1);
$posts = $user->getPosts()->getResults(); // Tableau de Post

// Avec contraintes
$recentPosts = $user->getPosts()
    ->where('created_at', '>', '2024-01-01')
    ->orderBy('created_at', 'DESC')
    ->getResults();

// Compter les posts
$postCount = $user->getPosts()->count();
```

---

## 🔗 ManyToOne (Plusieurs-à-Un)

### Concept

Plusieurs modèles enfants appartiennent à un modèle parent.

**Exemple** : Plusieurs posts appartiennent à un utilisateur.

### Structure de Base de Données

Même structure que OneToMany (c'est l'inverse).

### Définition dans le Modèle

```php
// src/Model/Post.php
namespace App\Model;

use Ogan\Database\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    /**
     * Relation ManyToOne : Un post appartient à un utilisateur
     */
    public function getUser(): \Ogan\Database\Relations\ManyToOne
    {
        return $this->manyToOne(User::class, 'user_id');
    }
}
```

### Utilisation

```php
// Récupérer l'utilisateur d'un post
$post = Post::find(1);
$user = $post->getUser()->getResults(); // Instance de User ou null

// Utilisation dans un template
if ($user) {
    echo $user->name;
}
```

---

## 🔗 OneToOne (Un-à-Un)

### Concept

Un modèle parent a exactement un modèle enfant.

**Exemple** : Un utilisateur a exactement un profil.

### Structure de Base de Données

```sql
-- Table users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255)
);

-- Table profiles
CREATE TABLE profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bio TEXT,
    avatar VARCHAR(255),
    user_id INT UNIQUE,  -- Clé étrangère unique
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Définition dans le Modèle

```php
// src/Model/User.php
class User extends Model
{
    protected static string $table = 'users';

    /**
     * Relation OneToOne : Un utilisateur a un profil
     */
    public function getProfile(): \Ogan\Database\Relations\OneToOne
    {
        return $this->oneToOne(Profile::class, 'user_id');
    }
}
```

### Utilisation

```php
// Récupérer le profil d'un utilisateur
$user = User::find(1);
$profile = $user->getProfile()->getResults(); // Instance de Profile ou null

if ($profile) {
    echo $profile->bio;
}
```

---

## 🔗 ManyToMany (Plusieurs-à-Plusieurs)

### Concept

Plusieurs modèles sont liés à plusieurs autres modèles via une table pivot.

**Exemple** : Un utilisateur peut avoir plusieurs rôles, et un rôle peut être assigné à plusieurs utilisateurs.

### Structure de Base de Données

```sql
-- Table users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255)
);

-- Table roles
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255)
);

-- Table pivot user_role
CREATE TABLE user_role (
    user_id INT,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### Définition dans le Modèle

```php
// src/Model/User.php
class User extends Model
{
    protected static string $table = 'users';

    /**
     * Relation ManyToMany : Un utilisateur a plusieurs rôles
     */
    public function getRoles(): \Ogan\Database\Relations\ManyToMany
    {
        return $this->manyToMany(
            Role::class,
            'user_role',      // Table pivot
            'user_id',        // Clé étrangère vers users
            'role_id'         // Clé étrangère vers roles
        );
    }
}
```

### Utilisation

```php
// Récupérer les rôles d'un utilisateur
$user = User::find(1);
$roles = $user->getRoles()->getResults(); // Tableau de Role

// Attacher un rôle
$user->getRoles()->attach($roleId);

// Attacher un rôle avec données supplémentaires dans la table pivot
$user->getRoles()->attach($roleId, [
    'assigned_at' => date('Y-m-d H:i:s')
]);

// Détacher un rôle
$user->getRoles()->detach($roleId);

// Détacher tous les rôles
$user->getRoles()->detach();

// Compter les rôles
$roleCount = $user->getRoles()->count();
```

---

## 🎯 Exemples Complets

### Exemple 1 : Blog (User → Posts)

```php
// src/Model/User.php
class User extends Model
{
    protected static string $table = 'users';

    public function getPosts(): \Ogan\Database\Relations\OneToMany
    {
        return $this->oneToMany(Post::class, 'user_id');
    }
}

// src/Model/Post.php
class Post extends Model
{
    protected static string $table = 'posts';

    public function getUser(): \Ogan\Database\Relations\ManyToOne
    {
        return $this->manyToOne(User::class, 'user_id');
    }
}

// Utilisation
$user = User::find(1);
$posts = $user->getPosts()->getResults();

foreach ($posts as $post) {
    echo $post->title;
    echo $post->getUser()->getResults()->name; // Auteur
}
```

### Exemple 2 : E-commerce (Product → Categories)

```php
// src/Model/Product.php
class Product extends Model
{
    protected static string $table = 'products';

    public function getCategories(): \Ogan\Database\Relations\ManyToMany
    {
        return $this->manyToMany(
            Category::class,
            'product_category',
            'product_id',
            'category_id'
        );
    }
}

// Utilisation
$product = Product::find(1);
$categories = $product->getCategories()->getResults();

// Ajouter une catégorie
$product->getCategories()->attach($categoryId);
```

---

## 🔧 Méthodes Disponibles sur les Relations

### Méthodes Communes (OneToMany, ManyToOne, OneToOne, ManyToMany)

```php
// Ajouter une contrainte WHERE
$relation->where('active', '=', 1);

// Ajouter un ORDER BY
$relation->orderBy('created_at', 'DESC');

// Limiter le nombre de résultats
$relation->limit(10);
```

### Méthodes Spécifiques

#### OneToMany
- `getResults()` : Retourne un tableau
- `count()` : Compte les éléments

#### ManyToOne / OneToOne
- `getResults()` : Retourne une instance ou null

#### ManyToMany
- `getResults()` : Retourne un tableau
- `attach($id, $pivotData = [])` : Attacher un élément
- `detach($id = null)` : Détacher un élément ou tous
- `count()` : Compte les éléments

---

## ⚠️ Bonnes Pratiques

### 1. Nommer les Méthodes avec `get`

```php
// ✅ Bon
public function getPosts(): OneToMany { ... }
public function getUser(): ManyToOne { ... }

// ❌ Éviter
public function posts(): OneToMany { ... }
```

### 2. Utiliser les Relations dans les Contrôleurs

```php
// Dans un contrôleur
public function show(int $id)
{
    $user = User::find($id);
    if (!$user) {
        return $this->redirect('/users');
    }

    $posts = $user->getPosts()
        ->orderBy('created_at', 'DESC')
        ->limit(10)
        ->getResults();

    return $this->render('user/show.html.php', [
        'user' => $user,
        'posts' => $posts
    ]);
}
```

### 3. Lazy Loading vs Eager Loading

**Lazy Loading** (par défaut) :
```php
$user = User::find(1);
$posts = $user->getPosts()->getResults(); // Requête exécutée ici
```

**Eager Loading** (à implémenter plus tard) :
```php
// À venir : charger les relations en une seule requête
$users = User::with('posts')->all();
```

---

## 🐛 Dépannage

### Erreur "Class not found"

**Problème** : La classe du modèle cible n'existe pas.

**Solution** : Vérifier que la classe est bien importée :

```php
use App\Model\Post;

public function getPosts(): OneToMany
{
    return $this->oneToMany(Post::class, 'user_id');
}
```

### Erreur "Table not found"

**Problème** : La table n'existe pas en base de données.

**Solution** : Créer la table avec une migration.

### Relation retourne null

**Problème** : La clé étrangère n'existe pas ou est null.

**Solution** : Vérifier que la clé étrangère est bien définie et a une valeur.

---

## 📚 Ressources

- [Documentation Symfony - Relations](https://symfony.com/doc/current/doctrine/associations.html)
- [Pattern Active Record](https://en.wikipedia.org/wiki/Active_record_pattern)

---

**Les relations ORM sont maintenant disponibles !** 🔗

