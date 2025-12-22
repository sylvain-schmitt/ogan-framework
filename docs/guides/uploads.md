# 📁 Upload de Fichiers et Optimisation d'Images

Ce guide explique comment utiliser le système d'upload de fichiers et d'optimisation d'images du framework Ogan.

## Fonctionnalités

- **Upload de fichiers** avec classe `UploadedFile`
- **Optimisation automatique** (redimensionnement, compression)
- **Conversion WebP** pour une meilleure performance
- **Génération de thumbnails** (plusieurs tailles)
- **Validation** (taille max, type MIME, dimensions)

---

## Upload Simple

### Dans le FormType

```php
use Ogan\Form\Types\FileType;

class ArticleFormType extends AbstractFormType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('image', FileType::class, [
                'label' => 'Image de l\'article',
                'accept' => 'image/*',
                'required' => false,
            ]);
    }
}
```

### Dans le Contrôleur

```php
use Ogan\Http\Request;
use Ogan\Http\UploadedFile;

#[Route('/article/create', methods: ['POST'])]
public function create(Request $request): Response
{
    $file = $request->file('image');
    
    if ($file && $file->isValid()) {
        // Déplacer le fichier
        $path = $file->move('public/uploads/', 'mon-image.jpg');
        
        // Ou avec un nom auto-généré
        $path = $file->move('public/uploads/');
    }
}
```

---

## Classe UploadedFile

L'objet `UploadedFile` offre une API fluide pour manipuler les fichiers :

```php
$file = $request->file('image');

// Vérifications
$file->isValid();           // true si uploadé sans erreur
$file->isImage();           // true si c'est une image

// Informations
$file->getOriginalName();   // "photo.jpg"
$file->getExtension();      // "jpg"
$file->getMimeType();       // "image/jpeg"
$file->getSize();           // 1234567 (bytes)
$file->getFormattedSize();  // "1.18 Mo"
$file->getImageDimensions(); // ['width' => 1920, 'height' => 1080]

// Erreurs
$file->getError();          // Code d'erreur PHP
$file->getErrorMessage();   // Message lisible

// Actions
$file->move($dir, $name);              // Déplace le fichier
$file->generateUniqueFilename('webp'); // "abc123_def456.webp"
```

---

## Optimisation d'Images

### Service ImageOptimizer

Le service `ImageOptimizer` permet d'optimiser automatiquement les images :

```php
use Ogan\Image\ImageOptimizer;

#[Route('/article/create', methods: ['POST'])]
public function create(Request $request, ImageOptimizer $optimizer): Response
{
    $file = $request->file('image');
    
    if ($file && $file->isValid() && $file->isImage()) {
        $result = $optimizer->optimize($file, [
            'maxWidth' => 1920,
            'maxHeight' => 1080,
            'quality' => 85,
            'format' => 'webp',
            'directory' => 'public/uploads/articles/',
        ]);
        
        // Résultat
        $result->path;           // "public/uploads/articles/abc123.webp"
        $result->getWebPath();   // "uploads/articles/abc123.webp" (pour les URLs)
        $result->width;          // 1920
        $result->height;         // 1080
        $result->getFormattedSize(); // "156 Ko"
    }
}
```

### Génération de Thumbnails

Générez plusieurs tailles automatiquement :

```php
$results = $optimizer->optimizeWithThumbnails($file, [
    'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    'medium'    => ['width' => 600],
    'large'     => ['width' => 1200],
]);

// Accès aux résultats
$results['original']->getWebPath();   // Image optimisée originale
$results['thumbnail']->getWebPath();  // Thumbnail 150x150 (croppé)
$results['medium']->getWebPath();     // Version 600px de large
$results['large']->getWebPath();      // Version 1200px de large
```

### Tailles par défaut

Si vous ne spécifiez pas de tailles, les tailles par défaut sont utilisées :

```php
// Utilise les tailles par défaut
$results = $optimizer->optimizeWithThumbnails($file);

// Équivalent à :
$results = $optimizer->optimizeWithThumbnails($file, [
    'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    'medium'    => ['width' => 600],
    'large'     => ['width' => 1200],
]);
```

---

## Configuration

### Dans `config/parameters.yaml`

```yaml
uploads:
  directory: 'public/uploads'    # Dossier par défaut
  quality: 85                    # Qualité de compression (1-100)
  format: 'webp'                 # Format de sortie par défaut
```

---

## Validation des Fichiers

### Contraintes disponibles

```php
use Ogan\Validation\Constraints\MaxFileSize;
use Ogan\Validation\Constraints\MimeType;
use Ogan\Validation\Constraints\ImageDimensions;

// Taille maximale
$constraint = new MaxFileSize('5M');  // 5 Mo
$constraint = new MaxFileSize('500K'); // 500 Ko

// Type MIME
$constraint = new MimeType(['image/jpeg', 'image/png', 'image/webp']);
$constraint = new MimeType(['image/*']); // Toutes les images

// Dimensions d'image
$constraint = new ImageDimensions([
    'minWidth' => 800,
    'maxWidth' => 4000,
    'minHeight' => 600,
    'maxHeight' => 3000,
]);
```

### Validation dans le contrôleur

```php
use Ogan\Validation\Constraints\MaxFileSize;
use Ogan\Validation\Constraints\MimeType;

$file = $request->file('image');

if ($file && $file->isValid()) {
    $errors = [];
    
    // Valider la taille
    $sizeError = (new MaxFileSize('5M'))->validate($file);
    if ($sizeError) $errors[] = $sizeError;
    
    // Valider le type
    $typeError = (new MimeType(['image/*']))->validate($file);
    if ($typeError) $errors[] = $typeError;
    
    if (empty($errors)) {
        // Fichier valide, procéder à l'upload
    }
}
```

