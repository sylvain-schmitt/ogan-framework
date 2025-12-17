# 💾 Système de Cache - Ogan Framework

Le framework Ogan inclut un système de cache complet pour améliorer les performances de votre application.

## 📋 Table des matières

- [Introduction](#introduction)
- [Utilisation basique](#utilisation-basique)
- [Drivers de cache](#drivers-de-cache)
- [Cache des requêtes](#cache-des-requêtes)
- [Cache des routes](#cache-des-routes)
- [Commandes CLI](#commandes-cli)
- [Configuration](#configuration)

---

## 🎯 Introduction

Le système de cache permet de stocker temporairement des données coûteuses à calculer ou récupérer, améliorant ainsi les performances de votre application.

### Avantages

- ✅ Réduction des requêtes base de données
- ✅ Amélioration des temps de réponse
- ✅ Réduction de la charge serveur
- ✅ Cache des routes compilées en production

---

## 🚀 Utilisation basique

### Fonctions helpers globales

```php
// Récupérer une valeur
$value = cache('my_key');

// Stocker une valeur (TTL par défaut: 3600s)
cache()->set('my_key', $data);

// Stocker avec TTL personnalisé (1 heure)
cache()->set('my_key', $data, 3600);

// Vérifier si une clé existe
if (cache()->has('my_key')) {
    // ...
}

// Supprimer une entrée
cache_forget('my_key');

// Vider tout le cache
cache_clear();
```

### Pattern "Remember" (le plus utile)

Récupère la valeur si elle existe, sinon exécute le callback et stocke le résultat :

```php
$users = cache_remember('all_users', 600, function() {
    return User::all();
});

// Équivalent à:
$users = cache()->remember('all_users', 600, fn() => User::all());
```

---

## 📦 Drivers de cache

### FileCache (par défaut)

Stockage sur le système de fichiers. Idéal pour la plupart des applications.

```php
// Configuration dans config/parameters.yaml
cache:
  default: file
  path: var/cache/data
  ttl: 3600
```

### ArrayCache

Stockage en mémoire. Idéal pour les tests ou le cache par requête.

```php
$cache = cache()->store('array');
```

---

## 🔍 Cache des requêtes

Le QueryBuilder supporte le cache natif :

```php
// Cache les résultats pendant 10 minutes
$products = Product::query()
    ->cache(600)
    ->where('active', true)
    ->get();

// La prochaine fois, les données viennent du cache
```

### Fonctionnement

1. Une clé unique est générée à partir de la requête SQL et des paramètres
2. Si les données sont en cache, elles sont retournées directement
3. Sinon, la requête est exécutée et le résultat est mis en cache

---

## 🛣️ Cache des routes

En production, les routes sont automatiquement compilées et mises en cache.

### Compilation manuelle

```bash
php bin/console cache:routes
```

### Auto-compilation

En mode `prod` (`APP_ENV=prod`), les routes sont automatiquement compilées au premier accès.

### Comportement par environnement

| Environnement | Comportement |
|---------------|--------------|
| `dev` | Routes découvertes par réflexion à chaque requête |
| `prod` | Routes chargées depuis le cache, auto-compilées si nécessaire |

---

## 🛠️ Commandes CLI

### cache:clear

Vide le cache de l'application.

```bash
# Vider tout le cache
php bin/console cache:clear

# Vider uniquement le cache des données
php bin/console cache:clear --type=data

# Vider uniquement le cache des routes
php bin/console cache:clear --type=routes
```

### cache:stats

Affiche les statistiques du cache.

```bash
php bin/console cache:stats
```

Exemple de sortie :

```
📊 Statistiques du cache : file
─────────────────────────────────────
   Chemin       : /path/to/var/cache/data
   Entrées      : 42
   Taille       : 1.2 MB
```

### cache:routes

Compile et met en cache les routes de l'application.

```bash
php bin/console cache:routes
```

### cache:gc

Nettoyage des entrées de cache expirées (Garbage Collection).

```bash
php bin/console cache:gc
```

---

## ⚙️ Configuration

Dans `config/parameters.yaml` :

```yaml
cache:
  # Driver par défaut
  default: file

  # Chemin de stockage (FileCache)
  path: var/cache/data

  # TTL par défaut (secondes)
  ttl: 3600

  # Configuration par store
  stores:
    file:
      path: var/cache/data
      gc_probability: 100

    array:
      max_size: 1000

  # Cache des requêtes DB
  query:
    enabled: true
    ttl: 600

  # Cache des routes
  routes:
    enabled: true
    file: var/cache/routes.php
```

---

## 💡 Bonnes pratiques

### 1. Utilisez `cache_remember`

C'est le pattern le plus courant et le plus sûr :

```php
$data = cache_remember('key', 3600, fn() => expensiveOperation());
```

### 2. Clés de cache descriptives

```php
// ✅ Bon
cache_remember("user_{$userId}_posts", 600, ...);

// ❌ Mauvais
cache_remember("data", 600, ...);
```

### 3. Invalidation appropriée

```php
// Après modification d'un utilisateur
$user->save();
cache_forget("user_{$user->id}");
cache_forget("all_users");
```

### 4. Compilez les routes en production

```bash
# Dans votre script de déploiement
php bin/console cache:routes
```

---

## 📚 Ressources

- [Documentation des requêtes](./databases.md)
- [Documentation CLI](./code-generation.md)
