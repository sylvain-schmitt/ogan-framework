# 🔄 Système de Migrations Versionnées

Le framework Ogan inclut un système de migrations versionnées pour gérer l'évolution de votre schéma de base de données de manière structurée et réversible.

## 📋 Table des matières

- [Introduction](#introduction)
- [Créer une migration](#créer-une-migration)
- [Exécuter les migrations](#exécuter-les-migrations)
- [Annuler les migrations](#annuler-les-migrations)
- [Voir le statut](#voir-le-statut)
- [Structure d'une migration](#structure-dune-migration)
- [Support multi-base de données](#support-multi-base-de-données)

---

## 🎯 Introduction

### Qu'est-ce qu'une migration ?

Une **migration** est un fichier PHP qui décrit une modification à apporter à votre schéma de base de données. Chaque migration contient deux méthodes :

- **`up()`** : Applique la modification (créer une table, ajouter une colonne, etc.)
- **`down()`** : Annule la modification (supprimer la table, retirer la colonne, etc.)

### Avantages

✅ **Versionnement** : Chaque modification est tracée et versionnée  
✅ **Réversibilité** : Possibilité d'annuler les modifications  
✅ **Collaboration** : Facilite le travail en équipe  
✅ **Déploiement** : Automatisation des mises à jour de schéma  

---

## 📝 Créer une migration

### Option 1 : Génération automatique pour tous les modèles (Recommandé - Style Symfony)

Le framework peut scanner automatiquement tous vos modèles et générer les migrations manquantes, exactement comme Symfony/Doctrine :

```bash
php bin/migrate make
# ou
php bin/migrate diff
```

**Comment ça fonctionne :**
1. ✅ Scanne automatiquement le dossier `src/Model/`
2. ✅ Détecte quels modèles ont déjà une migration
3. ✅ Génère uniquement les migrations manquantes
4. ✅ Ignore les modèles qui ont déjà une migration

**Exemple :**

```bash
# Si vous avez User (avec migration) et Post (sans migration)
php bin/migrate make

# Résultat :
# 🔍 Scan des modèles...
#    Modèles trouvés : 2
#    Migrations existantes : 1
#    Migrations à générer : 1
# 
# 🔧 Génération de la migration pour : App\Model\Post
#    ✅ Migration créée : 2024_01_15_143000_create_post_table.php
```

### Option 2 : Génération pour un modèle spécifique

Vous pouvez aussi générer une migration pour un modèle spécifique. Deux syntaxes sont possibles :

**Syntaxe simple (recommandée) :**
```bash
php bin/migrate make User
php bin/migrate make Post
```

**Syntaxe complète (avec namespace) :**
```bash
php bin/migrate make App\Model\User
php bin/migrate make App\Model\Post
```

Le framework cherche automatiquement le modèle dans `src/Model/` si vous utilisez la syntaxe simple.

Cette commande :
- ✅ Analyse les propriétés privées du modèle
- ✅ Détecte les types (int, string, DateTime, etc.)
- ✅ Génère le SQL pour MySQL, PostgreSQL et SQLite
- ✅ Crée les index et contraintes appropriés
- ✅ Génère les méthodes `up()` et `down()`

### Option 2 : Création manuelle

Si vous préférez créer manuellement une migration, suivez le format suivant :

```
YYYY_MM_DD_HHMMSS_description.php
```

**Exemple :**
```
2024_01_15_143000_create_posts_table.php
2024_01_20_100000_add_status_to_users.php
```

### Structure d'une migration

```php
<?php

namespace App\Database\Migration;

use Ogan\Database\Migration\AbstractMigration;

class CreatePostsTable extends AbstractMigration
{
    public function up(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match (strtolower($driver)) {
            'mysql', 'mariadb' => "
                CREATE TABLE IF NOT EXISTS post (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES user(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE IF NOT EXISTS post (
                    id SERIAL PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES user(id)
                )
            ",
            default => throw new \RuntimeException("Driver non supporté: {$driver}")
        };

        $this->execute($sql);
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS post");
    }
}
```

### Points importants

1. **Namespace** : Utilisez `App\Database\Migration`
2. **Nom de classe** : Convertit automatiquement depuis le nom de fichier
   - `2024_01_15_143000_create_posts_table.php` → `CreatePostsTable`
3. **Méthode `up()`** : Code pour appliquer la migration
4. **Méthode `down()`** : Code pour annuler la migration

---

## 🚀 Exécuter les migrations

### Commande de base

```bash
php bin/migrate
# ou
php bin/migrate migrate
```

Cette commande exécute **toutes les migrations en attente** dans l'ordre chronologique.

### Exemple de sortie

```
🔄 Exécution des migrations en attente...

🔄 Exécution de la migration : 2024_01_15_143000_create_posts_table.php
✅ Migration 2024_01_15_143000_create_posts_table.php exécutée avec succès

✅ Toutes les migrations ont été exécutées (batch #1)
```

---

## ⏪ Annuler les migrations

### Annuler la dernière migration

```bash
php bin/migrate rollback
```

### Annuler plusieurs migrations

```bash
php bin/migrate rollback --steps=3
```

Cette commande annule les 3 dernières migrations.

### Exemple de sortie

```
🔄 Annulation de 1 migration(s)...

🔄 Annulation de la migration : 2024_01_15_143000_create_posts_table.php
✅ Migration 2024_01_15_143000_create_posts_table.php annulée avec succès

✅ Rollback terminé
```

---

## 📊 Voir le statut

### Commande

```bash
php bin/migrate status
```

### Exemple de sortie

```
📊 Statut des migrations

Total : 2
Exécutées : 1
En attente : 1

Détails :
────────────────────────────────────────────────────────────────────────────────
Migration                                          Statut          Batch
────────────────────────────────────────────────────────────────────────────────
2024_01_01_000000_create_user_table.php           ✅ Exécutée      #1
2024_01_15_143000_create_posts_table.php          ⏳ En attente    -
```

---

## 🏗️ Structure d'une migration

### Méthodes disponibles

#### `up(): void`
Applique la migration. Exécutée lors de `php bin/migrate`.

#### `down(): void`
Annule la migration. Exécutée lors de `php bin/migrate rollback`.

#### `execute(string $sql): void`
Exécute une requête SQL simple.

```php
$this->execute("CREATE TABLE example (id INT PRIMARY KEY)");
```

#### `executeMultiple(string $sql): void`
Exécute plusieurs requêtes SQL séparées par des points-virgules.

```php
$sql = "
    CREATE TABLE table1 (...);
    CREATE TABLE table2 (...);
    CREATE INDEX idx_name ON table1(name);
";
$this->executeMultiple($sql);
```

### Accès à la connexion PDO

Vous pouvez accéder directement à la connexion PDO via `$this->pdo` :

```php
public function up(): void
{
    $stmt = $this->pdo->prepare("INSERT INTO config (key, value) VALUES (?, ?)");
    $stmt->execute(['app_name', 'Ogan Framework']);
}
```

---

## 🗄️ Support multi-base de données

Le système de migrations supporte plusieurs bases de données :

- **MySQL / MariaDB** : `mysql`, `mariadb`
- **PostgreSQL** : `pgsql`, `postgresql`
- **SQLite** : `sqlite`
- **SQL Server** : `sqlsrv`, `mssql`

### Exemple avec détection du driver

```php
public function up(): void
{
    $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

    $sql = match (strtolower($driver)) {
        'mysql', 'mariadb' => "
            CREATE TABLE example (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100)
            ) ENGINE=InnoDB
        ",
        'pgsql', 'postgresql' => "
            CREATE TABLE example (
                id SERIAL PRIMARY KEY,
                name VARCHAR(100)
            )
        ",
        'sqlite' => "
            CREATE TABLE example (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100)
            )
        ",
        default => throw new \RuntimeException("Driver non supporté: {$driver}")
    };

    $this->execute($sql);
}
```

---

## 📁 Organisation des fichiers

```
database/
└── migrations/
    ├── 2024_01_01_000000_create_user_table.php
    ├── 2024_01_15_143000_create_posts_table.php
    └── 2024_02_01_120000_add_status_to_users.php
```

---

## 🔍 Table de suivi

Le système crée automatiquement une table `migrations` pour suivre les migrations exécutées :

```sql
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Champs :**
- `migration` : Nom du fichier de migration
- `batch` : Numéro du batch d'exécution
- `executed_at` : Date et heure d'exécution

---

## 💡 Bonnes pratiques

### 1. Nommage descriptif

✅ **Bon :**
```
2024_01_15_143000_create_posts_table.php
2024_01_20_100000_add_status_to_users.php
```

❌ **Mauvais :**
```
2024_01_15_migration.php
2024_01_20_update.php
```

### 2. Une migration = Une modification

✅ **Bon :** Une migration pour créer la table `posts`, une autre pour ajouter la colonne `status`.

❌ **Mauvais :** Tout dans une seule migration.

### 3. Toujours implémenter `down()`

Assurez-vous que votre méthode `down()` annule correctement les modifications de `up()`.

### 4. Tester les migrations

Testez toujours vos migrations sur un environnement de développement avant de les appliquer en production.

---

## 🐛 Dépannage

### Erreur : "Impossible de charger la classe de migration"

**Cause :** Le namespace ou le nom de classe ne correspond pas.

**Solution :** Vérifiez que :
1. Le namespace est `App\Database\Migration`
2. Le nom de classe correspond au format attendu (PascalCase)

### Erreur : "Driver de base de données non supporté"

**Cause :** Le driver n'est pas reconnu.

**Solution :** Vérifiez la configuration dans `.env` :
```env
DB_DRIVER=mysql  # ou pgsql, sqlite, sqlsrv
```

### Migration déjà exécutée

Si une migration a déjà été exécutée, elle ne sera pas réexécutée automatiquement. Pour la réexécuter :

1. Supprimez l'enregistrement de la table `migrations`
2. Ou annulez-la avec `rollback` puis réexécutez-la

---

## 📚 Exemples complets

### Créer une table avec index

```php
public function up(): void
{
    $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

    $sql = match (strtolower($driver)) {
        'mysql', 'mariadb' => "
            CREATE TABLE IF NOT EXISTS post (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                content TEXT,
                published BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_published (published)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        default => throw new \RuntimeException("Driver non supporté")
    };

    $this->execute($sql);
}

public function down(): void
{
    $this->execute("DROP TABLE IF EXISTS post");
}
```

### Ajouter une colonne

```php
public function up(): void
{
    $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

    $sql = match (strtolower($driver)) {
        'mysql', 'mariadb' => "ALTER TABLE user ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
        'pgsql', 'postgresql' => "ALTER TABLE user ADD COLUMN status VARCHAR(20) DEFAULT 'active'",
        default => throw new \RuntimeException("Driver non supporté")
    };

    $this->execute($sql);
}

public function down(): void
{
    $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

    $sql = match (strtolower($driver)) {
        'mysql', 'mariadb' => "ALTER TABLE user DROP COLUMN status",
        'pgsql', 'postgresql' => "ALTER TABLE user DROP COLUMN status",
        default => throw new \RuntimeException("Driver non supporté")
    };

    $this->execute($sql);
}
```

---

## 🎓 Concepts pédagogiques

### Pattern Template Method

La classe `AbstractMigration` utilise le **pattern Template Method** :
- Définit la structure (méthodes `up()` et `down()`)
- Laisse les classes filles implémenter les détails

### Transactions

Les migrations sont exécutées dans des **transactions** :
- Si une migration échoue, toutes les modifications sont annulées
- Garantit la cohérence de la base de données

### Versioning

Le système de **versioning** permet de :
- Suivre l'historique des modifications
- Appliquer les migrations dans l'ordre chronologique
- Gérer les rollbacks de manière sécurisée

---

## 📖 Ressources

- [Documentation des bases de données](./databases.md)
- [Guide de configuration](./configuration.md)
- [Architecture du framework](../architecture/)

