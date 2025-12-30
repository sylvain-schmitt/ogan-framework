# 🎨 Moteur de Templates & Vues

Ce guide couvre la syntaxe des templates Ogan (`.ogan`), l'héritage, et les helpers disponibles.

## 📋 Table des matières

- [Syntaxe de base](#syntaxe-de-base)
- [Structures de contrôle](#structures-de-contrôle)
- [Héritage (Layouts)](#héritage-layouts)
- [Helpers de Vue](#helpers-de-vue)
    - [URLs & Routes](#urls--routes)
    - [Assets, CSS & JS](#assets-css--js)
    - [Composants](#composants)
    - [Sécurité (CSRF)](#sécurité-csrf)

---

## Syntaxe de base

Le framework utilise une syntaxe inspirée de Twig/Blade.

**Affichage de variables (échappé par défaut) :**
```html
<h1>{{ page_title }}</h1>
<p>Bienvenue {{ user.name }}</p>
```

**Affichage brut (NON échappé) :**
> ⚠️ Attention aux risques XSS ! N'utilisez ceci que pour du contenu de confiance.
```html
{{! content_html }}
```

** Expressions PHP :**
Les expressions complexes sont supportées.
```html
{{ user.name|upper }}   <!-- Filtres -->
{{ $count + 1 }}        <!-- Expressions PHP -->
{{ time() }}            <!-- Fonctions PHP -->
```

**Variable Globale `app` :**
L'objet `app` est accessible partout.
```html
{{ app.user.email }}      <!-- Utilisateur connecté -->
{{ app.request.uri }}     <!-- URL courante -->
{{ app.debug ? 'DEBUG' }} <!-- Mode debug -->
```

---

## Structures de contrôle

Utilisez `{% ... %}` pour la logique.

**Conditions :**
```html
{% if app.user %}
    <p>Bonjour {{ app.user.name }}</p>
{% elseif some_condition %}
    <p>Autre chose</p>
{% else %}
    <a href="{{ route('login') }}">Connexion</a>
{% endif %}
```

**Boucles :**
```html
<ul>
{% for item in items %}
    <li>{{ item.name }}</li>
{% endfor %}
</ul>

<!-- Avec clé/valeur -->
{% for key, val in data %}
    {{ key }}: {{ val }}
{% endfor %}
```

---

## Héritage (Layouts)

L'héritage permet de définir une structure commune (Layout) réutilisée par plusieurs pages.

**1. Le Parent (`templates/layouts/base.ogan`)** :
```html
<!DOCTYPE html>
<html>
<head>
    <title>{{ title ?? 'Mon Site' }}</title>
</head>
<body>
    <nav>...</nav>
    
    <!-- Zone de contenu -->
    {{ section('body') }}
    
    <footer>...</footer>
</body>
</html>
```

**2. L'Enfant (`templates/home.ogan`)** :
```html
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
    <h1>Accueil</h1>
    <p>Ceci est injecté dans le layout.</p>
{{ end }}
```

---

## Helpers de Vue

Des fonctions helper sont disponibles pour simplifier les tâches courantes.

### URLs & Routes

Ne jamais hardcoder les URLs ! Utilisez les helpers.

**`route(name, params = [])`** : Génère une URL depuis le nom de la route.
```html
<!-- Lien vers la route 'user_show' avec paramètre id -->
<a href="{{ route('user_show', ['id' => 42]) }}">Profil</a>

<!-- Lien absolu (http://...) -->
<a href="{{ url('/admin', true) }}">Admin</a>
```

### Assets, CSS & JS

Gérez vos ressources statiques facilement.

**`asset(path)`** : Lien vers un fichier dans `public/`.
```html
<img src="{{ asset('img/logo.png') }}" alt="Logo">
```

**`css(path)` et `js(path)`** : Génèrent les balises `<link>` et `<script>`.
```html
{{ css('css/style.css') }}
{{ js('js/app.js') }}

<!-- Avec attributs -->
{{ js('js/chart.js', ['defer' => true]) }}
```

### Composants

Inclure des fragments de template réutilisables (`templates/components/`).

```html
<!-- Inclut templates/components/alert.ogan -->
{{ component('alert', ['type' => 'danger', 'message' => 'Erreur !']) }}
```

### Sécurité (CSRF)

Protection contre les attaques Cross-Site Request Forgery.

**`csrf_input()`** : Génère le champ caché complet.
```html
<form method="POST" action="...">
    {{ csrf_input() }}
    <!-- ... -->
</form>
```

**`csrf_token(id)`** : Retourne juste le token (pour usage JS/API).
```html
<meta name="csrf-token" content="{{ csrf_token('form') }}">
```

### HTMX

Si activé, ce helper injecte le script et la configuration requise.
```html
<!-- À mettre en bas du layout -->
{{ htmx_script() }}
```

---

## Extension de fichiers

*   Les templates utilisent l'extension **`.ogan`**.
*   Les anciens fichiers `.html.php` sont toujours supportés pour rétrocompatibilité.
