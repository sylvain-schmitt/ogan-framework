# CRUD Articles avec HTMX - Guide pour l'Agent IA

Ce document explique comment implémenter un CRUD complet avec HTMX dans le framework Ogan, en utilisant des **partials** pour éviter les rechargements complets de page.

## 🎯 Principe de Base

HTMX permet de faire des requêtes AJAX et de remplacer uniquement une partie du DOM. Les règles clés :

1. **Requêtes HTMX** : Détectées via le header `HX-Request`
2. **Partials** : Retournent uniquement le fragment HTML (sans layout)
3. **Pages complètes** : Retournent la page avec le layout complet

---

## 📁 Structure des Fichiers

```
src/
├── Controller/
│   └── Admin/
│       └── ArticleController.php
├── Model/
│   └── Article.php
└── Form/
    └── ArticleType.php

templates/
├── admin/
│   └── article/
│       ├── index.ogan           # Page complète avec layout
│       ├── create.ogan          # Page création
│       ├── edit.ogan            # Page édition
│       └── _partials/
│           ├── _list.ogan       # Fragment liste (tableau)
│           ├── _row.ogan        # Fragment ligne (une seule ligne)
│           └── _form.ogan       # Fragment formulaire
```

---

## 🎮 Controller avec Support HTMX

```php
<?php
// src/Controller/Admin/ArticleController.php

namespace App\Controller\Admin;

use App\Model\Article;
use App\Form\ArticleType;
use Ogan\Controller\AbstractController;
use Ogan\Http\Response;
use Ogan\Router\Attributes\Route;

class ArticleController extends AbstractController
{
    // ══════════════════════════════════════════════════════════════════════
    // 📋 INDEX - Liste des articles
    // ══════════════════════════════════════════════════════════════════════
    
    #[Route(path: '/admin/articles', methods: ['GET'], name: 'admin_articles_index')]
    public function index(): Response
    {
        $articles = Article::all();
        
        // Si c'est une requête HTMX, retourner uniquement le partial
        if ($this->isHtmxRequest()) {
            return $this->partial('admin/article/_partials/_list.ogan', [
                'articles' => $articles
            ]);
        }
        
        // Sinon, retourner la page complète avec layout
        return $this->render('admin/article/index.ogan', [
            'articles' => $articles,
            'title' => 'Gestion des Articles'
        ]);
    }
    
    // ══════════════════════════════════════════════════════════════════════
    // ➕ CREATE - Création d'un article
    // ══════════════════════════════════════════════════════════════════════
    
    #[Route(path: '/admin/articles/create', methods: ['GET', 'POST'], name: 'admin_articles_create')]
    public function create(): Response
    {
        $article = new Article();
        $form = $this->formFactory->create(ArticleType::class, $article);
        
        $form->handleRequest($this->request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $article->save();
            $this->addFlash('success', 'Article créé avec succès !');
            
            // Requête HTMX : retourner la nouvelle ligne + trigger refresh
            if ($this->isHtmxRequest()) {
                return $this->partial('admin/article/_partials/_row.ogan', [
                    'article' => $article
                ])->withHeader('HX-Trigger', 'articleCreated');
            }
            
            return $this->redirect('/admin/articles');
        }
        
        // Afficher le formulaire (partial ou page complète)
        if ($this->isHtmxRequest()) {
            return $this->partial('admin/article/_partials/_form.ogan', [
                'form' => $form->createView(),
                'action' => '/admin/articles/create',
                'submitLabel' => 'Créer'
            ]);
        }
        
        return $this->render('admin/article/create.ogan', [
            'form' => $form->createView(),
            'title' => 'Nouvel Article'
        ]);
    }
    
    // ══════════════════════════════════════════════════════════════════════
    // ✏️ EDIT - Modification d'un article
    // ══════════════════════════════════════════════════════════════════════
    
    #[Route(path: '/admin/articles/{id}/edit', methods: ['GET', 'POST'], name: 'admin_articles_edit')]
    public function edit(int $id): Response
    {
        $article = Article::find($id);
        
        if (!$article) {
            $this->addFlash('error', 'Article non trouvé');
            return $this->redirect('/admin/articles');
        }
        
        $form = $this->formFactory->create(ArticleType::class, $article);
        $form->handleRequest($this->request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $article->save();
            $this->addFlash('success', 'Article modifié avec succès !');
            
            // HTMX : retourner la ligne mise à jour
            if ($this->isHtmxRequest()) {
                return $this->partial('admin/article/_partials/_row.ogan', [
                    'article' => $article
                ])->withHeader('HX-Trigger', 'articleUpdated');
            }
            
            return $this->redirect('/admin/articles');
        }
        
        if ($this->isHtmxRequest()) {
            return $this->partial('admin/article/_partials/_form.ogan', [
                'form' => $form->createView(),
                'article' => $article,
                'action' => "/admin/articles/{$id}/edit",
                'submitLabel' => 'Mettre à jour'
            ]);
        }
        
        return $this->render('admin/article/edit.ogan', [
            'form' => $form->createView(),
            'article' => $article,
            'title' => 'Modifier l\'Article'
        ]);
    }
    
    // ══════════════════════════════════════════════════════════════════════
    // 🗑️ DELETE - Suppression d'un article
    // ══════════════════════════════════════════════════════════════════════
    
    #[Route(path: '/admin/articles/{id}/delete', methods: ['DELETE', 'POST'], name: 'admin_articles_delete')]
    public function delete(int $id): Response
    {
        $article = Article::find($id);
        
        if ($article) {
            $article->delete();
            $this->addFlash('success', 'Article supprimé');
        }
        
        // HTMX : retourner une réponse vide avec trigger
        if ($this->isHtmxRequest()) {
            return $this->response
                ->setContent('')
                ->withHeader('HX-Trigger', 'articleDeleted');
        }
        
        return $this->redirect('/admin/articles');
    }
    
    // ══════════════════════════════════════════════════════════════════════
    // 🔧 HELPERS HTMX
    // ══════════════════════════════════════════════════════════════════════
    
    /**
     * Détecte si la requête vient de HTMX
     */
    protected function isHtmxRequest(): bool
    {
        return $this->request->getHeader('HX-Request') === 'true';
    }
    
    /**
     * Retourne un partial (sans layout)
     */
    protected function partial(string $template, array $params = []): Response
    {
        $content = $this->view->render($template, $params);
        return $this->response->setContent($content);
    }
}
```

