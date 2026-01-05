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
        // Pagine avec 15 éléments par page, triés par date de création (plus récent d'abord)
        // La page courante est auto-détectée depuis ?page=N
        $users = User::latest()->paginate(15);
        
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

## ⚡ Pagination HTMX

Ogan Framework intègre une solution robuste pour la pagination HTMX qui contourne les bugs connus de HTMX 2.0.8.

### Prérequis

1. Activer HTMX dans `config/parameters.yaml` :

```yaml
frontend:
  htmx:
    enabled: true
    progress_bar: true
```

2. Ajouter `{{ htmx_script() }}` à la fin du `<body>` de votre layout.

> [!IMPORTANT]
> Le framework injecte automatiquement le fix de pagination HTMX quand `htmx.enabled: true`. Aucun JavaScript supplémentaire n'est requis.

### Génération Automatique

Utilisez la commande `make:pagination` avec l'option `--htmx` :

```bash
php bin/console make:pagination Article --htmx
```

Cela génère :
- `templates/article/list.ogan` : Page principale avec wrapper simple
- `templates/article/_list_partial.ogan` : Partial avec `data-htmx-paginated`

### Structure Manuelle

#### 1. Controller

```php
use Ogan\View\Helper\HtmxHelper;

#[Route('/articles', methods: ['GET'])]
public function index(): Response
{
    $articles = Article::orderBy('created_at', 'desc')->paginate(15);

    // Requête HTMX (non-boostée) → retourne le partial
    if (HtmxHelper::isHtmxRequest() && !$this->request->getHeader('HX-Boosted')) {
        return $this->render('article/_list_partial', [
            'articles' => $articles
        ]);
    }

    // Requête normale → page complète
    return $this->render('article/list', ['articles' => $articles]);
}
```

#### 2. Template Principal (list.ogan)

```html
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="container mx-auto">
    <h1>Articles</h1>

    <!-- Zone de la liste - PAS d'attributs HTMX ici -->
    <div id="articles-list">
        {{ component('article/_list_partial', ['articles' => articles]) }}
    </div>
</div>
{{ end }}
```

#### 3. Partial (_list_partial.ogan)

```html
<div id="articles-list" data-htmx-paginated hx-boost="false">
{% if showFlashOob ?? false %}{{ component('flashes', ['oob' => true]) }}{% endif %}
<div class="bg-white rounded-lg shadow">
    <table>
        {% for article in articles %}
        <tr>
            <td>{{ article.title }}</td>
        </tr>
        {% endfor %}
    </table>
</div>
<div class="mt-6">
    {{ articles.linksHtmx('#articles-list')|raw }}
</div>
</div>
```

> [!CAUTION]
> **Points critiques :**
> - Le partial DOIT inclure son propre wrapper avec le même ID
> - `data-htmx-paginated` active le fix de pagination automatique
> - `hx-boost="false"` empêche le boost global d'interférer
> - Le partial ne DOIT PAS commencer par des lignes vides ou commentaires

### Paramètres de `linksHtmx()`

| Paramètre | Description | Obligatoire |
|-----------|-------------|-------------|
| `$target` | Sélecteur CSS cible (ex: `#articles-list`) | ✅ Oui |
| `$swap` | Type de swap HTMX (défaut: `outerHTML`) | Non |

### Messages Flash avec OOB

Pour afficher des messages flash après une action CRUD, le partial peut les recevoir via OOB (Out Of Band) :

```php
// Dans le controller après création/modification
return $this->render('article/_list_partial', [
    'articles' => $articles,
    'showFlashOob' => true  // Active l'envoi OOB des flashes
]);
```

Le flash sera injecté dans l'élément `#flash-messages` de votre layout via `hx-swap-oob`.

### Schéma du Flux

```
┌─ Clic pagination (page 2) ─────────────────────────────────────┐
│  hx-get="/articles?page=2"                                      │
│  hx-target="#articles-list"                                     │
│  hx-swap="outerHTML"                                            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─ Serveur détecte HX-Request ───────────────────────────────────┐
│  → Retourne _list_partial.ogan (sans layout)                   │
│  → Le partial contient <div id="articles-list" ...>            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─ Fix JS intercepte le swap ────────────────────────────────────┐
│  → htmx:beforeSwap déclenché                                   │
│  → target.outerHTML = response (swap manuel)                   │
│  → htmx.process(newElement) initialise les nouveaux liens      │
└─────────────────────────────────────────────────────────────────┘
```

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
