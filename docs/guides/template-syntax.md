# 📝 Syntaxe des Templates - Ogan Framework

> Guide complet sur la nouvelle syntaxe `{{ }}` pour les templates

## 🎯 Vue d'Ensemble

Le framework supporte maintenant une syntaxe moderne similaire à Twig ou Blade, permettant d'utiliser `{{ }}` au lieu de `<?= $this->e($variable) ?>`.

## ✅ Activation

La compilation de templates est activée par défaut dans `config/parameters.php` :

```php
'view' => [
    'use_compiler' => true,  // Activer la syntaxe {{ }}
    'cache_dir' => __DIR__ . '/../var/cache/templates',
],
```

## 📖 Syntaxe de Base

### Variables

**Ancienne syntaxe :**
```php
<?= $this->e($title) ?>
```

**Nouvelle syntaxe :**
```html
{{ title }}
```

### Variables sans échappement

Pour afficher du HTML brut (attention aux risques XSS) :

**Ancienne syntaxe :**
```php
<?= $html ?>
```

**Nouvelle syntaxe :**
```html
{{! html }}
```

### Sections

**Ancienne syntaxe :**
```php
<?= $this->section('body') ?>
```

**Nouvelle syntaxe :**
```html
{{ section('body') }}
```

### Routes

**Ancienne syntaxe :**
```php
<?= $this->route('user_show', ['id' => 42]) ?>
```

**Nouvelle syntaxe :**
```html
{{ route('user_show', ['id' => 42]) }}
```

### Assets

**Ancienne syntaxe :**
```php
<?= $this->asset('assets/css/style.css') ?>
```

**Nouvelle syntaxe :**
```html
{{ asset('assets/css/style.css') }}
```

### Helpers CSS/JS

**Ancienne syntaxe :**
```php
<?= $this->css('assets/css/style.css') ?>
<?= $this->js('assets/js/app.js') ?>
```

**Nouvelle syntaxe :**
```html
{{ css('assets/css/style.css') }}
{{ js('assets/js/app.js') }}
```

### Framework CSS

**Ancienne syntaxe :**
```php
<?= $this->cssFramework() ?>
```

**Nouvelle syntaxe :**
```html
{{ cssFramework() }}
```

## 📋 Exemple Complet

### Template avec nouvelle syntaxe

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ title }}</title>
    {{ cssFramework() }}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
    {{ section('header') }}
    
    <main>
        <h1>{{ page_title }}</h1>
        <p>{{ description }}</p>
        
        <a href="{{ route('users_list') }}">Voir les utilisateurs</a>
        <a href="{{ route('user_show', ['id' => 42]) }}">Utilisateur #42</a>
    </main>
    
    {{ section('footer') }}
</body>
</html>
```

### Template compilé (généré automatiquement)

```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $this->e($title) ?></title>
    <?= $this->cssFramework() ?>
    <link rel="stylesheet" href="<?= $this->e($this->asset('assets/css/style.css')) ?>">
</head>
<body>
    <?= $this->section('header') ?>
    
    <main>
        <h1><?= $this->e($page_title) ?></h1>
        <p><?= $this->e($description) ?></p>
        
        <a href="<?= $this->e($this->route('users_list')) ?>">Voir les utilisateurs</a>
        <a href="<?= $this->e($this->route('user_show', ['id' => 42])) ?>">Utilisateur #42</a>
    </main>
    
    <?= $this->section('footer') ?>
</body>
</html>
```

---

## 🏗️ Héritage de Templates

Le système supporte l'héritage de templates (layouts) avec les directives `extend()`, `start()` et `end`.

### Syntaxe d'héritage

Pour qu'un template hérite d'un layout, utilisez `{{ extend('chemin/du/layout') }}` :

```html
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="content">
    <h1>{{ title }}</h1>
    <p>Contenu de ma page</p>
</div>
{{ end }}
```

### Le layout parent

Le layout parent définit la structure HTML et utilise `{{ section('body') }}` pour afficher les blocs enfants :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>{{ title }}</title>
    <link rel="stylesheet" href="{{ asset('/assets/css/app.css') }}">
</head>
<body>
    {{ component('navbar') }}
    
    <main class="container">
        {{ component('flashes') }}
        {{ section('body') }}
    </main>
    
    {{ component('footer') }}
</body>
</html>
```

### Directives d'héritage

| Directive | Usage | Description |
|-----------|-------|-------------|
| `{{ extend('path') }}` | Template enfant | Définit le layout parent à utiliser |
| `{{ start('name') }}` | Template enfant | Commence un bloc nommé |
| `{{ end }}` | Template enfant | Termine le bloc en cours |
| `{{ section('name') }}` | Layout parent | Affiche le contenu du bloc nommé |

