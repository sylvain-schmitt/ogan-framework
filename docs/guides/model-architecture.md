# 🏗️ Architecture des Modèles - Ogan Framework

> Guide sur l'architecture des modèles et où placer les différentes méthodes

## 📋 Vue d'Ensemble

Ce guide explique :
- Comment structurer vos modèles
- Où placer les méthodes métier vs méthodes de requête
- Comment fonctionnent les propriétés et attributs
- Bonnes pratiques d'architecture

---

## 🎯 Structure d'un Modèle

### Modèle Minimal

```php
// src/Model/User.php
namespace App\Model;

use Ogan\Database\Model;

class User extends Model
{
    // Le nom de la table est automatiquement déduit : User → users
    // Pas besoin de définir $table sauf si vous voulez un nom personnalisé
}
```

**C'est tout !** Le framework déduit automatiquement :
- **Nom de la table** : `User` → `users`, `PostCategory` → `post_categories`
- **Clé primaire** : `id` par défaut

### Modèle avec Nom de Table Personnalisé

```php
class User extends Model
{
    // Si vous voulez un nom différent
    protected static ?string $table = 'my_users';
}
```

---

## 🔧 Propriétés et Attributs

### Comment ça Fonctionne

Ogan Framework utilise le pattern **Active Record** avec des **magic methods** (`__get()`, `__set()`).

**Vous n'avez PAS besoin de définir les propriétés** :

```php
// ❌ PAS NÉCESSAIRE
class User extends Model
{
    public int $id;
    public string $name;
    public string $email;
    // ...
}
```

**Les attributs sont gérés automatiquement** :

```php
// ✅ ÇA FONCTIONNE DIRECTEMENT
$user = User::find(1);
echo $user->name;        // Magic __get()
$user->email = 'new@example.com';  // Magic __set()
$user->save();
```

### Comment ça Marche en Interne

```php
// Quand vous faites :
$user->name = 'Ogan';

// Le framework appelle automatiquement :
$user->__set('name', 'Ogan');
// Qui stocke dans :
$user->attributes['name'] = 'Ogan';
```

### Avantages

- ✅ Pas besoin de définir toutes les colonnes
- ✅ Flexible : nouvelles colonnes = pas de modification du code
- ✅ Simple et rapide à utiliser

### Inconvénients

- ❌ Pas de typage strict (PHP 8.1+ peut aider avec des propriétés)
- ❌ Pas d'autocomplétion IDE (mais on peut ajouter des PHPDoc)

---

## 📍 Où Placer les Méthodes ?

### ✅ Dans le Modèle (Recommandé)

#### 1. Méthodes Métier (Logique Business)

**Ces méthodes doivent rester dans le Model** car elles sont spécifiques à l'entité :

```php
// src/Model/User.php
class User extends Model
{
    /**
     * Hasher le mot de passe avant sauvegarde
     * ✅ MÉTHODE MÉTIER : Logique spécifique à User
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifier un mot de passe
     * ✅ MÉTHODE MÉTIER : Logique spécifique à User
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password ?? '');
    }

    /**
     * Calculer l'âge de l'utilisateur
     * ✅ MÉTHODE MÉTIER : Logique spécifique à User
     */
    public function getAge(): ?int
    {
        if (!$this->birthdate) {
            return null;
        }
        return (new \DateTime())->diff(new \DateTime($this->birthdate))->y;
    }
}
```

#### 2. Méthodes de Requête Spécifiques

**Ces méthodes peuvent rester dans le Model** si elles sont spécifiques à l'entité :

```php
// src/Model/User.php
class User extends Model
{
    /**
     * Trouver un utilisateur par email
     * ✅ MÉTHODE DE REQUÊTE : Spécifique à User
     */
    public static function findByEmail(string $email): ?self
    {
        $result = self::where('email', '=', $email)->first();
        
        if ($result === null) {
            return null;
        }
        
        $user = new static($result);
        $user->exists = true;
        return $user;
    }

    /**
     * Trouver les utilisateurs actifs
     * ✅ MÉTHODE DE REQUÊTE : Spécifique à User
     */
    public static function findActive(): array
    {
        return self::where('active', '=', 1)->get();
    }
}
```

---

### ⚠️ Dans un Repository (Optionnel)

Si vous avez **beaucoup de méthodes de requête complexes**, vous pouvez créer un Repository :