---

## Exemple Complet : Blog avec Images

### Modèle Article

```php
class Article extends Model
{
    protected static string $table = 'articles';
    
    protected ?string $title = null;
    protected ?string $content = null;
    protected ?string $image = null;           // Chemin image principale
    protected ?string $image_thumbnail = null; // Chemin thumbnail
}
```

### Contrôleur

```php
use Ogan\Image\ImageOptimizer;
use Ogan\Validation\Constraints\{MaxFileSize, MimeType};

class ArticleController extends AbstractController
{
    #[Route('/article/create', methods: ['POST'])]
    public function create(Request $request, ImageOptimizer $optimizer): Response
    {
        $form = $this->createForm(ArticleFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $article = new Article();
            $article->setTitle($form->get('title'));
            $article->setContent($form->get('content'));
            
            // Gestion de l'image
            $file = $request->file('image');
            if ($file && $file->isValid()) {
                // Validation
                $sizeError = (new MaxFileSize('5M'))->validate($file);
                $typeError = (new MimeType(['image/*']))->validate($file);
                
                if ($sizeError || $typeError) {
                    $this->addFlash('error', $sizeError ?? $typeError);
                    return $this->redirect('/article/new');
                }
                
                // Optimisation avec thumbnails
                $results = $optimizer->optimizeWithThumbnails($file, [
                    'thumbnail' => ['width' => 300, 'height' => 200, 'crop' => true],
                ], [
                    'directory' => 'public/uploads/articles/',
                ]);
                
                $article->setImage($results['original']->getWebPath());
                $article->setImageThumbnail($results['thumbnail']->getWebPath());
            }
            
            $article->save();
            
            $this->addFlash('success', 'Article créé !');
            return $this->redirect('/articles');
        }
        
        return $this->render('article/new.ogan', ['form' => $form]);
    }
}
```

### Template (affichage)

```html
<article>
    <h1>{{ article.title }}</h1>
    
    {% if article.image %}
        <picture>
            <!-- Thumbnail pour mobile -->
            <source media="(max-width: 600px)" 
                    srcset="/{{ article.image_thumbnail }}">
            <!-- Image principale -->
            <img src="/{{ article.image }}" 
                 alt="{{ article.title }}"
                 loading="lazy">
        </picture>
    {% endif %}
    
    <div>{{ article.content|raw }}</div>
</article>
```

---

## Formats Supportés

### Entrée (lecture)
- JPEG
- PNG
- GIF
- WebP

### Sortie (écriture)
- JPEG
- PNG
- GIF
- WebP (recommandé pour le web)

---

## Notes

- **GD Extension** : Le système utilise l'extension GD de PHP (incluse par défaut)
- **WebP** : Conversion automatique pour une meilleure compression (~30% plus léger que JPEG)
- **Ratio** : Le ratio d'aspect est toujours préservé lors du redimensionnement
- **Crop** : L'option `crop` centre et recadre l'image pour obtenir les dimensions exactes

---

## Upload et HTMX

L'upload de fichiers fonctionne parfaitement avec **HTMX**, offrant une expérience utilisateur fluide sans rechargement complet de page.

### Configuration du Formulaire

Pour que l'upload fonctionne avec HTMX, vous devez :
1. Ajouter `hx-encoding="multipart/form-data"` (obligatoire).
2. Ajouter `hx-post` vers votre route.
3. Définir une cible `hx-target` pour afficher le résultat.

```html
<form method="POST" enctype="multipart/form-data" 
      hx-post="{{ path('upload_route') }}"
      hx-encoding="multipart/form-data"
      hx-target="#upload-result">
      
    <input type="file" name="image">
    <button type="submit">Uploader</button>
</form>

<div id="upload-result">
    <!-- Le résultat s'affichera ici -->
</div>
```

### Performance : Utilisation de Partial

Pour optimiser les performances et la fluidité (ne pas recharger toute la page), il est recommandé de retourner uniquement un **Partial HTML** si la requête est faite via HTMX.

**Contrôleur :**

```php
// Si requête HTMX, on renvoie seulement le partial du résultat
if ($this->request->getHeader('HX-Request')) {
    return $this->render('partials/_upload_result.ogan', [
        'result' => $result
    ]);
}

// Sinon, rendu de la page complète (fallback)
return $this->render('upload/index.ogan', [
    'result' => $result
]);
```

**Template Partial (`partials/_upload_result.ogan`) :**

Ce template contient uniquement le HTML à injecter dans `#upload-result`.

```html
{% if result %}
    <div class="success">Image uploadée : {{ result.getWebPath() }}</div>
{% endif %}

<!-- Mise à jour des Flash Messages via OOB (Out Of Band) -->
{{ component('flashes', ['oob' => true]) }}
```

### Mise à jour des Flash Messages (OOB)

Lorsque vous utilisez un partial HTMX, le reste de la page (comme le conteneur des messages flash) n'est pas mis à jour automatiquement. 

Pour forcer la mise à jour des flash messages, utilisez l'option `oob` du composant `flashes` dans votre partial de réponse :

```html
<!-- Dans votre partial de réponse (ex: _result.ogan) -->
{{ component('flashes', ['oob' => true]) }}
```

Cela génèrera un bloc HTML avec l'attribut `hx-swap-oob="true"`, indiquant à HTMX de mettre à jour le conteneur des flash messages existant dans la page principale, en plus du contenu cible.