---

## 📄 Templates

### 1. Page Index Complète (`index.ogan`)

```twig
{% extend 'layouts/admin.ogan' %}

{% section body %}
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Gestion des Articles
        </h1>
        
        <!-- Bouton qui charge le formulaire en modal/aside via HTMX -->
        <button 
            hx-get="/admin/articles/create"
            hx-target="#modal-content"
            hx-swap="innerHTML"
            class="btn btn-primary"
            @click="$dispatch('open-modal')"
        >
            + Nouvel Article
        </button>
    </div>
    
    <!-- Zone de la liste - sera mise à jour par HTMX -->
    <div id="articles-list" 
         hx-trigger="articleCreated from:body, articleUpdated from:body, articleDeleted from:body"
         hx-get="/admin/articles"
         hx-swap="innerHTML">
        {{ include('admin/article/_partials/_list.ogan', { articles: articles }) }}
    </div>
</div>

<!-- Container pour la modal -->
<div id="modal-content"></div>
{% endsection %}
```

### 2. Partial Liste (`_partials/_list.ogan`)

```twig
{# 
    Ce partial retourne UNIQUEMENT le tableau, sans layout.
    Utilisé par HTMX pour rafraîchir la liste sans recharger la page.
#}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Titre
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody id="articles-tbody" class="divide-y divide-gray-200 dark:divide-gray-700">
            {% for article in articles %}
                {{ include('admin/article/_partials/_row.ogan', { article: article }) }}
            {% endfor %}
            
            {% if articles is empty %}
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        Aucun article trouvé
                    </td>
                </tr>
            {% endif %}
        </tbody>
    </table>
</div>
```

### 3. Partial Ligne (`_partials/_row.ogan`)

```twig
{#
    Une seule ligne du tableau.
    Utilisé pour :
    - Afficher chaque article dans la liste
    - Remplacer une ligne après modification (via hx-swap-oob)
#}
<tr id="article-{{ article.id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700">
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center">
            <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ article.title }}
            </div>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        {% if article.isPublished %}
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                Publié
            </span>
        {% else %}
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                Brouillon
            </span>
        {% endif %}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        {{ article.createdAt|date('d/m/Y H:i') }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
        <!-- Éditer via HTMX -->
        <button 
            hx-get="/admin/articles/{{ article.id }}/edit"
            hx-target="#modal-content"
            hx-swap="innerHTML"
            class="text-blue-600 hover:text-blue-900"
        >
            Éditer
        </button>
        
        <!-- Supprimer via HTMX avec confirmation -->
        <button 
            hx-delete="/admin/articles/{{ article.id }}/delete"
            hx-confirm="Êtes-vous sûr de vouloir supprimer cet article ?"
            hx-target="#article-{{ article.id }}"
            hx-swap="outerHTML"
            class="text-red-600 hover:text-red-900"
        >
            Supprimer
        </button>
    </td>
</tr>
```

### 4. Partial Formulaire (`_partials/_form.ogan`)

