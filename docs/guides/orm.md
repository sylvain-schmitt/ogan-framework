# 🏗️ ORM & Modèles - Ogan Framework

Ce guide explique l'architecture des modèles, le pattern Active Record, et la gestion des relations.

## 📋 Table des matières

- [Structure d'un Modèle](#structure-dun-modèle)
- [Propriétés et Attributs (Active Record)](#propriétés-et-attributs-active-record)
- [Architecture & Bonnes Pratiques](#architecture--bonnes-pratiques)
- [Relations](#relations)
    - [OneToMany (Un-à-Plusieurs)](#onetomany-un-à-plusieurs)
    - [ManyToOne (Plusieurs-à-Un)](#manytoone-plusieurs-à-un)
    - [OneToOne (Un-à-Un)](#onetoone-un-à-un)
    - [ManyToMany (Plusieurs-à-Plusieurs)](#manytomany-plusieurs-à-plusieurs)

---

## Structure d'un Modèle

### Modèle Minimal

```php
// src/Model/User.php
namespace App\Model;

use Ogan\Database\Model;

class User extends Model
{
    // Le nom de la table est déduit : User → users
    // La clé primaire est 'id' par défaut
}
```

### Configuration Personnalisée

```php
class User extends Model
{
    // Nom de table spécifique
    protected static ?string $table = 'my_users';
    
    // Clé primaire spécifique
    protected static ?string $primaryKey = 'user_id';
}
```

---

## Propriétés et Attributs (Active Record)

Ogan utilise le pattern **Active Record**. Vous **n'avez pas besoin** de définir les propriétés de la classe pour chaque colonne de la base de données. ELles sont gérées dynamiquement via `__get` et `__set`.

**Exemple :**
```php
$user = User::find(1);

// Lecture (appelle __get)
echo $user->name; 

// Écriture (appelle __set)
$user->email = 'new@example.com'; 
$user->save();
```

> **Note** : Si vous définissez des getters explicites (ex: `getName()` ou `isPublished()`), ils seront utilisés prioritairement lors de l'accès via `$user->name`.

---

## Architecture & Bonnes Pratiques

### Où placer la logique ?

**✅ Dans le Modèle :**
*   **Méthodes méties** : Calculs, validation interne, manipulation de données.
    *   Ex: `setPassword($pwd)`, `getAge()`, `isAdmin()`.
*   **Requêtes spécifiques** : Scopes ou recherches fréquentes.
    *   Ex: `findByEmail($email)`, `findActive()`.

**❌ Dans le Contrôleur :**
*   **Ne jamais** mettre de logique métier complexe ici. Le contrôleur ne doit que coordonner (HTTP -> Modèle -> Vue).

**⚠️ Dans un Repository (Optionnel) :**
*   Si vous avez besoin de séparer strictement les requêtes complexes, vous pouvez utiliser un Repository, mais le pattern Active Record encourage de garder les requêtes simples dans le Modèle.

### Exemple de Modèle Riche

```php
class User extends Model
{
    // --- Métier ---
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password ?? '');
    }

    // --- Requêtes ---
    public static function findActive(): array
    {
        return self::where('active', '=', 1)->get();
    }
}
```

---

## Relations

Le framework supporte les relations de type Symfony/Doctrine.

### OneToMany (Un-à-Plusieurs)

**Exemple** : Un utilisateur a plusieurs articles.

```php
// User.php
public function getArticles(): \Ogan\Database\Relations\OneToMany
{
    // (Classe Cible, Clé Étrangère dans la cible)
    return $this->oneToMany(Article::class, 'user_id');
}
```

**Utilisation :**
```php
$user = User::find(1);
$articles = $user->getArticles()->getResults(); // array
$count = $user->getArticles()->count();
```

### ManyToOne (Plusieurs-à-Un)

**Exemple** : Un article appartient à un utilisateur.

```php
// Article.php
public function getAuthor(): \Ogan\Database\Relations\ManyToOne
{
    // (Classe Cible, Clé Étrangère locale)
    return $this->manyToOne(User::class, 'user_id');
}
```

**Utilisation :**
```php
$article = Article::find(1);
$author = $article->getAuthor()->getResults(); // User object
echo $author->name;
```

### OneToOne (Un-à-Un)

**Exemple** : Un utilisateur a un profil.

```php
// User.php
public function getProfile(): \Ogan\Database\Relations\OneToOne
{
    return $this->oneToOne(Profile::class, 'user_id');
}
```

**Utilisation :**
```php
$profile = $user->getProfile()->getResults();
```

### ManyToMany (Plusieurs-à-Plusieurs)

**Exemple** : Un utilisateur a plusieurs rôles.

```php
// User.php
public function getRoles(): \Ogan\Database\Relations\ManyToMany
{
    return $this->manyToMany(
        Role::class,      // Classe Cible
        'user_role',      // Table Pivot
        'user_id',        // Clé locale dans Pivot
        'role_id'         // Clé cible dans Pivot
    );
}
```

**Utilisation :**
```php
$roles = $user->getRoles()->getResults();

// Attacher/Détacher
$user->getRoles()->attach($roleId);
$user->getRoles()->detach($roleId);
```

### Lazy Loading vs Eager Loading

Par défaut, les relations sont **Lazy Loaded** (chargées à la demande).

```php
$user = User::find(1); // 1 requête
$posts = $user->getPosts()->getResults(); // 1 requête supplémentaire
```