> **⚠️ Important** : `extend()` doit toujours utiliser des **parenthèses** autour du chemin du layout. La syntaxe `{{ extend 'path' }}` sans parenthèses ne fonctionne pas.

### Exemple complet

**Layout** (`templates/layouts/base.ogan`) :
```html
{{ title = title ?? 'Mon site' }}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ title }}</title>
</head>
<body>
    {{ section('body') }}
</body>
</html>
```

**Page** (`templates/home/index.ogan`) :
```html
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="hero">
    <h1>Bienvenue sur {{ title }}</h1>
</div>
{{ end }}
```

---

## 🔧 Méthodes Supportées

Les méthodes suivantes peuvent être utilisées avec la syntaxe `{{ }}` :

**Héritage de templates :**
- `extend('layout')` - Définit le layout parent (doit être en première ligne)
- `start('name')` - Commence un bloc nommé
- `end` - Termine le bloc en cours

**Affichage :**
- `section('name')` - Affiche une section (retourne du HTML, **non échappée**)
- `component('name', ['prop' => 'value'])` - Affiche un composant (retourne du HTML, **non échappée**)
- `route('name', ['param' => 'value'])` - Génère une URL depuis un nom de route
- `url('/path', true)` - Génère une URL absolue ou relative
- `asset('path')` - Génère le chemin vers un asset
- `css('path')` - Génère une balise `<link>` CSS
- `js('path')` - Génère une balise `<script>` JS
- `cssFramework()` - Génère les balises du framework CSS configuré (retourne du HTML, **non échappée**)
- `csrf_token()` - Retourne le token CSRF
- `csrf_input()` - Génère un champ caché avec le token CSRF (retourne du HTML, **non échappée**)


### 🤔 Pourquoi certaines méthodes ne sont pas échappées ?

**Méthodes non échappées** : `section()`, `component()`, `cssFramework()`, `csrf_input()`

Ces méthodes retournent du **HTML déjà formaté et sécurisé**. Si on les échappait, le HTML serait affiché comme du texte brut au lieu d'être interprété par le navigateur.

#### Exemple avec `section()` :

```html
<!-- Dans votre template -->
{{ section('header') }}

<!-- Ce que section() retourne (du HTML) -->
<nav class="bg-blue-600">
    <a href="/">Accueil</a>
</nav>
```

**Si on échappait** `section()` :
```html
<!-- Résultat affiché dans le navigateur (TEXTE BRUT) -->
&lt;nav class=&quot;bg-blue-600&quot;&gt;
    &lt;a href=&quot;/&quot;&gt;Accueil&lt;/a&gt;
&lt;/nav&gt;
```

**Sans échappement** (correct) :
```html
<!-- Résultat : le HTML est interprété -->
<nav class="bg-blue-600">
    <a href="/">Accueil</a>
</nav>
```

#### Exemple avec `cssFramework()` :

```html
<!-- Dans votre template -->
{{ cssFramework() }}

<!-- Ce que cssFramework() retourne (du HTML) -->
<link href="https://cdn.tailwindcss.com/3.4.0" rel="stylesheet">
```

**Si on échappait** `cssFramework()` :
```html
<!-- Résultat : la balise CSS serait affichée comme texte -->
&lt;link href=&quot;https://cdn.tailwindcss.com/3.4.0&quot; rel=&quot;stylesheet&quot;&gt;
<!-- ❌ Le CSS ne serait pas chargé ! -->
```

**Sans échappement** (correct) :
```html
<!-- Résultat : la balise CSS est interprétée -->
<link href="https://cdn.tailwindcss.com/3.4.0" rel="stylesheet">
<!-- ✅ Le CSS est chargé correctement -->
```

#### Exemple avec `component()` :

```html
<!-- Dans votre template -->
{{ component('alert', ['type' => 'success', 'message' => 'Bravo !']) }}

<!-- Ce que component() retourne (du HTML) -->
<div class="bg-green-100 text-green-800">
    <strong>Success!</strong>
    <span>Bravo !</span>
</div>
```

**Si on échappait** `component()` :
```html
<!-- Résultat : le composant serait affiché comme texte -->
&lt;div class=&quot;bg-green-100&quot;&gt;
    &lt;strong&gt;Success!&lt;/strong&gt;
    &lt;span&gt;Bravo !&lt;/span&gt;
&lt;/div&gt;
<!-- ❌ Pas de design, pas de style ! -->
```

