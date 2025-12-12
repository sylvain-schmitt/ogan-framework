# Ogan Framework - Mini Framework PHP Pédagogique

> _En mémoire d'Ogan 🐕💙 - Un framework créé avec passion pour apprendre et comprendre_

## 🎯 Objectif

Ce projet est un framework PHP pédagogique qui démontre :
- Architecture MVC propre
- Injection de dépendances (DI)
- Routing avec attributs PHP 8+
- Autoloader PSR-4 maison
- Séparation framework / application

## 📁 Structure du Projet

```
Mini-Fw/
├── ogan/              # Code du framework (réutilisable) 🔧
│   ├── DependencyInjection/
│   ├── Http/
│   ├── Router/
│   └── View/
├── src/                # Code de l'application (spécifique) 🎨
│   └── Controller/
├── templates/          # Vues (layouts, partials, pages) 📄
├── public/             # Point d'entrée web 🌐
│   └── index.php
├── config/             # Configuration ⚙️
├── GUIDE_PEDAGOGIQUE.md # 📚 Guide détaillé de chaque fichier
└── autoload.php        # Autoloader PSR-4 🔌
```

## 🚀 Démarrage Rapide

### 1. Cloner le Projet

```bash
git clone <votre-repo>
cd Mini-Fw
composer install
```

### 2. Configuration

```bash
# Copier le fichier d'exemple
cp .env.example .env

# (Optionnel) Pour votre environnement local
cp .env.local.example .env.local
```

### 3. Base de Données (Optionnel - avec Docker)

```bash
# Démarrer MySQL et PostgreSQL
docker-compose up -d

# Configurer .env pour MySQL (Docker)
# DB_DRIVER=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_NAME=ogan_framework
# DB_USER=root
# DB_PASS=root
```

**Ou utiliser SQLite (pas besoin de Docker) :**
```env
DB_DRIVER=sqlite
DB_NAME=myapp.db
```

### 4. Lancer le Serveur

```bash
php -S localhost:8000 -t public
```

### 5. Ouvrir dans le Navigateur

