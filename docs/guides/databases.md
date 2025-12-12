# 🗄️ Support des Bases de Données - Ogan Framework

> Guide complet sur le support multi-bases de données dans l'ORM Ogan

## ✅ Bases de Données Supportées

Le framework Ogan supporte **4 types de bases de données** via PDO :

1. **MySQL / MariaDB** ✅
2. **PostgreSQL** ✅
3. **SQLite** ✅
4. **SQL Server** ✅

---

## 🔧 Configuration

### Via `config/parameters.php`

```php
return [
    'database' => [
        'driver' => 'mysql',        // mysql, pgsql, sqlite, sqlsrv
        'host' => 'localhost',
        'port' => 3306,            // Optionnel (port par défaut selon le driver)
        'name' => 'myapp',
        'user' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',    // Uniquement pour MySQL/MariaDB
    ],
];
```

### Via `.env`

```env
# Type de base de données
DB_DRIVER=mysql

# Configuration MySQL/PostgreSQL/SQL Server
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4

# Pour SQLite, seul DB_NAME est nécessaire (chemin vers le fichier)
```

---

## 📊 Configuration par Type de Base

### 1. MySQL / MariaDB

**Driver :** `mysql` ou `mariadb`

**Configuration :**
```php
'database' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'myapp',
    'user' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4',
]
```

**Via .env :**
```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
```

**DSN généré :**
```
mysql:host=localhost;port=3306;dbname=myapp;charset=utf8mb4
```

**Prérequis :**
- Extension PHP : `pdo_mysql`
- Serveur MySQL ou MariaDB installé

---

### 2. PostgreSQL

**Driver :** `pgsql` ou `postgresql`

**Configuration :**
```php
'database' => [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'myapp',
    'user' => 'postgres',
    'password' => 'secret',
]
```

**Via .env :**
```env
DB_DRIVER=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=myapp
DB_USER=postgres
DB_PASS=secret
```

**DSN généré :**
```
pgsql:host=localhost;port=5432;dbname=myapp
```

**Prérequis :**
- Extension PHP : `pdo_pgsql`
- Serveur PostgreSQL installé

**Note :** Le charset n'est pas nécessaire pour PostgreSQL (utilise UTF-8 par défaut).

---

### 3. SQLite

**Driver :** `sqlite`

**Configuration :**
```php
'database' => [
    'driver' => 'sqlite',
    'name' => 'myapp.db',  // Nom du fichier (sera créé dans var/db/)
    // host, port, user, password ne sont pas nécessaires
]
```

**Via .env :**
```env
DB_DRIVER=sqlite
DB_NAME=myapp.db
```

**Chemin absolu :**
```php
'database' => [
    'driver' => 'sqlite',
    'name' => '/chemin/absolu/vers/myapp.db',
]
```

**DSN généré :**
```
sqlite:/chemin/vers/var/db/myapp.db
```

**Prérequis :**
- Extension PHP : `pdo_sqlite`
- Aucun serveur nécessaire (fichier local)

**Avantages :**
- ✅ Pas besoin de serveur
- ✅ Parfait pour le développement et les tests
- ✅ Fichier unique, facile à déplacer

**Structure :**
```
var/
└── db/
    └── myapp.db  (créé automatiquement)
```

---

### 4. SQL Server

**Driver :** `sqlsrv` ou `mssql`

**Configuration :**
```php
'database' => [
    'driver' => 'sqlsrv',
    'host' => 'localhost',
    'port' => 1433,
    'name' => 'myapp',
    'user' => 'sa',
    'password' => 'secret',
]
```

**Via .env :**
```env
DB_DRIVER=sqlsrv
DB_HOST=localhost
DB_PORT=1433
DB_NAME=myapp
DB_USER=sa
DB_PASS=secret
```

**DSN généré :**
```
sqlsrv:Server=localhost,1433;Database=myapp
```

**Prérequis :**
- Extension PHP : `pdo_sqlsrv` (Windows) ou `pdo_dblib` (Linux)
- Serveur SQL Server installé

**Note :** L'extension `pdo_sqlsrv` est spécifique à Windows. Sur Linux, utilisez `pdo_dblib` ou `pdo_odbc`.

---

## 🔄 Changer de Base de Données

### Exemple : Passer de MySQL à PostgreSQL

