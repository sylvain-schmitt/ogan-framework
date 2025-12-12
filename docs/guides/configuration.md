# ⚙️ Configuration - Ogan Framework

> Guide complet sur la configuration du framework

## 📊 Hiérarchie de Priorité

La configuration suit cette hiérarchie (du plus prioritaire au moins prioritaire) :

1. **Variables d'environnement (`.env`)** → **PRIORITÉ MAXIMALE** ⭐
2. Fichier PHP (`config/parameters.php`)
3. Valeurs par défaut dans le code

**Exemple :**
```php
// Si .env contient : DB_HOST=production.db
// Et parameters.php contient : 'database' => ['host' => 'localhost']
// Alors Config::get('database.host') retournera 'production.db' (depuis .env)
```

---

## 🔧 Configuration via `.env` (Recommandé)

### Avantages

- ✅ **Séparé du code** : Pas besoin de modifier `parameters.php`
- ✅ **Par environnement** : Un `.env` différent pour dev/prod
- ✅ **Sécurisé** : Déjà dans `.gitignore`, ne sera pas commité
- ✅ **Simple** : Format clé=valeur

### Format

Créez un fichier `.env` à la racine du projet :

```env
# Configuration d'environnement
APP_ENV=dev
APP_DEBUG=true

# Base de données
# Driver supportés : mysql, pgsql, sqlite, sqlsrv
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4

# Router
ROUTER_BASE_PATH=
```

### Variables Disponibles

| Variable .env | Accès dans le code | Description |
|--------------|-------------------|-------------|
| `APP_ENV` | `Config::get('app.env')` | Environnement (dev, prod) |
| `APP_DEBUG` | `Config::get('app.debug')` | Mode debug (true, false) |
| `DB_DRIVER` | `Config::get('database.driver')` | Type de base (mysql, pgsql, sqlite, sqlsrv) |
| `DB_HOST` | `Config::get('database.host')` | Hôte de la base de données |
| `DB_PORT` | `Config::get('database.port')` | Port de la base de données |
| `DB_NAME` | `Config::get('database.name')` | Nom de la base de données |
| `DB_USER` | `Config::get('database.user')` | Utilisateur de la base |
| `DB_PASS` | `Config::get('database.password')` | Mot de passe |
| `DB_CHARSET` | `Config::get('database.charset')` | Charset (MySQL uniquement) |
| `ROUTER_BASE_PATH` | `Config::get('router.base_path')` | Préfixe des routes |

### Convention de Nommage

Les variables d'environnement sont automatiquement converties :

- `APP_*` → `app.*`
- `DB_*` → `database.*`
- `ROUTER_*` → `router.*`
- Les underscores `_` deviennent des points `.`

---

## 📝 Configuration via `config/parameters.php`

### Quand l'utiliser ?

- ✅ Valeurs par défaut pour tous les environnements
- ✅ Configuration complexe (tableaux, objets)
- ✅ Configuration qui doit être versionnée

### Format

```php
<?php

return [
    // Application
    'app' => [
        'env' => 'dev',
        'debug' => true,
    ],

    // Base de données
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'myapp',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    // Router
    'router' => [
        'base_path' => '',
    ],

    // Vues
    'view' => [
        'templates_path' => __DIR__ . '/../templates',
        'default_layout' => 'layouts/base.html.php',
        'default_title' => 'Mon site',
    ],
];
```

---

## 🎯 Exemples de Configuration

### Développement (`.env`)

```env
APP_ENV=dev
APP_DEBUG=true

DB_DRIVER=sqlite
DB_NAME=dev.db
```

**Note :** Pour SQLite, seul `DB_NAME` est nécessaire.

### Production (`.env`)

```env
APP_ENV=prod
APP_DEBUG=false

DB_DRIVER=mysql
DB_HOST=production.db.example.com
DB_PORT=3306
DB_NAME=myapp_prod
DB_USER=prod_user
DB_PASS=super_secret_password
DB_CHARSET=utf8mb4
```

### Test (`.env`)

```env
APP_ENV=test
APP_DEBUG=true

DB_DRIVER=sqlite
DB_NAME=test.db
```

---

## 🔄 Changer de Base de Données

### Exemple : Passer de MySQL à PostgreSQL

**Avant (`.env`) :**
```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
```

**Après (`.env`) :**
```env
DB_DRIVER=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=myapp
DB_USER=postgres
DB_PASS=secret
# DB_CHARSET n'est pas nécessaire pour PostgreSQL
```

**C'est tout !** Le framework détecte automatiquement le changement.

---

## ✅ Configuration Minimale

### Pour MySQL/MariaDB

**Minimum requis dans `.env` :**
```env
DB_DRIVER=mysql
DB_NAME=myapp
```

Les autres valeurs utilisent les défauts :
- `DB_HOST` → `localhost`
- `DB_PORT` → `3306`
- `DB_USER` → `root`
- `DB_PASS` → `''`
- `DB_CHARSET` → `utf8mb4`

### Pour SQLite

**Minimum requis dans `.env` :**
```env
DB_DRIVER=sqlite
DB_NAME=myapp.db
```

Le fichier sera créé automatiquement dans `var/db/myapp.db`.

---

## 🔍 Vérifier la Configuration

### Dans le Code

```php
use Ogan\Config\Config;

// Vérifier le driver
$driver = Config::get('database.driver', 'mysql');
echo "Driver : {$driver}";

// Vérifier la configuration complète
$dbConfig = Config::get('database');
var_dump($dbConfig);
```

### Test de Connexion

```php
use Ogan\Database\Database;

try {
    $pdo = Database::getConnection();
    echo "✅ Connexion réussie !";
    echo "Driver : " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
```

---

## ⚠️ Sécurité

### Ne JAMAIS commiter `.env`

Le fichier `.env` est déjà dans `.gitignore` :

```gitignore
.env
.env.local
.env.*.local
```

### Créer un `.env.example`

Créez un fichier `.env.example` avec des valeurs d'exemple (sans secrets) :

```env
# .env.example
APP_ENV=dev
APP_DEBUG=true

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

Les développeurs peuvent copier ce fichier :
```bash
cp .env.example .env
```

---

## 📚 Résumé

### Configuration Recommandée

**Pour la plupart des cas :** Utilisez uniquement `.env` ✅

```env
# .env - Configuration complète
APP_ENV=dev
APP_DEBUG=true
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
```

**Pour les valeurs par défaut communes :** Utilisez `parameters.php` comme fallback

```php
// config/parameters.php - Valeurs par défaut
return [
    'view' => [
        'templates_path' => __DIR__ . '/../templates',
        'default_layout' => 'layouts/base.html.php',
    ],
];
```

### Avantages de `.env` uniquement

- ✅ **Simple** : Un seul fichier à modifier
- ✅ **Sécurisé** : Pas commité dans Git
- ✅ **Flexible** : Différent par environnement
- ✅ **Standard** : Convention utilisée par Laravel, Symfony, etc.

---

**Conclusion : Oui, vous pouvez configurer uniquement via `.env` !** ✅

Le fichier `parameters.php` sert de fallback pour les valeurs par défaut, mais n'est pas obligatoire si tout est dans `.env`.