- Home : [http://localhost:8000/](http://localhost:8000/)
- phpMyAdmin (MySQL) : [http://localhost:8080](http://localhost:8080)
- pgAdmin (PostgreSQL) : [http://localhost:5050](http://localhost:5050)

## 🎓 Concepts Clés

### 1. **Routing avec Attributs PHP 8+**

```php
use Ogan\Router\Attributes\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', methods: ['GET'], name: 'home')]
    public function index()
    {
        $this->render('home/index.html.php', [
            'title' => 'Accueil'
        ]);
    }
}
```

### 2. **Container d'Injection de Dépendances**

Le container résout automatiquement les dépendances :

```php
$container = new Container();
$controller = $container->get(HomeController::class);
// Le container injecte automatiquement Request, Response, etc.
```

### 3. **Système de Vues avec Layouts**

```php
// Layout : templates/layouts/base.html.php
<?php $this->section('body'); ?>

// Page : templates/home/index.html.php
<h1>Ma page</h1>
```

### 4. **Deux Namespaces**

- `Ogan\` → Code du framework (dans `ogan/`)
- `App\` → Code de votre application (dans `src/`)

## 🐳 Docker (Développement Rapide)

Démarrez rapidement les services de développement avec Docker :

```bash
docker-compose up -d
```

**Services par défaut :**
- MySQL 8.0 → Port 3306 (Base de données)
- phpMyAdmin → [http://localhost:8080](http://localhost:8080) (Interface MySQL)
- MailHog → [http://localhost:8025](http://localhost:8025) (Test d'emails)

**Services optionnels** (décommenter dans `docker-compose.yml`) :
- PostgreSQL 15 → Port 5432
- pgAdmin → [http://localhost:5050](http://localhost:5050)

Voir le [Guide Docker](docs/guides/docker.md) pour plus de détails.

## 🛠️ Commandes CLI

Le framework inclut des outils CLI pour générer du code rapidement :

### Génération de Code

```bash
# Générer un contrôleur complet avec CRUD
php bin/make controller User

# Générer un FormType
php bin/make form User

# Générer un modèle
php bin/make model User

# Générer tout en une commande (modèle + FormType + contrôleur)
php bin/make all Post
```

### Migrations

```bash
# Exécuter les migrations
php bin/migrate

# Générer une migration depuis un modèle
php bin/migrate make User

# Scanner et générer toutes les migrations manquantes
php bin/migrate make

# Voir le statut des migrations
php bin/migrate status

# Annuler la dernière migration
php bin/migrate rollback
```

Voir la [Documentation de génération de code](docs/guides/code-generation.md) et la [Documentation des migrations](docs/guides/migrations.md) pour plus de détails.

## 📚 Documentation

Toute la documentation est organisée dans le dossier [`docs/`](docs/) :

- **[Guide Pédagogique](docs/guides/pedagogique.md)** : 🎯 Guide complet expliquant chaque fichier et concept
- **[Installation](docs/guides/installation.md)** : Guide d'installation et de configuration
- **[Explications](docs/guides/explications.md)** : Explications détaillées (ORM, .env, cookies.txt, etc.)
- **[Exemples FormType](docs/examples/form-types.md)** : Guide complet pour créer des formulaires
- **[Génération de Code](docs/guides/code-generation.md)** : Guide pour générer contrôleurs, FormTypes et modèles
- **[Migrations](docs/guides/migrations.md)** : Guide complet du système de migrations
- **[Architecture Composer](docs/architecture/composer.md)** : Comment fonctionne l'architecture avec Composer
- **[API du Framework](docs/reference/framework-api.md)** : Documentation de l'API
- **[Changelog](docs/reference/changelog.md)** : Historique des modifications

Voir le [README de la documentation](docs/README.md) pour la liste complète.

## 🛠️ Fonctionnalités

### ✅ Core Framework
- ✅ Routing automatique avec attributs PHP 8+
- ✅ Container DI avec autowiring avancé
- ✅ Système de templates avec héritage et composants
- ✅ Request / Response HTTP enrichis
- ✅ Autoloader PSR-4 multi-namespace (Composer compatible)

### ✅ Router Avancé (Phase 3)
- ✅ Contraintes de paramètres (`{id:\d+}`, `{slug:[a-z-]+}`)
- ✅ Paramètres optionnels (`{category?}`)
- ✅ Middlewares par route et par groupe
- ✅ Groupes de routes avec préfixes
- ✅ Support des sous-domaines
- ✅ Génération d'URLs nommées

### ✅ Système HTTP Robuste (Phase 4)
- ✅ Request enrichi (headers, files, session, IP, JSON, AJAX)
- ✅ Response enrichi (headers, cookies, redirects, JSON)
- ✅ Gestion des sessions avec flash messages
- ✅ Support des fichiers uploadés

### ✅ Services et Outils (Phase 6)
- ✅ Validator de formulaires
- ✅ Logger PSR-3 (8 niveaux de log)
- ✅ Gestionnaire de configuration (Config + .env)
- ✅ Couche Database (PDO abstrait avec transactions)

### ✅ ORM Maison (Phase 7.5)
- ✅ Query Builder (SELECT, INSERT, UPDATE, DELETE)
- ✅ Model (Active Record Pattern)
- ✅ Repository Pattern (Data Mapper)
- ✅ Support des jointures (INNER, LEFT)
- ✅ Pagination et tri

### ✅ Intégration Composer (Phase 7)
- ✅ composer.json configuré
- ✅ Autoload PSR-4
- ✅ Prêt pour publication sur Packagist

### ✅ Gestion des Erreurs
- ✅ Exceptions personnalisées
- ✅ ErrorHandler avec modes dev/prod
- ✅ Pages d'erreur personnalisées

## 🎯 Objectif Final

Transformer le dossier `framework/` en **package Composer** réutilisable, comme Symfony ou Laravel le font.

## 📖 Exemples

### Créer une Nouvelle Route

1. Créer un contrôleur dans `src/Controller/`
2. Ajouter l'attribut `#[Route]`
3. Le router le détecte automatiquement !

```php
namespace App\Controller;

use Framework\Router\Attributes\Route;

class BlogController extends AbstractController
{
    #[Route(path: '/blog', methods: ['GET'], name: 'blog_index')]
    public function index()
    {
        $this->render('blog/index.html.php', [
            'title' => 'Mon Blog'
        ]);
    }
}
```

### Utiliser les Paramètres de Route

```php
#[Route(path: '/article/{id}', methods: ['GET'])]
public function show(string $id)
{
    // $id est automatiquement injecté !
    $this->render('article/show.html.php', [
        'articleId' => $id
    ]);
}
```

## 🤝 Contribution

Ce projet est pédagogique. N'hésitez pas à l'améliorer en suivant le plan dans `implementation_plan.md`.

## 📝 Licence

MIT - Libre d'utilisation pour apprendre et enseigner.

---

**Créé avec passion pour apprendre les bonnes pratiques PHP** 🚀
