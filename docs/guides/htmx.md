# ⚡ Guide HTMX - Ogan Framework

> Apprenez à créer des interfaces dynamiques et réactives avec HTMX, intégré nativement dans Ogan Framework.

## 🎯 Introduction

HTMX vous permet d'accéder aux fonctionnalités modernes du navigateur (AJAX, Transitions CSS, WebSockets) directement depuis HTML, sans écrire de JavaScript complexe.

Ogan Framework intègre HTMX nativement avec :
- ✅ **Helpers de vue** pour inclure le script
- ✅ **Détection côté serveur** des requêtes HTMX
- ✅ **Helpers de réponse** pour renvoyer des fragments HTML
- ✅ **Support dans `make:auth`** pour des dashboards dynamiques

---

## 🚀 Installation & Activation

### 1. Configuration

Activez HTMX dans `config/parameters.yaml` :

```yaml
frontend:
  htmx:
    enabled: true      # Active les helpers HTMX
    progress_bar: true # Barre de progression automatique
    script: /assets/js/htmx.min.js
```

### 2. Inclusion du Script

Ajoutez le helper `{{ htmx_script() }}` **à la fin du `<body>`** de votre layout :

```html
<body hx-boost="true" hx-target="#page-content" hx-swap="innerHTML" hx-select="#page-content">

    <div id="page-content">
        {{ component('navbar') }}
        <main>{{ section('body') }}</main>
        {{ component('footer') }}
    </div>

    <!-- HTMX en fin de body -->
    {{ htmx_script() }}
</body>
```

> [!IMPORTANT]
> Le wrapper `#page-content` est essentiel pour que la barre de progression reste visible pendant les transitions.

### 3. Barre de Progression

Une barre de progression bleue apparaît automatiquement en haut de la page lors des requêtes HTMX.

Pour la désactiver :
```yaml
frontend:
  htmx:
    progress_bar: false
```

---

## 🛠️ Utilisation Basique

### Requêtes AJAX simples

Utilisez les attributs `hx-*` pour déclencher des requêtes :

```html
<!-- Clic -> GET /users -> Remplace le contenu de #result -->
<button hx-get="{{ route('user_list') }}" hx-target="#result">
    Charger les utilisateurs
</button>

<div id="result"></div>
```

### Navigation Boostée (`hx-boost`)

Transforme vos liens et formulaires classiques en requêtes AJAX pour une navigation ultra-rapide (comme une SPA).

```html
<body hx-boost="true">
    <nav>
        <a href="{{ route('home') }}">Accueil</a>
        <a href="{{ route('contact') }}">Contact</a>
    </nav>
    
    <main>
        <!-- Le contenu changera sans rechargement complet -->
        {{ section('content') }}
    </main>
</body>
```

> **Note :** Si vous utilisez `hx-boost` sur le `<body>`, assurez-vous que vos scripts JS sont compatibles (rechargement d'événements).

---

## 🧩 Patterns Courants

### 1. Recherche Active (Active Search)

Rechercher pendant la frappe utilisateur :

```html
<input type="text" 
       name="q"
       hx-get="{{ route('search') }}" 
       hx-trigger="keyup changed delay:500ms" 
       hx-target="#search-results" 
       placeholder="Rechercher...">

<div id="search-results"></div>
```

Côté Contrôleur :

```php
public function search(Request $request)
{
    $query = $request->input('q');
    $results = User::where('name', 'LIKE', "%$query%")->get();
    
    // Si c'est une requête HTMX, on renvoie seulement la liste (fragment)
    if ($request->isHtmx()) {
        return $this->render('user/partials/_list.ogan', ['users' => $results]);
    }
    
    // Sinon page complète
    return $this->render('user/search.ogan', ['users' => $results]);
}
```

### 2. Édition en Ligne (Click to Edit)

```html
<div hx-target="this" hx-swap="outerHTML">
    <div>
        <label>Nom : {{ user.name }}</label>
        <button hx-get="{{ route('user_edit_inline', ['id' => user.id]) }}">
            Modifier
        </button>
    </div>
</div>
```

Le contrôleur renvoie un formulaire qui remplace la div. Le formulaire, une fois soumis, renvoie la div mise à jour.

### 3. Suppression d'une ligne

```html
<tr>
    <td>{{ user.name }}</td>
    <td>
        <button hx-delete="{{ route('user_delete', ['id' => user.id]) }}"
                hx-confirm="Êtes-vous sûr ?"
                hx-target="closest tr"
                hx-swap="outerHTML">
            Supprimer
        </button>
    </td>
</tr>
```

Le contrôleur effectue la suppression et renvoie une réponse vide (ou 200 OK) pour faire disparaître la ligne.

---

## 🔧 API Framework

### Helpers de Template

- `{{ htmx_script() }}` : Affiche la balise `<script>` si activé.
- `htmx_enabled()` : Retourne `true` si HTMX est activé dans la config.
- `htmx_request()` : Retourne `true` si la requête courante est une requête HTMX.

Exemple conditionnel :

```html
{% if not htmx_request() %}
    {{ extend('layouts/base.ogan') }}
{% endif %}

{{ start('content') }}
    <!-- Contenu de la page -->
{{ end }}
```

### Dans les Contrôleurs

L'objet `Request` possède une méthode `isHtmx()` :

```php
public function index(Request $request)
{
    if ($request->isHtmx()) {
        // Logique spécifique HTMX (ex: désactiver le layout)
        // ...
    }
}
```

---

## ⚠️ Pièges & Astuces

### 1. `hx-boost` et Dropdowns
Évitez de mettre `hx-boost="true"` sur des conteneurs qui ont des interactions JS complexes (comme des menus déroulants ou des modales), car HTMX intercepte les clics.
*Le générateur `make:auth` gère cela automatiquement.*

### 2. Redirections HTMX
Pour rediriger le navigateur complet depuis une réponse HTMX, utilisez l'en-tête `HX-Redirect` (le framework le gère souvent nativement via `redirect()` si détecté, ou manuellement).

### 3. Debug
Utilisez l'extension navigateur **HTMX Debugger** ou inspectez l'onglet Réseau pour voir les requêtes/réponses partielles.

---

---

## 🔄 Mises à jour Multiples (Out of Band Swaps)

Parfois, une action (ex: soumission de formulaire) doit mettre à jour plusieurs parties de la page qui ne sont pas adjacentes (ex: le résultat du formulaire + les messages flash en haut de page).

HTMX permet cela avec l'attribut `hx-swap-oob`.

### Exemple : Messages Flash

Dans votre partial de réponse (`_result.ogan`), incluez le contenu principal + le bloc flash avec `hx-swap-oob="true"` :

```html
<!-- Contenu principal (injecté dans la cible hx-target) -->
<div class="result">
    Opération réussie !
</div>

<!-- Contenu secondaire (injecté dans l'élément id="flashes-container") -->
{{ component('flashes', ['oob' => true]) }}
```

Le composant `flashes` d'Ogan gère automatiquement l'attribut `hx-swap-oob="true"` lorsqu'on lui passe `oob: true`.
Notez que la page doit contenir un élément `<div id="flashes-container">` pour que cela fonctionne.

---

## 📚 Ressources

- [Documentation Officielle HTMX](https://htmx.org/docs/)
- [Exemples HTMX](https://htmx.org/examples/)
- [Tutoriel : CRUD Articles avec HTMX](./htmx-crud-example.md)