```twig
{#
    Formulaire réutilisable pour création et édition.
    Peut être affiché dans une modal ou une page complète.
#}
<form 
    hx-post="{{ action }}"
    hx-target="#articles-list"
    hx-swap="innerHTML"
    class="space-y-6 p-6 bg-white dark:bg-gray-800 rounded-lg"
>
    {{ csrf() }}
    
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Titre
        </label>
        {{ form.title }}
        {% if form.title.errors %}
            <p class="mt-1 text-sm text-red-600">{{ form.title.errors|first }}</p>
        {% endif %}
    </div>
    
    <div>
        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Contenu
        </label>
        {{ form.content }}
        {% if form.content.errors %}
            <p class="mt-1 text-sm text-red-600">{{ form.content.errors|first }}</p>
        {% endif %}
    </div>
    
    <div class="flex items-center">
        <input 
            type="checkbox" 
            name="is_published" 
            id="is_published"
            {{ article.isPublished ? 'checked' : '' }}
            class="h-4 w-4 text-blue-600 rounded"
        >
        <label for="is_published" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            Publier immédiatement
        </label>
    </div>
    
    <div class="flex justify-end space-x-3">
        <button 
            type="button" 
            @click="$dispatch('close-modal')"
            class="btn btn-secondary"
        >
            Annuler
        </button>
        <button type="submit" class="btn btn-primary">
            {{ submitLabel }}
        </button>
    </div>
</form>
```

---

## 🔄 Patterns HTMX Courants

### 1. Rafraîchir une liste après action

```html
<!-- Le conteneur écoute les événements et se rafraîchit -->
<div id="articles-list" 
     hx-trigger="articleCreated from:body, articleUpdated from:body"
     hx-get="/admin/articles"
     hx-swap="innerHTML">
    ...
</div>
```

### 2. Supprimer une ligne directement

```html
<button 
    hx-delete="/admin/articles/{{ id }}/delete"
    hx-target="#article-{{ id }}"
    hx-swap="outerHTML"       {# Remplace la ligne entière #}
    hx-confirm="Supprimer ?"
>
    Supprimer
</button>
```

### 3. Charger un formulaire dans une modal

```html
<button 
    hx-get="/admin/articles/create"
    hx-target="#modal-content"
    hx-swap="innerHTML"
>
    Nouveau
</button>
```

### 4. Soumettre un formulaire et remplacer la liste

```html
<form 
    hx-post="/admin/articles/create"
    hx-target="#articles-list"
    hx-swap="innerHTML"
>
    ...
</form>
```

### 5. Indicateur de chargement

```html
<button 
    hx-get="/admin/articles"
    hx-indicator="#loading"
>
    <span id="loading" class="htmx-indicator">
        <svg class="animate-spin h-5 w-5">...</svg>
    </span>
    Charger
</button>
```

---

## 🧩 Méthode Helper à ajouter dans AbstractController

```php
/**
 * Détecte si la requête vient de HTMX
 */
protected function isHtmxRequest(): bool
{
    return $this->request->getHeader('HX-Request') === 'true';
}

/**
 * Retourne un partial (sans layout)
 */
protected function partial(string $template, array $params = []): Response
{
    $content = $this->view->render($template, $params);
    return $this->response->setContent($content);
}

/**
 * Ajoute des headers HTMX à la réponse
 */
protected function htmxResponse(string $content): Response
{
    return $this->response->setContent($content);
}

/**
 * Déclenche un événement HTMX côté client
 */
protected function htmxTrigger(string $event): Response
{
    $this->response->setHeader('HX-Trigger', $event);
    return $this->response;
}

/**
 * Redirige via HTMX (sans rechargement complet)
 */
protected function htmxRedirect(string $url): Response
{
    $this->response->setHeader('HX-Redirect', $url);
    return $this->response->setContent('');
}
```

---

## ⚡ Règles pour l'Agent IA

> **IMPORTANT** : Quand l'agent IA génère du code CRUD avec HTMX :

1. **Toujours créer des partials** dans `_partials/` pour :
   - `_list.ogan` : La liste/tableau complète
   - `_row.ogan` : Une seule ligne/carte
   - `_form.ogan` : Le formulaire réutilisable

2. **Dans le controller**, vérifier `isHtmxRequest()` :
   - Si TRUE → retourner le partial avec `$this->partial()`
   - Si FALSE → retourner la page complète avec `$this->render()`

3. **Utiliser les headers HTMX** :
   - `HX-Trigger` : Pour déclencher des événements (refresh liste)
   - `HX-Redirect` : Pour rediriger côté client
   - `HX-Reswap` : Pour changer le swap dynamiquement

4. **Convention de nommage** :
   - Fichiers partiels préfixés par `_` (ex: `_list.ogan`)
   - Dossier `_partials/` dans chaque module

5. **ID HTML cohérents** :
   - `#articles-list` pour le conteneur liste
   - `#article-{{ id }}` pour chaque ligne
   - `#modal-content` pour la zone modale
