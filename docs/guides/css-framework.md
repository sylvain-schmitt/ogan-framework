# 🎨 Configuration du Framework CSS

> Guide pour configurer et personnaliser le framework CSS dans Ogan Framework

## 📋 Vue d'ensemble

Ogan Framework intègre nativement **Tailwind CSS v4** via un binaire autonome (standalone CLI), vous permettant de compiler votre CSS sans avoir besoin d'installer Node.js ou NPM.

Vous pouvez également utiliser d'autres frameworks (Bootstrap, Custom) via configuration.

## 🚀 Tailwind CSS (Natif)

C'est la méthode recommandée. Le framework gère le téléchargement du binaire et la compilation.

### Configuration (`config/parameters.yaml`)

```yaml
css_framework:
  provider: tailwind
  version: 4.0.0
  cdn: false  # false = utilisation du compilateur local

tailwind:
  input: assets/css/app.css        # Fichier source
  output: public/assets/css/app.css # Fichier compilé
  minify: false                     # Minification pour prod
```

### Installation & Compilation

Le CLI du framework fournit des commandes dédiées :

```bash
# 1. Initialiser (télécharge le binaire si nécessaire)
php bin/console tailwind:init

# 2. Compiler (One-shot)
php bin/console tailwind:build

# 3. Compiler en mode Watch (développement)
php bin/console tailwind:build --watch

# 4. Compiler pour la production (minifié)
php bin/console tailwind:build --minify
```

### Utilisation dans les templates

Le layout par défaut inclut déjà l'asset compilé :

```html
<!-- templates/layouts/base.ogan -->
<head>
    <!-- ... -->
    <link rel="stylesheet" href="{{ asset('/assets/css/app.css') }}">
</head>
```

Vous pouvez utiliser les classes Tailwind directement dans vos fichiers `.ogan` :

```html
<div class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
    Bouton
</div>
```

---

## 🌐 Autres Frameworks (CDN)

Si vous ne souhaitez pas de compilation, vous pouvez utiliser des versions CDN.

### Configuration

Dans `config/parameters.yaml` :

```yaml
css_framework:
  provider: bootstrap  # ou 'tailwind' pour CDN
  version: 5.3.2
  cdn: true            # Force l'utilisation du CDN
```

### Helper de Vue

Utilisez `{{ cssFramework() }}` dans votre `<head>` pour insérer automatiquement le lien CDN approprié :

```html
<head>
    <title>{{ title }}</title>
    {{ cssFramework() }}
</head>
```

**Résultat (Bootstrap) :**
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
```

---

## 🔧 CSS Personnalisé

Si vous utilisez votre propre CSS sans framework :

```yaml
css_framework:
  provider: none
```

Ajoutez simplement vos fichiers CSS dans le dossier `public/assets/css/` et liez-les via le helper `asset()` :

```html
<link rel="stylesheet" href="{{ asset('/assets/css/style.css') }}">
```

---

## 📚 Résumé des Commandes

| Commande | Description |
|----------|-------------|
| `php bin/console tailwind:init` | Initialise Tailwind (télécharge binaire) |
| `php bin/console tailwind:build` | Compile le CSS une fois |
| `php bin/console tailwind:build --watch` | Compile et surveille les changements |
| `php bin/console tailwind:build --minify` | Compile et minifie pour la prod |

---

## ✅ Checklist Production

Pour déployer en production :

1. Configurer `minify: true` dans `parameters.yaml` (ou via surcharge en prod).
2. Exécuter `php bin/console tailwind:build --minify` lors du déploiement.
3. Vider le cache : `php bin/console cache:clear`.