**Sans échappement** (correct) :
```html
<!-- Résultat : le composant est rendu correctement -->
<div class="bg-green-100 text-green-800">
    <strong>Success!</strong>
    <span>Bravo !</span>
</div>
<!-- ✅ Design et styles appliqués -->
```

### 🔒 Sécurité

**Pourquoi c'est sûr ?**

Ces méthodes génèrent du HTML **contrôlé par le framework**, pas du contenu utilisateur :

- `section()` : Retourne du contenu défini dans vos templates (vous contrôlez le contenu)
- `component()` : Retourne du HTML généré depuis vos templates de composants (sécurisé)
- `cssFramework()` : Génère des balises CSS depuis la configuration (sécurisé)
- `csrf_input()` : Génère un champ caché avec un token sécurisé (sécurisé)

**⚠️ Important** : Ne jamais utiliser `{{! }}` avec du contenu utilisateur non validé, car cela désactiverait l'échappement et exposerait votre application aux attaques XSS.

### 📊 Comparaison

| Méthode | Retourne | Échappée ? | Pourquoi |
|---------|----------|------------|----------|
| `section()` | HTML | ❌ Non | HTML déjà formaté, doit être interprété |
| `component()` | HTML | ❌ Non | HTML déjà formaté, doit être interprété |
| `cssFramework()` | HTML | ❌ Non | Balises CSS, doivent être interprétées |
| `csrf_input()` | HTML | ❌ Non | Champ HTML, doit être interprété |
| `route()` | URL (string) | ✅ Oui | Chaîne simple, doit être échappée |
| `asset()` | Chemin (string) | ✅ Oui | Chaîne simple, doit être échappée |
| `csrf_token()` | Token (string) | ✅ Oui | Chaîne simple, doit être échappée |

### Expressions Complexes

Le compilateur supporte également les expressions PHP complexes :

```html
<!-- Variables PHP avec $ -->
<div class="{{ $class }}">{{ $user->getName() }}</div>

<!-- Expressions avec opérateurs -->
<p>{{ ucfirst($type ?? 'Info') }}</p>
<p>{{ count($items) }} éléments</p>

<!-- Appels de méthodes -->
<p>{{ $user->getEmail() }}</p>
```

**Note** : Les expressions qui commencent par `$` sont automatiquement reconnues comme du PHP et ne nécessitent pas de guillemets.

## 🔍 Expressions Complexes

### Variables PHP avec `$`

Vous pouvez utiliser des expressions PHP complexes :

```html
{{ $class }}
{{ ucfirst($type ?? 'Info') }}
{{ $user->getName() }}
{{ count($items) }}
```

### Composants

Les composants sont automatiquement compilés :

```html
{{ component('alert', ['type' => 'success', 'message' => 'Bravo !']) }}
{{ component('card', ['title' => 'Titre', 'content' => 'Contenu']) }}
```

## ⚠️ Notes Importantes

1. **Échappement automatique** : Par défaut, toutes les variables sont échappées pour la sécurité XSS. Utilisez `{{! }}` uniquement si vous êtes sûr que le contenu est sûr.

2. **Méthodes non échappées** : Certaines méthodes retournent du HTML et ne sont **jamais** échappées automatiquement :
   - `section()` - Retourne le contenu HTML d'une section
   - `component()` - Retourne le HTML d'un composant
   - `cssFramework()` - Retourne les balises CSS du framework
   - `csrf_input()` - Retourne un champ HTML pour le CSRF

3. **Cache** : Les templates sont compilés et mis en cache dans `var/cache/templates/`. 
   - **Mode développement** : Le cache est automatiquement invalidé à chaque requête (auto-reload activé)
   - **Mode production** : Le cache est persistant pour de meilleures performances (auto-reload désactivé)

4. **Compilation automatique** : Tous les templates et composants sont automatiquement compilés lors de leur première utilisation. Les fichiers compilés sont stockés dans `var/cache/templates/`.

5. **Compatibilité** : L'ancienne syntaxe PHP (`<?= $this->e($variable) ?>`) continue de fonctionner. Vous pouvez mélanger les deux syntaxes si nécessaire.

6. **Structures de contrôle** : Les structures de contrôle utilisent la syntaxe `{% %}` (pas `{{ }}`) :

```html
{% if user %}
    {% for item in items %}
        <div>{{ item.name }}</div>
    {% endfor %}
{% endif %}
```

