# 🐳 Docker - Démarrage Rapide avec Ogan Framework

> Guide pour démarrer rapidement les services de développement avec Docker

## 🚀 Démarrage Rapide

### 1. Démarrer les Services

```bash
docker-compose up -d
```

Cette commande démarre **par défaut** :
- ✅ **MySQL 8.0** (port 3306) - Base de données principale
- ✅ **phpMyAdmin** (port 8080) - Interface web pour MySQL
- ✅ **MailHog** (ports 1025 SMTP, 8025 Web) - Serveur SMTP de test pour les emails

**Services optionnels** (décommenter dans `docker-compose.yml` si nécessaire) :
- PostgreSQL 15 (port 5432)
- pgAdmin (port 5050) - Interface web pour PostgreSQL

### 2. Configurer votre `.env`

Créez un fichier `.env` à partir de `.env.example` :

```bash
cp .env.example .env
```

La configuration par défaut est déjà prête pour MySQL (Docker) :

```env
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ogan_framework
DB_USER=root
DB_PASS=root

# MailHog pour les emails de test
MAILER_DSN=smtp://127.0.0.1:1025
```

**C'est tout !** Vous pouvez maintenant utiliser la base de données.

### 3. Tester la Connexion

```php
use Ogan\Database\Database;

$pdo = Database::getConnection();
echo "✅ Connexion réussie !";
```

### 4. Tester MailHog

