# 📄 Guide de Pagination

> Paginatez facilement vos résultats de base de données avec Ogan Framework.

## 🚀 Utilisation Rapide

### Dans le Contrôleur

```php
use App\Model\User;

class UserController extends AbstractController
{
    public function index()
    {
        // Pagine avec 15 éléments par page
        // La page courante est auto-détectée depuis ?page=N
        $users = User::paginate(15);
        
        return $this->render('user/index.ogan', [
            'users' => $users
        ]);
    }
}
```

### Dans le Template

```html
<table class="w-full">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>
        {% foreach users as user %}
        <tr>
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
        </tr>
        {% endforeach %}
    </tbody>
</table>

<!-- Affiche les liens de pagination (Tailwind CSS) -->
{{ users.links()|raw }}
```

---

## 📚 API du Paginator

### Propriétés

| Méthode | Description |
|---------|-------------|
| `items()` | Tableau des éléments de la page courante |
| `total()` | Nombre total d'éléments |
| `perPage()` | Nombre d'éléments par page |
| `currentPage()` | Numéro de la page courante |
| `lastPage()` | Numéro de la dernière page |
| `count()` | Nombre d'éléments sur cette page |
| `hasPages()` | `true` s'il y a plus d'une page |
| `getSimpleRange()` | Tableau `[1, 2, 3, ...]` pour itération |

### Navigation

| Méthode | Description |
|---------|-------------|
| `hasMorePages()` | `true` s'il y a des pages suivantes |
| `hasPreviousPages()` | `true` s'il y a des pages précédentes |
| `onFirstPage()` | `true` si on est sur la première page |
| `onLastPage()` | `true` si on est sur la dernière page |
| `firstItem()` | Index du premier élément affiché |
| `lastItem()` | Index du dernier élément affiché |

### URLs

| Méthode | Description |
|---------|-------------|
| `url(int $page)` | URL vers une page spécifique |
| `previousPageUrl()` | URL de la page précédente (ou `null`) |
| `nextPageUrl()` | URL de la page suivante (ou `null`) |

### Rendu

| Méthode | Description |
|---------|-------------|
| `links()` | HTML complet des liens de pagination (Tailwind) |
| `linksHtmx()` | Liens avec attributs HTMX intégrés |
| `linksPageNumbersHtmx()` | Numéros de page avec HTMX (pour templates) |
| `toArray()` | Données de pagination en tableau (pour API JSON) |

---

## 🔧 Pagination avec QueryBuilder

Vous pouvez aussi paginer depuis le `QueryBuilder` directement :

```php
use Ogan\Database\QueryBuilder;

$users = QueryBuilder::table('users')
    ->where('active', '=', true)
    ->orderBy('created_at', 'DESC')
    ->paginate(20);
```

> **Note** : Avec `QueryBuilder`, les résultats sont des tableaux associatifs.  
> Avec `Model::paginate()`, les résultats sont des instances hydratées du modèle.

---

## 🎨 Personnalisation du Rendu

Le HTML généré par `links()` utilise Tailwind CSS. Si vous souhaitez personnaliser :

```php
// Récupérer les données et faire votre propre rendu
$paginator = User::paginate(15);

// Utiliser les méthodes individuelles
if ($paginator->hasPreviousPages()) {
    echo '<a href="' . $paginator->previousPageUrl() . '">← Précédent</a>';
}

for ($i = 1; $i <= $paginator->lastPage(); $i++) {
    $class = ($i === $paginator->currentPage()) ? 'active' : '';
    echo '<a href="' . $paginator->url($i) . '" class="' . $class . '">' . $i . '</a>';
}

if ($paginator->hasMorePages()) {
    echo '<a href="' . $paginator->nextPageUrl() . '">Suivant →</a>';
}
```

---

## 🎨 Templates Personnalisés

Vous pouvez créer des templates de pagination entièrement personnalisés dans votre projet. Le `Paginator` cherche automatiquement les templates dans `templates/pagination/` de votre application **avant** d'utiliser ceux du framework.

### Structure des Templates

Créez un fichier dans `templates/pagination/` avec l'extension `.ogan` :

```
templates/
└── pagination/
    ├── htmx.ogan      # Override du template HTMX par défaut
    ├── tailwind.ogan  # Override du template Tailwind
    └── custom.ogan    # Votre propre template
```

### Variables Disponibles

Dans vos templates, vous avez accès à :

| Variable | Type | Description |
|----------|------|-------------|
| `paginator` | `Paginator` | L'objet paginator complet |
| `pages` | `array` | Tableau d'objets page pré-calculés |
| `target` | `string` | Sélecteur CSS cible (pour HTMX) |
| `swap` | `string` | Type de swap HTMX |

### Structure de l'Objet Page

Chaque élément du tableau `pages` est un objet avec :

| Propriété | Type | Description |
|-----------|------|-------------|
| `page.type` | `string` | `'current'`, `'normal'`, ou `'ellipsis'` |
| `page.number` | `int` | Numéro de la page |
| `page.url` | `string` | URL de la page (vide pour ellipsis) |

### Exemple de Template HTMX Personnalisé

