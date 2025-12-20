# 🗑️ Soft Delete - Ogan Framework

> Guide d'utilisation de la suppression logique (soft delete)

## 📖 Introduction

Le Soft Delete permet de "supprimer" des enregistrements sans les effacer réellement de la base de données. Au lieu de supprimer la ligne, elle est marquée avec une date de suppression (`deleted_at`).

## ⚙️ Configuration

### 1. Ajouter la colonne `deleted_at`

Dans votre migration :

```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->timestamp('deleted_at')->nullable();
    $table->timestamps();
});
```

### 2. Utiliser le trait dans votre modèle

```php
<?php

namespace App\Model;

use Ogan\Database\Model;
use Ogan\Database\Traits\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;
    
    protected static ?string $table = 'articles';
}
```

## 🚀 Utilisation

### Suppression logique

```php
$article = Article::find(1);
$article->delete();  // Met deleted_at = NOW()

// L'article n'apparaît plus dans les requêtes normales
Article::all();        // Ne contient pas l'article supprimé
Article::find(1);      // Retourne null
```

### Suppression réelle (force delete)

```php
$article->forceDelete();  // Supprime vraiment de la base
```

### Restaurer un enregistrement

```php
// Récupérer un enregistrement supprimé
$article = Article::onlyTrashed()->where('id', '=', 1)->first();

// Le restaurer
$article->restore();  // deleted_at = NULL
```

### Vérifier si un enregistrement est supprimé

```php
if ($article->trashed()) {
    echo "Cet article a été supprimé";
}
```

## 🔍 Requêtes

### Comportement par défaut

```php
// Exclut automatiquement les enregistrements soft-deleted
Article::all();
Article::where('category', '=', 'tech')->get();
```

### Inclure les enregistrements supprimés

```php
// Inclure les supprimés
Article::withTrashed()->get();

// Seulement les supprimés
Article::onlyTrashed()->get();

// Avec des conditions
Article::withTrashed()
    ->where('author_id', '=', 1)
    ->get();
```

## 📋 Méthodes disponibles

| Méthode | Description |
|---------|-------------|
| `delete()` | Suppression logique (met `deleted_at`) |
| `forceDelete()` | Suppression réelle (DELETE SQL) |
| `restore()` | Restaure l'enregistrement (`deleted_at = null`) |
| `trashed()` | Vérifie si soft-deleted |
| `withTrashed()` | Inclut les soft-deleted dans les requêtes |
| `onlyTrashed()` | Retourne seulement les soft-deleted |
| `withoutTrashed()` | Exclut les soft-deleted (défaut) |

## 💡 Bonnes pratiques

1. **Toujours ajouter `deleted_at` nullable** dans vos migrations
2. **Utiliser `forceDelete()` avec précaution** - c'est irréversible
3. **Prévoir une interface admin** pour gérer les éléments supprimés
4. **Nettoyer périodiquement** les vieux enregistrements si nécessaire

## 🔧 Personnalisation

Vous pouvez personnaliser le nom de la colonne :

```php
class Article extends Model
{
    use SoftDeletes;
    
    protected static string $deletedAtColumn = 'archived_at';
}
```