**Structures supportées :**
- `{% if condition %}` ... `{% endif %}`
- `{% elseif condition %}`
- `{% else %}`
- `{% for item in items %}` ... `{% endfor %}`
- `{% for key, value in items %}` ... `{% endfor %}`

**Exemples :**
```html
{% if user_count > 0 %}
    <p>{{ user_count }} utilisateur(s)</p>
{% elseif user_count == 0 %}
    <p>Aucun utilisateur</p>
{% else %}
    <p>Nombre inconnu</p>
{% endif %}

{% for feature in features %}
    {{ component('card', ['title' => feature.title]) }}
{% endfor %}
```

**Note** : Vous pouvez toujours utiliser du PHP natif si vous préférez :
```html
<?php if (isset($users)): ?>
    <?php foreach ($users as $user): ?>
        <div>{{ user.name }}</div>
    <?php endforeach; ?>
<?php endif; ?>
```

## 🗑️ Vider le Cache

Pour vider le cache des templates compilés :

```php
$view->clearTemplateCache();
```

Ou manuellement :

```bash
rm -rf var/cache/templates/*
```

## 🎓 Migration depuis l'Ancienne Syntaxe

Pour migrer un template existant :

1. Renommer le fichier de `.html.php` vers `.ogan`
2. Remplacer `<?= $this->e($variable) ?>` par `{{ variable }}`
3. Remplacer `<?= $this->section('name') ?>` par `{{ section('name') }}`
4. Remplacer `<?= $this->route('name') ?>` par `{{ route('name') }}`
5. Remplacer les routes hardcodées par `{{ route('nom_route') }}`
6. Remplacer `{{ foreach (items as item) }}` par `{% for item in items %}`
7. **Héritage** : Remplacer `<?php $this->layout('...'); ?>` par `{{ extend('...') }}`
8. **Blocs** : Remplacer `<?php $this->start('body'); ?>` par `{{ start('body') }}`
9. **Fin de bloc** : Remplacer `<?php $this->end(); ?>` par `{{ end }}`

> **⚠️ Important** : La syntaxe `{{ extend('path') }}` requiert des **parenthèses**. `{{ extend 'path' }}` sans parenthèses ne fonctionne pas.

---

## 📁 Extension de fichiers `.ogan`

Les templates Ogan utilisent l'extension **`.ogan`** pour une meilleure identification et intégration avec les éditeurs.

### Structure des fichiers

```
templates/
├── layouts/
│   └── base.ogan
├── components/
│   ├── alert.ogan
│   ├── flashes.ogan
│   ├── card.ogan
│   └── navbar.ogan
├── home/
│   └── index.ogan
└── user/
    ├── list.ogan
    ├── show.ogan
    └── edit.ogan
```

### Configuration VS Code

Pour une coloration syntaxique optimale, consultez le guide [Configuration VS Code](vscode-setup.md).

### Rétrocompatibilité

Le framework supporte toujours l'ancienne extension `.html.php` en fallback. L'ordre de résolution est :
1. `.ogan` (prioritaire)
2. `.html.php` (rétrocompatibilité)

---

## 🔄 Structures de Contrôle `{% %}`

Les structures de contrôle utilisent la syntaxe `{% %}` inspirée de Twig :

### Boucles `{% for %}`

```html
{% for user in users %}
    <p>{{ user.name }}</p>
{% endfor %}
```

**Avec clé et valeur :**
```html
{% for type, messages in getAllFlashes() %}
    {% for message in messages %}
        <div>{{ message }}</div>
    {% endfor %}
{% endfor %}
```

### Conditions `{% if %}`

```html
{% if isAdmin %}
    <p>Admin</p>
{% elseif isModerator %}
    <p>Modérateur</p>
{% else %}
    <p>Utilisateur</p>
{% endif %}
```

### Tableau récapitulatif

| Syntaxe | Transformation |
|---------|----------------|
| `{% for item in items %}` | `foreach ($items as $item):` |
| `{% for key, value in items %}` | `foreach ($items as $key => $value):` |
| `{% endfor %}` | `endforeach;` |
| `{% if condition %}` | `if ($condition):` |
| `{% elseif condition %}` | `elseif ($condition):` |
| `{% else %}` | `else:` |
| `{% endif %}` | `endif;` |

### Avantages

- ✅ Syntaxe propre et lisible
- ✅ Variables sans `$` (automatiquement ajouté)
- ✅ Compatible avec la coloration Twig dans les éditeurs

---

**Note** : Cette fonctionnalité est activée par défaut. Pour la désactiver, mettez `'use_compiler' => false` dans `config/parameters.php`.


