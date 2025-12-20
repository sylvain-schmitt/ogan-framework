# 🐕 Ogan Framework

> _En mémoire d'Ogan 🐕💙 - Un framework PHP moderne créé avec passion pour apprendre et comprendre_

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🎯 Qu'est-ce que Ogan Framework ?

Ogan est un **framework PHP pédagogique** moderne qui démontre les meilleures pratiques de développement web. Inspiré par Symfony et Laravel, il offre une architecture MVC complète avec des fonctionnalités avancées tout en restant simple à comprendre.

### ✨ Points Forts

- 🏗️ **Architecture MVC** propre et moderne
- 🛣️ **Router avancé** avec attributs PHP 8+ et contraintes automatiques
- 📝 **Moteur de templates** avec compilation (`.ogan`)
- 🎨 **Tailwind CSS v4** intégré avec CLI standalone
- 📦 **Système de formulaires** complet avec 11 types de champs
- 🔐 **Sécurité** : CSRF, sessions, password hashing
- 🗄️ **ORM maison** avec QueryBuilder, relations et **Soft Delete**
- 🔄 **Migrations** de base de données
- 🎯 **Dependency Injection** avec autowiring
- 🔧 **Console CLI** avec générateurs de code
- 🔌 **API REST** : ApiController, sérialisation, `make:api`
- 📢 **Event Dispatcher** : événements kernel personnalisables
- 📝 **Logging** : PSR-3, channels, rotation automatique
- 🌱 **Seeders** : peuplement de base de données

## 📁 Structure du Projet

```
ogan-framework/
├── ogan/              # 🔧 Code du framework (réutilisable)
│   ├── Config/
│   ├── Console/
│   ├── Controller/
│   ├── Database/
│   ├── DependencyInjection/
│   ├── Form/
│   ├── Http/
│   ├── Router/
│   ├── Security/
│   ├── Session/
│   ├── View/
│   └── ...
├── src/               # 🎨 Code de l'application (exemple)
│   ├── Controller/
│   ├── Form/
│   └── Model/
├── templates/         # 📄 Vues (.ogan)
│   ├── layouts/
│   ├── components/
│   └── ...
├── public/            # 🌐 Point d'entrée web
│   └── index.php
├── config/            # ⚙️ Configuration
│   ├── parameters.yaml
│   └── middlewares.yaml
├── bin/               # 🔧 Console CLI
│   └── console
└── docs/              # 📚 Documentation complète
```

## 🚀 Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Extension PDO (SQLite, MySQL ou PostgreSQL)

### Installation via Composer (recommandé)

```bash
composer create-project ogan/framework mon-projet
cd mon-projet
```

### Installation manuelle

```bash
git clone https://github.com/votre-username/ogan-framework.git
cd ogan-framework
composer install
```

### Configuration

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Éditer .env selon vos besoins
nano .env
```

### Initialiser Tailwind CSS

```bash
# Initialiser Tailwind (télécharge le CLI standalone)
php bin/console tailwind:init

# Compiler les CSS
php bin/console tailwind:build

# Ou en mode watch pour le développement
php bin/console tailwind:build --watch
```

### Lancer le serveur

```bash
php -S localhost:8000 -t public
```

Ouvrir [http://localhost:8000](http://localhost:8000) dans votre navigateur.

## 🎓 Guide de Démarrage

### 1. Créer un Contrôleur

```php
namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;

class BlogController extends AbstractController
{
    #[Route(path: '/blog', methods: ['GET'], name: 'blog_index')]
    public function index()
    {
        return $this->render('blog/index.ogan', [
            'title' => 'Mon Blog'
        ]);
    }
    
