# 📝 Formulaires - Ogan Framework

Ce guide explique comment créer et utiliser les formulaires dans le framework Ogan.

## 📋 Table des matières

- [Créer un FormType](#créer-un-formtype)
- [Utiliser un formulaire dans un contrôleur](#utiliser-un-formulaire-dans-un-contrôleur)
- [Rendre le formulaire complet](#rendre-le-formulaire-complet)
- [Rendre les champs individuellement](#rendre-les-champs-individuellement)
- [Types de champs disponibles](#types-de-champs-disponibles)
- [Contraintes de validation](#contraintes-de-validation)

---

## Créer un FormType

### Commande de génération

```bash
php bin/console make:form User
```

### Structure d'un FormType

```php
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\{TextType, EmailType, PasswordType, SubmitType};
use Ogan\Form\Constraint\{Required, Email, MinLength};

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new Required(), new MinLength(2)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new Required(), new Email()],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'constraints' => [new Required(), new MinLength(8)],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
            ]);
    }
}
```

---

## Utiliser un formulaire dans un contrôleur

```php
use App\Form\UserFormType;

#[Route(path: '/user/create', methods: ['GET', 'POST'], name: 'user_create')]
public function create()
{
    $form = $this->formFactory->create(UserFormType::class, [
        'action' => '/user/store',
        'method' => 'POST',
    ]);

    if ($this->request->isMethod('POST')) {
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // Traiter les données...
            $this->addFlash('success', 'Utilisateur créé !');
            return $this->redirect('/users');
        }
    }

    return $this->render('user/create.ogan', [
        'form' => $form->createView()
    ]);
}
```

---

## Rendre le formulaire complet

La méthode la plus simple : laisser le framework générer tout le HTML.

```html
<!-- Dans votre template .ogan -->
{{ form }}
```

Cela génère :
- La balise `<form>` avec `method` et `action`
- Tous les champs avec leurs labels et erreurs
- L'attribut `enctype="multipart/form-data"` si un champ fichier est présent
- La balise `</form>`

---

## Rendre les champs individuellement

Pour un contrôle total sur le layout, vous pouvez rendre chaque champ séparément ou même ses composants individuels.

### Syntaxe disponible

| Syntaxe | Description |
|---------|-------------|
| `{{ form.email }}` | Champ complet (label + input + erreurs) |
| `{{ form.email.label }}` | Juste le label |
| `{{ form.email.widget }}` | Juste l'input |
| `{{ form.email.errors }}` | Juste les erreurs |
| `{{ form.email.row }}` | Alias du champ complet |

### Exemple basique

```html
<form method="POST" action="{{ path('user_store') }}">
    
    <div class="mb-4">
        {{ form.name }}
    </div>
    
    <div class="mb-4">
        {{ form.email }}
    </div>
    
    {{ form.submit }}
    
</form>
```

### Exemple avec contrôle total

Quand vous avez besoin d'un layout très personnalisé :

```html
<form method="POST" action="{{ path('register') }}" class="max-w-md mx-auto">
    
    <h2 class="text-2xl font-bold mb-6">Inscription</h2>
    
    <!-- Layout personnalisé avec label externe -->
    <div class="mb-4">
        {{ form.email.label }}
        <div class="flex items-center">
            <span class="text-gray-500 mr-2">@</span>
            {{ form.email.widget }}
        </div>
        {{ form.email.errors }}
    </div>
    
    <!-- Deux champs côte à côte, widgets seulement -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            {{ form.firstName.label }}
            {{ form.firstName.widget }}
        </div>
        <div>
            {{ form.lastName.label }}
            {{ form.lastName.widget }}
        </div>
    </div>
    
    <!-- Erreurs groupées en bas -->
    <div class="text-red-600 mb-4">
        {{ form.firstName.errors }}
        {{ form.lastName.errors }}
    </div>
    
    <!-- Mot de passe avec aide contextuelle -->
    <div class="mb-4">
        {{ form.password.label }}
        {{ form.password.widget }}
        <p class="text-sm text-gray-500 mt-1">
            Minimum 8 caractères, incluant une majuscule
        </p>
        {{ form.password.errors }}
    </div>
    
    {{ form.submit }}
    
</form>
```

### Afficher les erreurs globales

```html
{% if form.getErrors() %}
    <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
        {% for field, errors in form.getErrors() %}
            {% for error in errors %}
                <p>{{ error }}</p>
            {% endfor %}
        {% endfor %}
    </div>
{% endif %}
```

---

## Types de champs disponibles

| Type | Classe | Usage |
|------|--------|-------|
| Texte | `TextType::class` | Champ texte simple |
| Email | `EmailType::class` | Champ email avec validation HTML5 |
| Mot de passe | `PasswordType::class` | Champ masqué |
| Nombre | `NumberType::class` | Champ numérique |
| Textarea | `TextareaType::class` | Zone de texte multiligne |
| Select | `SelectType::class` | Liste déroulante |
| Checkbox | `CheckboxType::class` | Case à cocher |
| Fichier | `FileType::class` | Upload de fichier |
| Caché | `HiddenType::class` | Champ invisible |
| Submit | `SubmitType::class` | Bouton de soumission |

### Exemple avec différents types

```php
$builder
    ->add('title', TextType::class, ['label' => 'Titre'])
    ->add('category', SelectType::class, [
        'label' => 'Catégorie',
        'choices' => [
            'tech' => 'Technologie',
            'sport' => 'Sport',
            'culture' => 'Culture',
        ],
    ])
    ->add('content', TextareaType::class, [
        'label' => 'Contenu',
        'attr' => ['rows' => 10],
    ])
    ->add('published', CheckboxType::class, [
        'label' => 'Publier immédiatement',
        'required' => false,
    ])
    ->add('image', FileType::class, [
        'label' => 'Image de couverture',
        'accept' => 'image/*',
        'required' => false,
    ]);
```

---

## Contraintes de validation

Les contraintes s'appliquent au `handleRequest()` pour valider les données.

| Contrainte | Usage |
|------------|-------|
| `Required()` | Champ obligatoire |
| `Email()` | Email valide |
| `MinLength(n)` | Longueur minimum |
| `MaxLength(n)` | Longueur maximum |
| `Min(n)` | Valeur minimum (nombres) |
| `Max(n)` | Valeur maximum (nombres) |
| `Regex($pattern)` | Expression régulière |
| `EqualTo($field)` | Égalité avec un autre champ |
| `UniqueEntity($model, $field)` | Unicité en BDD |

### Exemple

```php
$builder
    ->add('email', EmailType::class, [
        'constraints' => [
            new Required('L\'email est obligatoire'),
            new Email('Email invalide'),
            new UniqueEntity(User::class, 'email', 'Cet email existe déjà'),
        ],
    ])
    ->add('password', PasswordType::class, [
        'constraints' => [
            new Required(),
            new MinLength(8, 'Minimum 8 caractères'),
        ],
    ])
    ->add('password_confirm', PasswordType::class, [
        'constraints' => [
            new EqualTo('password', 'Les mots de passe ne correspondent pas'),
        ],
    ]);
```

---

## Options communes des champs

| Option | Description | Exemple |
|--------|-------------|---------|
| `label` | Libellé affiché | `'label' => 'Votre nom'` |
| `required` | Champ requis (HTML5) | `'required' => false` |
| `attr` | Attributs HTML | `'attr' => ['class' => 'my-class']` |
| `data` | Valeur par défaut | `'data' => 'default'` |
| `placeholder` | Placeholder | `'placeholder' => 'Entrez...'` |
| `constraints` | Contraintes de validation | `'constraints' => [new Required()]` |

---

## Bonnes pratiques

1. **Un FormType par entité** : Créez un FormType dédié pour chaque modèle.
2. **Contraintes dans le FormType** : Gardez la logique de validation proche du formulaire.
3. **Rendu individuel pour les layouts complexes** : Utilisez `{{ form.field }}` quand vous avez besoin de contrôle.
4. **Messages d'erreur personnalisés** : Passez un message aux contraintes pour une meilleure UX.

```php
new Required('Ce champ est obligatoire')
new MinLength(8, 'Le mot de passe doit faire au moins 8 caractères')
```
