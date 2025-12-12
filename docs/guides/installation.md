# 📦 Guide d'Installation - Ogan Framework

## Installation via Composer

### 1. Installation

```bash
composer require ogan/framework
```

### 2. Structure du Projet

Après installation, créez la structure suivante :

```
votre-projet/
├── composer.json
├── composer.lock
├── vendor/              # Dependencies (généré par Composer)
├── config/
│   ├── parameters.php
│   └── middlewares.php
├── public/
│   └── index.php
├── src/
│   └── Controller/
└── templates/
    ├── layouts/
    └── components/
```

### 3. Configuration

#### 3.1. Point d'entrée (`public/index.php`)

```php
<?php

declare(strict_types=1);

use Ogan\Kernel\Kernel;

// Charger Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Initialiser la configuration
\Ogan\Config\Config::init(__DIR__ . '/../config/parameters.php');

// Créer et lancer le Kernel
$kernel = new Kernel(debug: true);
$kernel->run();
```

#### 3.2. Configuration (`config/parameters.php`)

```php
<?php

return [
    // Application
    'app' => [
        'env' => 'dev',        // dev, prod
        'debug' => true,
    ],

    // Base de données
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'myapp',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    // Vues
    'view' => [
        'templates_path' => __DIR__ . '/../templates',
        'default_layout' => 'layouts/base.html.php',
        'default_title' => 'Mon Application',
    ],
];
```

#### 3.3. Variables d'Environnement (`.env`)

Créez un fichier `.env` à la racine :

```env
APP_ENV=dev
APP_DEBUG=true

DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=
```

⚠️ **Important** : Ajoutez `.env` dans `.gitignore` pour ne pas commiter les secrets !

### 4. Créer votre Premier Contrôleur

```php
<?php

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', methods: ['GET'], name: 'home')]
    public function index()
    {
        $this->render('home/index.html.php', [
            'title' => 'Bienvenue sur Ogan Framework'
        ]);
    }
}
```

### 5. Créer votre Première Vue

`templates/home/index.html.php` :

```php
<h1><?= $title ?></h1>
<p>Bienvenue sur Ogan Framework ! 🐕💙</p>
```

### 6. Lancer le Serveur

```bash
php -S localhost:8000 -t public
```

Ouvrez [http://localhost:8000](http://localhost:8000) dans votre navigateur.

## Installation Manuelle (Sans Composer)

Si vous préférez ne pas utiliser Composer :

1. Clonez le repository
2. Copiez le dossier `ogan/` dans votre projet
3. Utilisez l'autoloader maison (`autoload.php`)

```php
require __DIR__ . '/../autoload.php';
```

## Configuration du Serveur Web

### Apache (.htaccess)

Créez `public/.htaccess` :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

### Nginx

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/your/project/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Prochaines Étapes

- 📖 Lisez le [GUIDE_PEDAGOGIQUE.md](GUIDE_PEDAGOGIQUE.md) pour comprendre chaque composant
- 🎯 Consultez les [exemples](examples/) pour voir des cas d'usage
- 🛠️ Explorez la [documentation API](docs/)

## Support

Pour toute question ou problème, ouvrez une issue sur GitHub.

---

**Bon développement avec Ogan Framework ! 🐕💙**

