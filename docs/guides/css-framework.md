# 🎨 Configuration du Framework CSS

> Guide pour configurer et personnaliser le framework CSS dans Ogan Framework

## 📋 Vue d'ensemble

Ogan Framework supporte plusieurs frameworks CSS et permet une configuration flexible via `config/parameters.php` ou `.env`.

**Framework par défaut :** Tailwind CSS (via CDN)

---

## ⚙️ Configuration

### Dans `config/parameters.php`

```php
'css_framework' => [
    'provider' => 'tailwind',  // tailwind, bootstrap, custom, none
    'version' => '3.4.0',      // Version du framework (si applicable)
    'cdn' => true,             // Utiliser le CDN (true) ou fichiers locaux (false)
    'custom_css' => [],        // Fichiers CSS personnalisés additionnels
],
```

### Dans `.env` (Optionnel)

```env
CSS_FRAMEWORK_PROVIDER=tailwind
CSS_FRAMEWORK_VERSION=3.4.0
CSS_FRAMEWORK_CDN=true
```

---

## 🎨 Frameworks Supportés

### 1. Tailwind CSS (Par Défaut)

**Configuration :**
```php
'css_framework' => [
    'provider' => 'tailwind',
    'cdn' => true,  // Utilise le CDN Tailwind
],
```

**Utilisation dans les templates :**
```php
// Le layout de base inclut automatiquement Tailwind
<?php $this->extend('layouts/base'); ?>

// Utilisez les classes Tailwind directement
<div class="bg-blue-500 text-white p-4 rounded-lg">
    Contenu
</div>
```

**Fichiers locaux (si `cdn => false`) :**
- Placez votre CSS compilé dans `public/assets/css/tailwind.css`
- Configurez `'cdn' => false` dans la config

---

### 2. Bootstrap 5

**Configuration :**
```php
'css_framework' => [
    'provider' => 'bootstrap',
    'version' => '5.3.2',
    'cdn' => true,
],
```

**Utilisation dans les templates :**
```php
// Utilisez les classes Bootstrap
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <button class="btn btn-primary">Cliquer</button>
        </div>
    </div>
</div>
```

---

### 3. CSS Personnalisé

**Configuration :**
```php
'css_framework' => [
    'provider' => 'custom',
    'custom_css' => [
        'assets/css/main.css',
        'assets/css/components.css',
    ],
],
```

**Utilisation :**
- Placez vos fichiers CSS dans `public/assets/css/`
- Ils seront automatiquement inclus dans le layout

---

### 4. Aucun Framework

**Configuration :**
```php
'css_framework' => [
    'provider' => 'none',
],
```

**Utilisation :**
- Aucun framework CSS n'est inclus
- Utilisez uniquement votre CSS personnalisé via `custom_css`

---

## 🔧 Utilisation dans les Templates

### Layout de Base

Le layout `templates/layouts/base.html.php` inclut automatiquement le framework CSS configuré :

```php
<?php $title = $title ?? 'Mon site'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <?= $this->cssFramework() ?>  <!-- ← Inclut le framework configuré -->
    
    <!-- CSS personnalisé additionnel -->
    <link rel="stylesheet" href="<?= $this->asset('assets/css/style.css') ?>">
</head>
<body>
    <!-- ... -->
</body>
</html>
```

### Helper dans View

Vous pouvez aussi utiliser le helper directement dans vos templates :

```php
<?= $this->cssFramework() ?>
```

---

## 📝 Exemples de Templates

### Avec Tailwind CSS (Par Défaut)

```php
<?php $this->extend('layouts/base'); ?>

<?php $this->start('body'); ?>
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Titre</h1>
    <div class="bg-white rounded-lg shadow-lg p-6">
        <p class="text-gray-600">Contenu</p>
    </div>
</div>
<?php $this->end(); ?>
```

### Avec Bootstrap

```php
<?php $this->extend('layouts/base'); ?>

<?php $this->start('body'); ?>
<div class="container">
    <h1 class="display-4">Titre</h1>
    <div class="card">
        <div class="card-body">
            <p>Contenu</p>
        </div>
    </div>
</div>
<?php $this->end(); ?>
```

---

## 🎯 Changer de Framework

### Méthode 1 : Via `config/parameters.php`

1. Ouvrez `config/parameters.php`
2. Modifiez la section `css_framework` :

```php
'css_framework' => [
    'provider' => 'bootstrap',  // Changez ici
    'version' => '5.3.2',
    'cdn' => true,
],
```

3. Mettez à jour vos templates pour utiliser les classes du nouveau framework

### Méthode 2 : Via `.env`

```env
CSS_FRAMEWORK_PROVIDER=bootstrap
CSS_FRAMEWORK_VERSION=5.3.2
CSS_FRAMEWORK_CDN=true
```

---

## 🚀 Installation de Tailwind CSS (Fichiers Locaux)

Si vous préférez compiler Tailwind localement au lieu d'utiliser le CDN :

### 1. Installer Tailwind CSS

```bash
npm install -D tailwindcss
npx tailwindcss init
```

### 2. Configurer `tailwind.config.js`

```js
module.exports = {
  content: [
    "./templates/**/*.html.php",
    "./src/**/*.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

### 3. Créer `public/assets/css/tailwind.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### 4. Compiler

```bash
npx tailwindcss -i ./public/assets/css/tailwind.css -o ./public/assets/css/tailwind.min.css --minify
```

### 5. Configurer le Framework

```php
'css_framework' => [
    'provider' => 'tailwind',
    'cdn' => false,  // Utiliser les fichiers locaux
],
```

---

## 📚 Ressources

- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [CDN Tailwind](https://cdn.tailwindcss.com)
- [CDN Bootstrap](https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/)

---

## ✅ Checklist

- [ ] Framework CSS configuré dans `config/parameters.php`
- [ ] Layout de base utilise `$this->cssFramework()`
- [ ] Templates utilisent les classes du framework choisi
- [ ] CSS personnalisé ajouté si nécessaire
- [ ] Responsive design testé

---

**Le framework CSS est maintenant configuré et prêt à être utilisé !** 🎨

