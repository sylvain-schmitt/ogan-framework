# 🎨 Helpers de Vue - Ogan Framework

> Guide complet des helpers disponibles dans les templates

## 📋 Vue d'Ensemble

Les helpers de vue sont des méthodes disponibles dans tous les templates via `$this->`. Ils simplifient la génération d'URLs, l'inclusion d'assets, et la création de liens.

---

## 🔗 Helpers d'URL

### `url($path, $absolute = false)`

Génère une URL absolue ou relative.

**Paramètres** :
- `$path` (string) : Chemin (ex: `/users` ou `users`)
- `$absolute` (bool) : Générer une URL absolue (défaut: `false`)

**Exemples** :

```php
<!-- URL relative -->
<a href="<?= $this->url('/users') ?>">Utilisateurs</a>
<!-- Génère : /users -->

<!-- URL absolue -->
<a href="<?= $this->url('/users', true) ?>">Utilisateurs</a>
<!-- Génère : http://localhost/users -->
```

---

### `route($name, $params = [], $absolute = false)`

Génère une URL depuis un nom de route.

**Paramètres** :
- `$name` (string) : Nom de la route
- `$params` (array) : Paramètres de la route
- `$absolute` (bool) : Générer une URL absolue (défaut: `false`)

**Exemples** :

```php
<!-- Route simple -->
<a href="<?= $this->route('user_list') ?>">Liste des utilisateurs</a>
<!-- Génère : /users -->

<!-- Route avec paramètres -->
<a href="<?= $this->route('user_show', ['id' => 42]) ?>">Voir l'utilisateur</a>
<!-- Génère : /users/42 -->

<!-- Route avec plusieurs paramètres -->
<a href="<?= $this->route('blog_post', [
    'year' => 2024,
    'month' => 12,
    'slug' => 'ogan-framework'
]) ?>">Article</a>
<!-- Génère : /blog/2024/12/ogan-framework -->

<!-- URL absolue -->
<a href="<?= $this->route('user_show', ['id' => 42], true) ?>">Voir l'utilisateur</a>
<!-- Génère : http://localhost/users/42 -->
```

**Définition de la route** :

```php
#[Route('/users/{id}', ['GET'], 'user_show')]
public function show(int $id) { ... }
```

---

## 📦 Helpers d'Assets

### `asset($path)`

Génère une URL pour un asset (CSS, JS, image).

**Paramètres** :
- `$path` (string) : Chemin vers l'asset (ex: `assets/css/style.css`)

**Exemples** :

```php
<!-- Image -->
<img src="<?= $this->asset('images/logo.png') ?>" alt="Logo">
<!-- Génère : /images/logo.png -->

<!-- Dans un attribut -->
<div style="background-image: url('<?= $this->asset('images/bg.jpg') ?>')">
</div>
```

---

### `css($path, $attributes = [])`

Génère une balise `<link>` pour un fichier CSS.

**Paramètres** :
- `$path` (string) : Chemin vers le fichier CSS
- `$attributes` (array) : Attributs additionnels (ex: `['media' => 'print']`)

**Exemples** :

```php
<!-- CSS simple -->
<?= $this->css('assets/css/style.css') ?>
<!-- Génère : <link rel="stylesheet" href="/assets/css/style.css"> -->

<!-- CSS avec attributs -->
<?= $this->css('assets/css/print.css', ['media' => 'print']) ?>
<!-- Génère : <link rel="stylesheet" href="/assets/css/print.css" media="print"> -->
```

---

### `js($path, $attributes = [])`

Génère une balise `<script>` pour un fichier JS.

**Paramètres** :
- `$path` (string) : Chemin vers le fichier JS
- `$attributes` (array) : Attributs additionnels (ex: `['defer' => true]`)

**Exemples** :

```php
<!-- JS simple -->
<?= $this->js('assets/js/app.js') ?>
<!-- Génère : <script src="/assets/js/app.js"></script> -->

<!-- JS avec defer -->
<?= $this->js('assets/js/app.js', ['defer' => true]) ?>
<!-- Génère : <script src="/assets/js/app.js" defer></script> -->

<!-- JS avec async -->
<?= $this->js('assets/js/analytics.js', ['async' => true]) ?>
<!-- Génère : <script src="/assets/js/analytics.js" async></script> -->
```

---

## 🔒 Helpers de Sécurité

### `e($value)` ou `escape($value)`

Échappe une chaîne pour l'affichage (protection XSS).

**Paramètres** :
- `$value` (string) : Valeur à échapper

**Exemples** :