    #[Route(path: '/blog/{slug}', methods: ['GET'], name: 'blog_show')]
    public function show(string $slug)
    {
        return $this->render('blog/show.ogan', [
            'slug' => $slug
        ]);
    }
}
```

### 2. Créer une Vue (Template .ogan)

```twig
{# templates/blog/index.ogan #}
{{ extend('layouts/base') }}

{{ start('body') }}
<div class="container mx-auto">
    <h1 class="text-4xl font-bold">{{ title }}</h1>
    
    {% for article in articles %}
        <article class="mb-4">
            <h2>{{ article.title }}</h2>
            <p>{{ article.content }}</p>
        </article>
    {% endfor %}
</div>
{{ end }}
```

### 3. Créer un Formulaire

```php
namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\Types\TextType;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\SubmitType;

class ContactFormType extends AbstractType
{
    public function buildForm(): void
    {
        $this->add('name', TextType::class, [
            'label' => 'Nom',
            'required' => true
        ]);
        
        $this->add('email', EmailType::class, [
            'label' => 'Email',
            'required' => true
        ]);
        
        $this->add('submit', SubmitType::class, [
            'label' => 'Envoyer'
        ]);
    }
}
```

Utilisation dans le template :

```twig
{% formStart(form) %}
    {% formRow(form.name) %}
    {% formRow(form.email) %}
    <button type="submit">Envoyer</button>
{% formEnd(form) %}
```

## � Console CLI

Le framework inclut une console puissante pour générer du code :

### Commandes Disponibles

```bash
# Afficher toutes les commandes
php bin/console

# Générer un contrôleur CRUD complet
php bin/console make:controller User

# Générer un formulaire
php bin/console make:form User

# Générer un modèle
php bin/console make:model Post

# Générer tout (modèle + form + contrôleur)
php bin/console make:all Article

# Migrations
php bin/console migrate              # Exécuter les migrations
php bin/console migrate:make User    # Créer une migration
php bin/console migrate:status       # Voir le statut
php bin/console migrate:rollback     # Annuler la dernière

# Tailwind CSS
php bin/console tailwind:init        # Initialiser Tailwind
php bin/console tailwind:build       # Compiler les CSS
php bin/console tailwind:build --watch  # Mode watch

# Utilitaires
php bin/console cache:clear          # Vider le cache
php bin/console routes:list          # Lister les routes
```

## � Système de Templates

### Syntaxe .ogan

Le framework utilise une syntaxe de template moderne et intuitive :

**Variables** : `{{ variable }}`
```twig
{{ title }}
{{ user.name }}
```

**Structures de contrôle** : `{% if/for %}`
```twig
{% if user %}
    <p>Bonjour {{ user.name }}</p>
{% endif %}

{% for item in items %}
    <li>{{ item }}</li>
{% endfor %}
```

**Helpers de formulaires** : `{% formStart() %}`
```twig
{% formStart(form) %}
    {% formRow(form.email) %}
    {% formRow(form.password) %}
{% formEnd(form) %}
```

**Rendu complet** : `{% form.render() %}`
```twig
{% form.render() %}
```

## 🛠️ Fonctionnalités Complètes

### ✅ Core Framework
- ✅ Routing avec attributs PHP 8+ et contraintes automatiques
- ✅ Container DI avec autowiring
- ✅ Moteur de templates avec compilation (`.ogan`)
- ✅ Request / Response HTTP enrichis
- ✅ Autoloader PSR-4 (compatible Composer)

### ✅ Router Avancé
- ✅ Contraintes automatiques (`{id}` → numérique, `{slug}` → URL-friendly)
- ✅ Paramètres optionnels (`{query?}`)
- ✅ Middlewares par route et par groupe
- ✅ Groupes de routes avec préfixes
- ✅ Support des sous-domaines
- ✅ Génération d'URLs nommées

### ✅ Système de Formulaires
- ✅ 11 types de champs (Text, Email, Password, Number, Date, Textarea, Select, Checkbox, Radio, File, Submit)
- ✅ Validation côté serveur et HTML5
- ✅ Helpers de rendu flexibles
- ✅ Support des fichiers uploadés
- ✅ Protection CSRF intégrée

### ✅ Sécurité
- ✅ Protection CSRF
- ✅ Password hashing (bcrypt)
- ✅ Sessions sécurisées
- ✅ Validation des données
- ✅ Échappement HTML automatique

### ✅ ORM & Base de Données
- ✅ Query Builder fluide
- ✅ Active Record Pattern
- ✅ Repository Pattern
- ✅ Relations (OneToOne, OneToMany, ManyToOne, ManyToMany)
- ✅ Migrations
- ✅ Support SQLite, MySQL, PostgreSQL

### ✅ Tailwind CSS
- ✅ CLI standalone (pas de Node.js requis)
- ✅ Compilation automatique
- ✅ Mode watch pour le développement
- ✅ Configuration via `assets/css/app.css`

### ✅ Console CLI
- ✅ Générateurs de code (controller, form, model)
- ✅ Gestion des migrations
- ✅ Compilation Tailwind
- ✅ Utilitaires (cache, routes)

## 📚 Documentation

Documentation complète disponible dans le dossier [`docs/`](docs/) :

- **[Guide Pédagogique](docs/guides/pedagogique.md)** - Guide complet pour comprendre le framework
- **[Installation](docs/guides/installation.md)** - Guide d'installation détaillé
- **[Syntaxe des Templates](docs/guides/template-syntax.md)** - Documentation de la syntaxe `.ogan`
- **[Formulaires](docs/examples/form-types.md)** - Guide complet des formulaires
- **[Génération de Code](docs/guides/code-generation.md)** - Utilisation des générateurs
- **[Migrations](docs/guides/migrations.md)** - Système de migrations
- **[ORM & Relations](docs/guides/orm-relations.md)** - Utilisation de l'ORM
- **[API du Framework](docs/reference/framework-api.md)** - Documentation de l'API

## 🐳 Docker (Optionnel)

Démarrez rapidement avec Docker :

```bash
docker-compose up -d
```

**Services inclus :**
- MySQL 8.0 → Port 3306
- phpMyAdmin → [http://localhost:8080](http://localhost:8080)
- MailHog → [http://localhost:8025](http://localhost:8025)

Voir le [Guide Docker](docs/guides/docker.md) pour plus de détails.

## 🎯 Objectif

Créer un framework PHP pédagogique qui :
- Démontre les meilleures pratiques modernes
- Reste simple à comprendre
- Peut être publié sur Packagist
- Sert de base d'apprentissage

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer des améliorations
- Soumettre des pull requests

## 📝 Licence

MIT - Libre d'utilisation pour apprendre et enseigner.

## 🙏 Remerciements

Ce framework a été créé avec passion pour honorer la mémoire d'Ogan 🐕💙 et pour aider les développeurs à comprendre les concepts fondamentaux des frameworks PHP modernes.

---

**Créé avec ❤️ pour apprendre et partager** 🚀