**Avant (MySQL) :**
```php
'database' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'myapp',
    'user' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4',
]
```

**Après (PostgreSQL) :**
```php
'database' => [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'myapp',
    'user' => 'postgres',
    'password' => 'secret',
    // charset n'est pas nécessaire
]
```

**C'est tout !** Le QueryBuilder génère du SQL standard qui fonctionne avec la plupart des bases de données.

---

## ⚠️ Différences de Syntaxe SQL

Bien que le QueryBuilder génère du SQL standard, certaines différences existent :

### LIMIT / OFFSET

**MySQL / SQLite :**
```sql
SELECT * FROM users LIMIT 10 OFFSET 20
```

**PostgreSQL :**
```sql
SELECT * FROM users LIMIT 10 OFFSET 20  -- ✅ Même syntaxe
```

**SQL Server :**
```sql
SELECT * FROM users OFFSET 20 ROWS FETCH NEXT 10 ROWS ONLY  -- ❌ Syntaxe différente
```

**Note :** Le QueryBuilder actuel génère `LIMIT/OFFSET` qui fonctionne pour MySQL, PostgreSQL et SQLite. Pour SQL Server, une adaptation serait nécessaire.

### Identifiants (Guillemets)

**MySQL :**
```sql
SELECT `id`, `name` FROM `users`
```

**PostgreSQL / SQL Server :**
```sql
SELECT "id", "name" FROM "users"
```

**SQLite :**
```sql
SELECT `id`, `name` FROM `users`  -- Accepte les backticks
```

**Note :** Le QueryBuilder utilise actuellement des backticks (MySQL). Pour PostgreSQL/SQL Server, il faudrait utiliser des guillemets doubles.

---

## 🧪 Tests avec Différentes Bases

### Test avec SQLite (Développement)

```php
// config/parameters.php
'database' => [
    'driver' => 'sqlite',
    'name' => 'test.db',
]

// Utilisation
$pdo = Database::getConnection();
// Fichier créé automatiquement dans var/db/test.db
```

### Test avec PostgreSQL

```php
// config/parameters.php
'database' => [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'myapp',
    'user' => 'postgres',
    'password' => 'secret',
]
```

---

## 📝 Exemples d'Utilisation

### Avec MySQL

```php
use Ogan\Database\Database;

$pdo = Database::getConnection();
$users = $pdo->query('SELECT * FROM users')->fetchAll();
```

### Avec SQLite

```php
use Ogan\Database\Database;

// Configuration dans parameters.php ou .env
// DB_DRIVER=sqlite
// DB_NAME=myapp.db

$pdo = Database::getConnection();
$users = $pdo->query('SELECT * FROM users')->fetchAll();
```

### Avec PostgreSQL

```php
use Ogan\Database\Database;

// Configuration dans parameters.php ou .env
// DB_DRIVER=pgsql
// DB_HOST=localhost
// DB_PORT=5432
// DB_NAME=myapp

$pdo = Database::getConnection();
$users = $pdo->query('SELECT * FROM users')->fetchAll();
```

---

## 🔍 Vérifier le Driver Actif

```php
use Ogan\Database\Database;

$pdo = Database::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "Driver actif : {$driver}";
// Affiche : mysql, pgsql, sqlite, ou sqlsrv
```

---

## 🚀 Recommandations

### Développement
- **SQLite** : Rapide, pas besoin de serveur, parfait pour les tests

### Production
- **MySQL/MariaDB** : Le plus utilisé, bien supporté
- **PostgreSQL** : Plus avancé, meilleur pour les données complexes

### Migration
- Le QueryBuilder génère du SQL standard
- La plupart des requêtes fonctionnent sans modification
- Seules quelques syntaxes spécifiques nécessitent des ajustements

---

## 📚 Ressources

- [PDO Documentation](https://www.php.net/manual/fr/book.pdo.php)
- [MySQL PDO](https://www.php.net/manual/fr/ref.pdo-mysql.php)
- [PostgreSQL PDO](https://www.php.net/manual/fr/ref.pdo-pgsql.php)
- [SQLite PDO](https://www.php.net/manual/fr/ref.pdo-sqlite.php)
- [SQL Server PDO](https://www.php.net/manual/fr/ref.pdo-sqlsrv.php)

---

**Le support multi-bases de données est maintenant actif !** ✅