```php
<!-- Échappement automatique -->
<h1><?= $this->e($title) ?></h1>
<!-- Si $title = "<script>alert('XSS')</script>" -->
<!-- Génère : <h1>&lt;script&gt;alert('XSS')&lt;/script&gt;</h1> -->

<!-- Alias -->
<p><?= $this->escape($user->name) ?></p>
```

**⚠️ Important** : Toujours échapper les variables utilisateur !

---

### `csrf_token()`

Génère le token CSRF.

**Exemples** :

```php
<!-- Dans un formulaire -->
<form method="POST">
    <?= $this->csrf_input() ?>
    <!-- Ou manuellement -->
    <input type="hidden" name="_csrf_token" value="<?= $this->csrf_token() ?>">
    ...
</form>
```

---

### `csrf_input()`

Génère un champ caché avec le token CSRF.

**Exemples** :

```php
<form method="POST">
    <?= $this->csrf_input() ?>
    <!-- Génère : <input type="hidden" name="_csrf_token" value="abc123..."> -->
    ...
</form>
```

---

## 🎨 Helpers de Composants

### `component($name, $props = [])`

Inclut un composant réutilisable.

**Paramètres** :
- `$name` (string) : Nom du composant
- `$props` (array) : Propriétés à passer au composant

**Exemples** :

```php
<!-- Composant simple -->
<?= $this->component('alert', ['type' => 'success', 'message' => 'Opération réussie']) ?>

<!-- Composant avec plusieurs props -->
<?= $this->component('card', [
    'title' => 'Titre',
    'content' => 'Contenu',
    'footer' => 'Pied de page'
]) ?>
```

---

## 📐 Helpers de Layout

### `extend($layout)` ou `layout($layout)`

Définit le layout parent.

**Exemples** :

```php
<?php
$this->extend('layouts/base.html.php');
$this->start('content');
?>
    <h1>Ma page</h1>
<?php $this->end(); ?>
```

---

### `start($name)` et `end()`

Démarre et termine une section.

**Exemples** :

```php
<?php $this->start('content'); ?>
    <h1>Contenu de la page</h1>
<?php $this->end(); ?>
```

---

### `section($name)`

Affiche le contenu d'une section.

**Exemples** :

```php
<!-- Dans le layout -->
<body>
    <?= $this->section('content') ?>
</body>
```

---

## 🎯 Exemples Complets

### Exemple 1 : Navigation avec Routes

```php
<nav>
    <a href="<?= $this->route('home') ?>">Accueil</a>
    <a href="<?= $this->route('user_list') ?>">Utilisateurs</a>
    <a href="<?= $this->route('user_show', ['id' => $currentUser->id]) ?>">Mon Profil</a>
</nav>
```

---

### Exemple 2 : Formulaire avec CSRF

```php
<form method="POST" action="<?= $this->route('user_update', ['id' => $user->id]) ?>">
    <?= $this->csrf_input() ?>
    
    <input type="text" name="name" value="<?= $this->e($user->name) ?>">
    <button type="submit">Mettre à jour</button>
</form>
```

---

### Exemple 3 : Page Complète avec Assets

```php
<?php
$this->extend('layouts/base.html.php');
$this->start('content');
?>

<!-- CSS additionnel -->
<?= $this->css('assets/css/page-specific.css') ?>

<h1><?= $this->e($title) ?></h1>

<!-- Image -->
<img src="<?= $this->asset('images/hero.jpg') ?>" alt="Hero">

<!-- JS -->
<?= $this->js('assets/js/page-specific.js', ['defer' => true]) ?>

<?php $this->end(); ?>
```

---

## ✅ Checklist d'Utilisation

- [ ] Utiliser `$this->route()` au lieu de hardcoder les URLs
- [ ] Toujours échapper les variables avec `$this->e()`
- [ ] Inclure `$this->csrf_input()` dans tous les formulaires POST
- [ ] Utiliser `$this->asset()` pour les images et fichiers statiques
- [ ] Utiliser `$this->css()` et `$this->js()` pour les assets

---

## 🐛 Dépannage

### Erreur "Router not set in View"

**Problème** : Le Router n'est pas injecté dans la View.

**Solution** : Vérifier que `AbstractController` injecte bien le Router dans la View (fait automatiquement).

---

### Route introuvable avec `route()`

**Problème** : La route n'existe pas ou n'a pas de nom.

**Solution** : Vérifier que la route a bien un attribut `name` :

```php
#[Route('/users/{id}', ['GET'], 'user_show')]
```

---

**Les helpers de vue sont maintenant disponibles dans tous vos templates !** 🎉