```html
{# templates/pagination/htmx.ogan #}
{% if paginator.hasPages() %}
<nav role="navigation" aria-label="Pagination" class="flex items-center justify-between mt-8">
    
    <!-- Infos -->
    <p class="text-sm text-gray-500">
        Page {{ paginator.currentPage() }} sur {{ paginator.lastPage() }}
    </p>

    <!-- Liens -->
    <div class="flex items-center gap-2">
        {# Précédent #}
        {% if paginator.onFirstPage() %}
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">←</span>
        {% else %}
            <a href="{{ paginator.previousPageUrl() }}"
               hx-get="{{ paginator.previousPageUrl() }}"
               hx-target="{{ target }}"
               hx-swap="{{ swap }}"
               hx-disinherit="*"
               class="px-3 py-2 hover:bg-gray-100 rounded">←</a>
        {% endif %}

        {# Numéros de page #}
        {% for page in pages %}
            {% if page.type == 'ellipsis' %}
                <span class="px-3 py-2">...</span>
            {% elseif page.type == 'current' %}
                <span class="px-3 py-2 bg-primary text-white rounded">{{ page.number }}</span>
            {% else %}
                <a href="{{ page.url }}"
                   hx-get="{{ page.url }}"
                   hx-target="{{ target }}"
                   hx-swap="{{ swap }}"
                   hx-disinherit="*"
                   class="px-3 py-2 hover:bg-gray-100 rounded">{{ page.number }}</a>
            {% endif %}
        {% endfor %}

        {# Suivant #}
        {% if paginator.hasMorePages() %}
            <a href="{{ paginator.nextPageUrl() }}"
               hx-get="{{ paginator.nextPageUrl() }}"
               hx-target="{{ target }}"
               hx-swap="{{ swap }}"
               hx-disinherit="*"
               class="px-3 py-2 hover:bg-gray-100 rounded">→</a>
        {% else %}
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">→</span>
        {% endif %}
    </div>
</nav>
{% endif %}
```

### Utilisation

```php
// Dans le controller
$articles = Article::paginate(15);

// Dans le template - utilise automatiquement votre templates/pagination/htmx.ogan
{{ articles.linksHtmx('#articles-list')|raw }}

// Ou spécifiez un template personnalisé
{{ articles.links('custom')|raw }}
```

> [!TIP]
> **hx-disinherit="*"** : Ajoutez cet attribut sur les liens de pagination pour éviter qu'ils héritent des attributs `hx-select` ou autres du `<body>`. C'est particulièrement important si votre layout utilise `hx-boost="true"` avec `hx-select`.

---

## ⚡ Support HTMX

### Configuration HTMX

Dans votre layout (`base.ogan`), placez `htmx_script()` **à la fin du `<body>`** :

```html
<body>
    <!-- Contenu de la page -->
    {{ section('body') }}
    
    <!-- HTMX en fin de body pour initialisation correcte -->
    {{ htmx_script() }}
</body>
```

> [!IMPORTANT]
> Placer le script dans le `<head>` empêche HTMX de traiter les éléments correctement.

### Barre de Progression Automatique

Quand HTMX est activé, une barre de progression bleue apparaît automatiquement en haut de la page lors des requêtes HTMX.

**Configuration** (`config/parameters.yaml`) :
```yaml
frontend:
  htmx:
    enabled: true
    progress_bar: true  # Désactiver avec false
```

### Liens de Pagination HTMX

Utilisez `linksHtmx()` ou le template `htmx` :

```html
<!-- Option 1 : Méthode linksHtmx() -->
<div id="content">
    <table>...</table>
    {{ users.linksHtmx('#content', 'innerHTML')|raw }}
</div>

<!-- Option 2 : Template htmx -->
<div id="content">
    <table>...</table>
    {{ users.links('htmx')|raw }}
</div>
```

### Contrôleur avec Réponse Partielle

Pour éviter la duplication du layout lors des requêtes HTMX :

```php
use Ogan\View\Helper\HtmxHelper;

class UserController extends AbstractController
{
    public function index()
    {
        $users = User::paginate(15);
        
        // Requête HTMX : retourner seulement le contenu
        if (HtmxHelper::isHtmxRequest()) {
            return $this->render('user/_list_partial.ogan', [
                'users' => $users
            ]);
        }
        
        // Requête normale : page complète
        return $this->render('user/index.ogan', [
            'users' => $users
        ]);
    }
}
```

**Paramètres de `linksHtmx()`** :
| Paramètre | Description | Défaut |
|-----------|-------------|--------|
| `$target` | Sélecteur CSS cible | `#content` |
| `$swap` | Type de swap HTMX | `innerHTML` |

Les liens générés incluent automatiquement : `hx-get`, `hx-target`, `hx-swap`, `hx-push-url`.

---

## 📁 Templates Externes

Utilisez des templates `.ogan` personnalisés :

```php
// Utiliser un template prédéfini
{{ users.links('simple')|raw }}      // simple.ogan : ← Page 1/5 →
{{ users.links('tailwind')|raw }}    // tailwind.ogan : Style Tailwind complet
{{ users.links('htmx')|raw }}        // htmx.ogan : Avec attributs HTMX

// Utiliser un chemin complet
{{ users.links('/templates/custom-pagination.ogan')|raw }}
```

**Templates disponibles** (`templates/pagination/`) :
| Fichier | Description |
|---------|-------------|
| `simple.ogan` | Minimal : ← Page X/Y → |
| `tailwind.ogan` | Style Tailwind complet |
| `htmx.ogan` | Tailwind + attributs HTMX |