Ouvrez [http://localhost:8025](http://localhost:8025) pour voir l'interface MailHog.

Tous les emails envoyés par votre application seront capturés ici.

### 5. Arrêter les Services

```bash
docker-compose down
```

Pour supprimer aussi les volumes (données) :

```bash
docker-compose down -v
```

---

## 📊 Services Disponibles

### MySQL 8.0 (Par Défaut)

**Configuration :**
- **Host :** `127.0.0.1` ou `localhost`
- **Port :** `3306`
- **Database :** `ogan_framework` (créée automatiquement)
- **User root :** `root` / `root`
- **User ogan :** `ogan` / `ogan`

**Interface Web :** [http://localhost:8080](http://localhost:8080) (phpMyAdmin)

**Connexion phpMyAdmin :**
- Serveur : `mysql`
- Utilisateur : `root`
- Mot de passe : `root`

### MailHog (Par Défaut)

**Configuration SMTP :**
- **Host :** `127.0.0.1` ou `localhost`
- **Port SMTP :** `1025`
- **Interface Web :** [http://localhost:8025](http://localhost:8025)

**Utilisation :**
- Configurez `MAILER_DSN=smtp://127.0.0.1:1025` dans votre `.env`
- Tous les emails envoyés seront capturés par MailHog
- Consultez l'interface web pour voir les emails

**Avantages :**
- ✅ Pas besoin de serveur SMTP réel
- ✅ Parfait pour le développement
- ✅ Voir le contenu HTML des emails
- ✅ Tester les emails sans envoyer de vrais messages

### PostgreSQL 15 (Optionnel)

**Pour activer PostgreSQL :**
1. Décommentez le service `postgres` dans `docker-compose.yml`
2. Décommentez le service `pgadmin` si vous voulez l'interface web
3. Redémarrez : `docker-compose up -d`

**Configuration :**
- **Host :** `127.0.0.1` ou `localhost`
- **Port :** `5432`
- **Database :** `ogan_framework` (créée automatiquement)
- **User :** `ogan` / `ogan`

**Interface Web :** [http://localhost:5050](http://localhost:5050) (pgAdmin)

**Connexion pgAdmin :**
- Email : `admin@ogan.local`
- Mot de passe : `admin`

---

## 🔧 Configuration Complète

### Exemple `.env` par défaut (MySQL + MailHog)

```env
APP_ENV=dev
APP_DEBUG=true

# MySQL (Docker)
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ogan_framework
DB_USER=root
DB_PASS=root
DB_CHARSET=utf8mb4

# MailHog (Docker)
MAILER_DSN=smtp://127.0.0.1:1025
```

### Exemple `.env` pour PostgreSQL (Optionnel)

Si vous avez décommenté PostgreSQL dans `docker-compose.yml` :

```env
APP_ENV=dev
APP_DEBUG=true

DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=ogan_framework
DB_USER=ogan
DB_PASS=ogan

MAILER_DSN=smtp://127.0.0.1:1025
```

### Exemple `.env` pour SQLite (Pas besoin de Docker)

```env
APP_ENV=dev
APP_DEBUG=true

DB_DRIVER=sqlite
DB_NAME=myapp.db

MAILER_DSN=smtp://127.0.0.1:1025
```

### Exemple `.env.local` (Surcharge locale)

```env
# Surcharge pour votre environnement local
DB_USER=mon_user_local
DB_PASS=mon_pass_local
```

---

## 🛠️ Commandes Utiles

### Voir les logs

```bash
# Tous les services
docker-compose logs -f

# Un service spécifique
docker-compose logs -f mysql
docker-compose logs -f postgres
```

### Redémarrer un service

```bash
docker-compose restart mysql
docker-compose restart postgres
```

### Accéder au shell MySQL

```bash
docker-compose exec mysql mysql -u root -proot ogan_framework
```

### Accéder au shell PostgreSQL

```bash
docker-compose exec postgres psql -U ogan -d ogan_framework
```

### Voir les services en cours

```bash
docker-compose ps
```

### Arrêter tous les services

```bash
docker-compose stop
```

### Supprimer tout (conteneurs + volumes)

```bash
docker-compose down -v
```

---

## 📧 Utilisation de MailHog

### Configuration

Dans votre `.env` :

```env
MAILER_DSN=smtp://127.0.0.1:1025
```

### Envoyer un Email (Exemple)

```php
// Exemple avec PHPMailer ou SwiftMailer
$mailer = new PHPMailer();
$mailer->isSMTP();
$mailer->Host = '127.0.0.1';
$mailer->Port = 1025;
$mailer->SMTPAuth = false;

$mailer->setFrom('noreply@example.com', 'Ogan Framework');
$mailer->addAddress('test@example.com');
$mailer->Subject = 'Test Email';
$mailer->Body = 'Ceci est un email de test';

$mailer->send();
```

### Consulter les Emails

1. Ouvrez [http://localhost:8025](http://localhost:8025)
2. Tous les emails envoyés apparaissent dans la liste
3. Cliquez sur un email pour voir son contenu (HTML, texte, headers)

**Avantages :**
- ✅ Pas besoin de serveur SMTP réel
- ✅ Voir le contenu HTML des emails
- ✅ Tester les emails sans envoyer de vrais messages
- ✅ Parfait pour le développement

## 🎯 Utilisation avec l'ORM

Une fois Docker démarré et `.env` configuré, vous pouvez utiliser l'ORM normalement :

```php
use Ogan\Database\Database;
use Ogan\Database\QueryBuilder;

// Connexion automatique
$pdo = Database::getConnection();

// Query Builder
$users = QueryBuilder::table('users')
    ->select(['id', 'name', 'email'])
    ->where('active', '=', 1)
    ->get();

// Model
class User extends Model {
    protected static string $table = 'users';
}

$user = User::find(1);
```

---

## 🔍 Vérification

### Vérifier que MySQL fonctionne

```bash
docker-compose exec mysql mysql -u root -proot -e "SHOW DATABASES;"
```

### Vérifier que PostgreSQL fonctionne

```bash
docker-compose exec postgres psql -U ogan -d ogan_framework -c "\l"
```

### Tester depuis PHP

```php
use Ogan\Database\Database;

try {
    $pdo = Database::getConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "✅ Connexion réussie avec {$driver}";
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
```

---

## 📝 Créer une Base de Données

Les bases de données sont créées automatiquement au démarrage :

- **MySQL :** `ogan_framework` (créée automatiquement)
- **PostgreSQL :** `ogan_framework` (créée automatiquement)

Si vous voulez créer une autre base :

### MySQL

```bash
docker-compose exec mysql mysql -u root -proot -e "CREATE DATABASE ma_base;"
```

### PostgreSQL

```bash
docker-compose exec postgres psql -U ogan -c "CREATE DATABASE ma_base;"
```

---

## ⚠️ Dépannage

### Port déjà utilisé

Si le port 3306 ou 5432 est déjà utilisé, modifiez dans `docker-compose.yml` :

```yaml
ports:
  - "3307:3306"  # Utilisez 3307 au lieu de 3306
```

Puis dans `.env` :

```env
DB_PORT=3307
```

### Erreur de connexion

1. Vérifiez que Docker est démarré : `docker-compose ps`
2. Vérifiez les logs : `docker-compose logs mysql`
3. Vérifiez que le service est "healthy" : `docker-compose ps`

### Réinitialiser les données

```bash
# Arrêter et supprimer les volumes
docker-compose down -v

# Redémarrer
docker-compose up -d
```

---

## 🎓 Avantages

- ✅ **Rapide** : Démarrage en quelques secondes
- ✅ **Isolé** : N'affecte pas votre système
- ✅ **Reproductible** : Même environnement pour tous
- ✅ **Complet** : MySQL + PostgreSQL + Interfaces web
- ✅ **Simple** : Une seule commande pour tout démarrer

---

## 📚 Ressources

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [MySQL Docker Image](https://hub.docker.com/_/mysql)
- [PostgreSQL Docker Image](https://hub.docker.com/_/postgres)
- [phpMyAdmin Documentation](https://www.phpmyadmin.net/docs/)
- [pgAdmin Documentation](https://www.pgadmin.org/docs/)

---

**Docker est maintenant configuré ! Démarrez avec `docker-compose up -d`** 🚀

