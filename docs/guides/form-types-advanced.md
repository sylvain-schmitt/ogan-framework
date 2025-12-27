# 📝 Types de Formulaires Avancés

> ColorType, WysiwygType et autres types spéciaux

## ColorType - Sélecteur de couleur

Génère un champ `<input type="color">` pour sélectionner une couleur.

### Usage

```php
use Ogan\Form\Types\ColorType;

$builder->add('color', ColorType::class, [
    'label' => 'Couleur',
    'required' => false,
    'attr' => [
        'class' => 'w-16 h-10 border border-default rounded-xl cursor-pointer',
        'value' => '#C07459'  // Couleur par défaut
    ]
]);
```

### Rendu

Le champ affiche :
- Un sélecteur de couleur natif du navigateur
- La valeur hexadécimale à côté (mise à jour en temps réel)

---

## WysiwygType - Éditeur de texte riche

Génère un `<textarea>` avec intégration TinyMCE (CDN, aucune configuration requise).

### Usage basique

```php
use Ogan\Form\Types\WysiwygType;

$builder->add('content', WysiwygType::class, [
    'label' => 'Contenu de l\'article',
    'required' => true
]);
```

### Options avancées

```php
$builder->add('content', WysiwygType::class, [
    'label' => 'Contenu',
    'toolbar' => 'full',     // minimal | simple | full
    'height' => 400,         // Hauteur en pixels
    'editor' => 'tinymce',   // tinymce | basic (sans JS)
    'attr' => [
        'rows' => 10
    ]
]);
```

### Presets de toolbar

| Preset | Boutons |
|--------|---------|
| `minimal` | Gras, Italique, Lien |
| `simple` | Gras, Italique, Souligné, Listes, Lien |
| `full` | Tout (Annuler, Blocs, Formatage, Listes, Lien, Image, Code) |

### Récupérer le contenu

```php
// Dans le contrôleur
$content = $form->getData()['content'];
// Le contenu est du HTML sécurisé
```

---

## Afficher le contenu WYSIWYG

Dans les templates, utilisez le filtre `|raw` pour afficher le HTML :

```html
<article>
    {{ article.content|raw }}
</article>
```

> ⚠️ **Sécurité** : Assurez-vous que le contenu provient d'une source de confiance (utilisateur authentifié).
