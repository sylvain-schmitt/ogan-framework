# 🔗 Slugs - URLs propres

> Génération de slugs URL-friendly avec unicité automatique

## Table des matières

- [Classe Slugger](#classe-slugger)
- [Trait HasSlug](#trait-hasslug)
- [Exemples pratiques](#exemples-pratiques)

---

## Classe Slugger

La classe `Ogan\Util\Slugger` fournit des méthodes statiques pour générer des slugs.

### Génération simple

```php
use Ogan\Util\Slugger;

$slug = Slugger::slugify('Mon Article de Blog');
// → "mon-article-de-blog"

$slug = Slugger::slugify('Événements à Paris!');
// → "evenements-a-paris"

$slug = Slugger::slugify('Café & Thé');
// → "cafe-the"
```

### Options

```php
// Séparateur personnalisé
$slug = Slugger::slugify('Hello World', '_');
// → "hello_world"

// Longueur maximale
$slug = Slugger::slugify('Un titre très long pour un article', '-', 20);
// → "un-titre-tres-long"
```

### Slug unique (vérifie la BDD)

```php
use App\Model\Article;

// Génère "mon-article" ou "mon-article-2" si déjà pris
$slug = Slugger::unique('Mon Article', Article::class, 'slug');

// Pour les mises à jour, exclure l'ID courant
$slug = Slugger::unique('Mon Article', Article::class, 'slug', $article->getId());
```

### Slug composé

```php
$slug = Slugger::fromParts(['Catégorie', 'Mon Article']);
// → "categorie-mon-article"
```

---

## Trait HasSlug

Le trait `HasSlug` automatise la génération de slugs pour les modèles.

### Installation

```php
namespace App\Model;

use Ogan\Database\Model;
use Ogan\Database\Trait\HasSlug;

class Article extends Model
{
    use HasSlug;
    
    protected string $table = 'articles';
    
    // Optionnel: personnaliser les champs
    protected string $slugSource = 'title';  // Champ source (défaut: 'title')
    protected string $slugField = 'slug';     // Champ slug (défaut: 'slug')
}
```

### Génération automatique

```php
$article = new Article();
$article->setTitle('Mon Super Article');
$article->generateUniqueSlug();  // Génère le slug
$article->save();

echo $article->getSlug(); // "mon-super-article"
```

### Recherche par slug

```php
// Trouver par slug
$article = Article::findBySlug('mon-super-article');

// Ou avec exception si non trouvé
$article = Article::findBySlugOrFail('mon-super-article');
```

### Régénérer un slug

```php
$article = Article::find(1);
$article->setTitle('Nouveau Titre');
$article->regenerateSlug();
$article->save();
```

---

## Exemples pratiques

### Blog avec articles

```php
// Contrôleur
#[Route('/articles/{slug}', methods: ['GET'])]
public function show(string $slug): Response
{
    $article = Article::findBySlugOrFail($slug);
    
    return $this->render('articles/show.ogan', [
        'article' => $article
    ]);
}
```

### Catégories hiérarchiques

```php
// Slug composé catégorie/article
$slug = Slugger::fromParts([
    $category->getName(),
    $article->getTitle()
]);
// → "technologie-mon-article-php"
```

### Produits e-commerce

```php
class Product extends Model
{
    use HasSlug;
    
    protected string $slugSource = 'name';  // Utilise 'name' au lieu de 'title'
}

$product = new Product();
$product->setName('iPhone 15 Pro Max');
$product->generateUniqueSlug();
// → "iphone-15-pro-max"
```

---

## Caractères supportés

Le Slugger gère automatiquement :

| Caractère | Résultat |
|-----------|----------|
| é, è, ê, ë | e |
| à, â, ä | a |
| ç | c |
| ù, û, ü | u |
| œ | oe |
| æ | ae |
| ß | ss |
| Espaces | - |
| Caractères spéciaux | Supprimés |