```php
// src/Repository/UserRepository.php
namespace App\Repository;

use App\Model\User;
use Ogan\Database\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected string $table = 'users';
    protected string $entityClass = User::class;

    /**
     * Trouver un utilisateur par email avec ses posts
     */
    public function findByEmailWithPosts(string $email): ?User
    {
        $user = $this->findOneBy(['email' => $email]);
        if ($user) {
            // Charger les posts (eager loading)
            // ...
        }
        return $user;
    }
}
```

**Quand utiliser un Repository ?**
- ✅ Beaucoup de requêtes complexes
- ✅ Besoin de séparer la logique de requête de la logique métier
- ✅ Pattern Data Mapper (plus avancé)

**Quand rester dans le Model ?**
- ✅ Requêtes simples
- ✅ Pattern Active Record (plus simple)
- ✅ Petites applications

---

### ❌ Dans le Contrôleur (À Éviter)

**Ne mettez PAS la logique métier dans le contrôleur** :

```php
// ❌ MAUVAIS
class UserController extends AbstractController
{
    public function create()
    {
        $user = new User();
        $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT); // ❌
        $user->save();
    }
}

// ✅ BON
class UserController extends AbstractController
{
    public function create()
    {
        $user = new User();
        $user->setPassword($_POST['password']); // ✅ Méthode dans le Model
        $user->save();
    }
}
```

---

## 🎨 Exemple Complet

### Modèle User Complet

```php
// src/Model/User.php
namespace App\Model;

use Ogan\Database\Model;

class User extends Model
{
    // Pas besoin de définir $table : User → users automatiquement
    // Pas besoin de définir les propriétés : gérées par __get()/__set()

    // ─────────────────────────────────────────────────────────────
    // MÉTHODES MÉTIER
    // ─────────────────────────────────────────────────────────────

    /**
     * Hasher le mot de passe
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifier un mot de passe
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password ?? '');
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTHODES DE REQUÊTE
    // ─────────────────────────────────────────────────────────────

    /**
     * Trouver par email
     */
    public static function findByEmail(string $email): ?self
    {
        $result = self::where('email', '=', $email)->first();
        
        if ($result === null) {
            return null;
        }
        
        $user = new static($result);
        $user->exists = true;
        return $user;
    }

    /**
     * Trouver les utilisateurs actifs
     */
    public static function findActive(): array
    {
        return self::where('active', '=', 1)->get();
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────────────────────

    /**
     * Relation OneToMany : Un utilisateur a plusieurs posts
     */
    public function getPosts(): \Ogan\Database\Relations\OneToMany
    {
        return $this->oneToMany(Post::class, 'user_id');
    }
}
```

---

## 📝 Règles de Décision

### Méthode Métier → Dans le Model

- ✅ Logique spécifique à l'entité
- ✅ Manipulation des attributs
- ✅ Calculs basés sur les données de l'entité
- ✅ Validation métier

**Exemples** : `setPassword()`, `verifyPassword()`, `getAge()`, `isAdmin()`, `calculateTotal()`

### Méthode de Requête → Dans le Model (ou Repository)

- ✅ Requêtes spécifiques à l'entité
- ✅ Filtres complexes
- ✅ Recherches personnalisées

**Exemples** : `findByEmail()`, `findActive()`, `findByRole()`

### Logique de Contrôle → Dans le Contrôleur

- ✅ Gestion des requêtes HTTP
- ✅ Validation des formulaires
- ✅ Redirections
- ✅ Rendu des vues

**Exemples** : `login()`, `register()`, `update()`, `delete()`

---

## 🔍 Détection Automatique du Nom de Table

### Règles de Conversion

Le framework convertit automatiquement le nom de la classe en nom de table :

| Classe | Table |
|--------|-------|
| `User` | `users` |
| `Post` | `posts` |
| `PostCategory` | `post_categories` |
| `UserProfile` | `user_profiles` |

### Règles de Pluriel

- Ajoute `s` : `User` → `users`
- Ajoute `es` : `Box` → `boxes`
- `y` → `ies` : `Category` → `categories`

### Personnaliser le Nom

```php
class User extends Model
{
    // Si vous voulez un nom différent
    protected static ?string $table = 'my_users';
}
```

---

## ✅ Checklist

- [ ] Le modèle étend `Model`
- [ ] Pas besoin de définir `$table` sauf si personnalisé
- [ ] Pas besoin de définir les propriétés (gérées par `__get()`/`__set()`)
- [ ] Méthodes métier dans le Model
- [ ] Méthodes de requête simples dans le Model
- [ ] Logique de contrôle dans le Contrôleur
- [ ] Relations définies dans le Model

---

**L'architecture des modèles est maintenant claire !** 🏗️

