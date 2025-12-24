# 🔍 SEO - Sitemap et Robots.txt

> Génération automatique des fichiers SEO pour Google Search Console

## 📋 Vue d'ensemble

Ogan Framework fournit des outils pour générer automatiquement :
- **`sitemap.xml`** : Liste des URLs indexables par les moteurs de recherche
- **`robots.txt`** : Instructions pour les crawlers (ce qu'ils peuvent/ne peuvent pas visiter)

---

## 🚀 Commandes Console

### Générer tous les fichiers SEO

```bash
php bin/console seo:all --base-url=https://votre-site.com
```

### Générer sitemap uniquement

```bash
php bin/console seo:sitemap --base-url=https://votre-site.com
```

### Générer robots.txt uniquement

```bash
php bin/console seo:robots --base-url=https://votre-site.com
```

### Options disponibles

| Option | Description | Défaut |
|--------|-------------|--------|
| `--base-url` | URL de base du site | `https://example.com` |
| `--output` | Dossier de sortie | `public/` |

---

## 🗺️ SitemapGenerator

### Utilisation basique

```php
use Ogan\Seo\SitemapGenerator;

$sitemap = new SitemapGenerator('https://votre-site.com');

// Ajouter des URLs manuellement
$sitemap->addUrl('/', priority: 1.0)
        ->addUrl('/about', priority: 0.8)
        ->addUrl('/contact', priority: 0.6)
        ->addUrl('/blog', changefreq: 'daily');

$sitemap->save('public/sitemap.xml');
```

### Génération automatique depuis les routes

```php
use Ogan\Seo\SitemapGenerator;
use Ogan\Router\Router;

$router = new Router();
$router->loadRoutesFromControllers(__DIR__ . '/src/Controller');

$sitemap = new SitemapGenerator('https://votre-site.com');
$sitemap->addRoutesFromRouter($router);
$sitemap->save('public/sitemap.xml');
```

### Paramètres de `addUrl()`

| Paramètre | Type | Description | Défaut |
|-----------|------|-------------|--------|
| `$path` | string | Chemin de l'URL | - |
| `$lastmod` | string\|null | Date de modification (ISO 8601) | Date du jour |
| `$changefreq` | string | Fréquence de mise à jour | `weekly` |
| `$priority` | float | Priorité (0.0 à 1.0) | `0.5` |

**Valeurs de `changefreq` :** `always`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`, `never`

### Patterns d'exclusion

Par défaut, les routes suivantes sont exclues :
- `/admin*`
- `/api*`
- `/login`, `/logout`, `/register`
- `/forgot-password`, `/reset-password*`

Personnaliser les exclusions :

```php
$sitemap->setExcludePatterns([
    '/admin*',
    '/private*',
]);

// Ou ajouter un pattern
$sitemap->addExcludePattern('/members-only*');
```

### Résultat généré

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://votre-site.com/</loc>
    <lastmod>2025-12-24</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
```

---

## 🤖 RobotsGenerator

### Utilisation basique

```php
use Ogan\Seo\RobotsGenerator;

$robots = new RobotsGenerator('https://votre-site.com');
// Les règles par défaut sont appliquées automatiquement

$robots->sitemap('/sitemap.xml')
       ->save('public/robots.txt');
```

### Règles par défaut

Le générateur bloque automatiquement :
- `/admin/`
- `/api/`
- `/login`, `/logout`, `/register`
- `/forgot-password`, `/reset-password`, `/verify-email`

### Personnaliser les règles

```php
$robots = new RobotsGenerator('https://votre-site.com', withDefaults: false);

$robots->allow('/')
       ->disallow('/admin/')
       ->disallow('/private/')
       ->sitemap('/sitemap.xml')
       ->crawlDelay(2)  // Délai entre les requêtes
       ->save();
```

### Règles par user-agent

```php
$robots = new RobotsGenerator('https://votre-site.com');

// Règles pour tous les bots
$robots->allow('/')
       ->disallow('/admin/');

// Règles spécifiques pour Googlebot
$robots->userAgent('Googlebot')
       ->allow('/special-page/')
       ->disallow('/no-google/');

$robots->save();
```

### Résultat généré

```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /login
Disallow: /logout

Sitemap: https://votre-site.com/sitemap.xml
```

---

## 📤 Soumettre à Google Search Console

1. **Générer les fichiers :**
   ```bash
   php bin/console seo:all --base-url=https://votre-site.com
   ```

2. **Vérifier les fichiers :**
   - `https://votre-site.com/sitemap.xml`
   - `https://votre-site.com/robots.txt`

3. **Soumettre le sitemap :**
   - Aller sur [Google Search Console](https://search.google.com/search-console)
   - Sélectionner votre propriété
   - Aller dans "Sitemaps" → Ajouter `sitemap.xml`

4. **Tester robots.txt :**
   - Utiliser l'outil [Robots Testing Tool](https://www.google.com/webmasters/tools/robots-testing-tool)

---

## 🔄 Automatisation

### Via cron (régénération quotidienne)

```bash
# crontab -e
0 2 * * * cd /var/www/mysite && php bin/console seo:all --base-url=https://votre-site.com
```

### Via événement de déploiement

Ajoutez dans votre script de déploiement :

```bash
composer install --no-dev
php bin/console cache:clear
php bin/console seo:all --base-url=https://votre-site.com
```

---

## ✅ Bonnes Pratiques

1. **Régénérer après chaque déploiement** pour inclure les nouvelles pages
2. **Utiliser des priorités cohérentes** : page d'accueil (1.0), pages importantes (0.8), autres (0.5)
3. **Ne pas indexer les pages admin/API** - déjà exclu par défaut
4. **Garder le sitemap à jour** avec les bonnes dates de modification
5. **Tester régulièrement** l'accessibilité du sitemap via Search Console
