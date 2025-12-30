# 📝 Formulaires - Ogan Framework

Ce guide explique comment créer, gérer et valider des formulaires dans Ogan Framework.

## 📋 Table des matières

- [Créer un FormType](#créer-un-formtype)
- [Utiliser un formulaire dans un contrôleur](#utiliser-un-formulaire-dans-un-contrôleur)
- [Rendu dans les vues](#rendu-dans-les-vues)
- [Validation des données](#validation-des-données)
- [Référence des Champs (Types)](#référence-des-champs-types)
    - [Champs de base](#champs-de-base)
    - [Champs avancés (Couleur, Wysiwyg...)](#champs-avancés)

---

## Créer un FormType

Les formulaires sont définis dans des classes dédiées (FormType) pour être réutilisables.

**Commande de génération :**
```bash
php bin/console make:form User
```

**Structure d'exemple (`src/Form/UserType.php`) :**

```php
namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\{TextType, EmailType, PasswordType, SelectType, SubmitType};
use Ogan\Form\Constraint\{Required, Email, MinLength};

class UserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [new Required()],
                'attr' => ['placeholder' => 'Jean Dupont']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'constraints' => [new Required(), new Email()]
            ])
            ->add('role', SelectType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'ROLE_USER' => 'Utilisateur',
                    'ROLE_ADMIN' => 'Administrateur',
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer']);
    }
}
```

---

## Utiliser un formulaire dans un contrôleur

Dans votre contrôleur, utilisez `FormFactory` pour instancier et gérer le formulaire.

```php
// Dans une méthode de contrôleur
public function register(): Response
{
    $form = $this->formFactory->create(UserType::class);

    // Gérer la soumission (si c'est une requête POST)
    $form->handleRequest($this->request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        
        // Sauvegarder l'utilisateur...
        // $user = new User($data);
        // $user->save();

        $this->addFlash('success', 'Inscription réussie !');
        return $this->redirect('/login');
    }

    return $this->render('auth/register.ogan', [
        'form' => $form->createView()
    ]);
}
```

---

## Rendu dans les vues

### Méthode simple (Rendu automatique)

La méthode la plus rapide. Affiche tous les champs les uns après les autres.

```html
<!-- templates/auth/register.ogan -->
<h1>Inscription</h1>

<div class="form-container">
    {% form.render() %}
</div>
```

### Méthode champ par champ (Contrôle total)

Pour personnaliser le layout (CSS, grids, etc.), affichez chaque champ individuellement.

```html
<form method="POST">
    <!-- Champ sécurisé CSRF (automatique, mais peut être manuel) -->
    {{ form._csrf_token }}

    <div class="grid grid-cols-2 gap-4">
        <div class="col">
            {{ form.name }}
        </div>
        <div class="col">
            {{ form.email }}
        </div>
    </div>

    <!-- Composants individuels d'un champ -->
    <div class="custom-field">
        {{ form.password.label }}
        {{ form.password.widget }}
        <span class="help-text">Min. 8 caractères</span>
        {{ form.password.errors }}
    </div>

    <div class="actions mt-4">
        {{ form.submit }}
    </div>
</form>
```

**Syntaxe disponible :**
| Syntaxe | Description |
|---------|-------------|
| `{{ form.field }}` | Rendu complet (Label + Widget + Erreurs) |
| `{{ form.field.label }}` | Libellé seulement |
| `{{ form.field.widget }}` | Input HTML seulement |
| `{{ form.field.errors }}` | Liste des erreurs seulement |

---

## Validation des données

La validation se fait via les **Constraints** passées dans les options du champ.

```php
use Ogan\Form\Constraint\{Required, Email, MinLength, EqualTo, UniqueEntity};

$builder->add('email', EmailType::class, [
    'constraints' => [
        new Required('Ce champ est obligatoire'),
        new Email('Format invalide'),
        new UniqueEntity(User::class, 'email', 'Cet email existe déjà')
    ]
]);
```

**Principales contraintes :**
*   `Required`
*   `Email`
*   `MinLength(min)`, `MaxLength(max)`
*   `Min(val)`, `Max(val)` (Nombres)
*   `Regex(pattern)`
*   `EqualTo(fieldName)` (ex: confirmation de mot de passe)
*   `UniqueEntity(Class, field)` (Vérification BDD)

---

## Référence des Champs (Types)

### Champs de base

| Classe | Description | Options spécifiques |
|--------|-------------|---------------------|
| `TextType` | Input texte simple | `placeholder` |
| `EmailType` | Input type email | |
| `PasswordType` | Input type password | |
| `TextareaType` | Textarea | `rows` |
| `NumberType` | Input number | `min`, `max`, `step` |
| `DateType` | Input date | `min`, `max` (format Y-m-d) |
| `CheckboxType` | Input checkbox | `checked` (bool) |
| `SelectType` | Liste déroulante | `choices` (array), `multiple` (bool), `expanded` (radio/checkbox list) |
| `RadioType` | Boutons radio | `choices`, `inline` (bool) |
| `FileType` | Input file | `accept` (extensions/MIME), `multiple` |
| `HiddenType` | Input hidden | |
| `SubmitType` | Bouton submit | |

### Champs avancés

#### ColorType
Sélecteur de couleur natif (`<input type="color">`).

```php
use Ogan\Form\Types\ColorType;

$builder->add('theme_color', ColorType::class, [
    'label' => 'Couleur du thème',
    'attr' => ['value' => '#ff0000']
]);
```

#### WysiwygType
Éditeur de texte riche (basé sur TinyMCE via CDN).

```php
use Ogan\Form\Types\WysiwygType;

$builder->add('content', WysiwygType::class, [
    'label' => 'Contenu de l\'article',
    'height' => 400,          // Hauteur en pixels
    'toolbar' => 'simple',    // minimal, simple, full
]);
```

> **Note** : Pour afficher le contenu d'un Wysiwyg dans Twig sans échappement, utilisez le filtre `raw` : `{{ article.content|raw }}`.
