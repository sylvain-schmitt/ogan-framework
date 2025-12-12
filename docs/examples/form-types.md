# 📝 Guide Complet - Système FormType

> Guide complet pour créer et utiliser des formulaires avec le système FormType d'Ogan Framework

## Table des Matières

1. [Introduction](#introduction)
2. [Créer un FormType](#créer-un-formtype)
3. [Types de Champs de Base](#types-de-champs-de-base)
4. [Types de Champs Avancés](#types-de-champs-avancés)
5. [Utilisation dans un Contrôleur](#utilisation-dans-un-contrôleur)
6. [Rendu dans la Vue](#rendu-dans-la-vue)
7. [Validation](#validation)
8. [Options et Personnalisation](#options-et-personnalisation)

---

## Introduction

Le système FormType permet de créer des formulaires de manière déclarative et réutilisable, similaire à Symfony Forms. Il offre :

- ✅ Création de formulaires déclaratifs
- ✅ Validation automatique
- ✅ Rendu HTML automatique
- ✅ Gestion des erreurs
- ✅ Intégration avec Validator
- ✅ Support des options personnalisées

---

## Créer un FormType

```php
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\TextType;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\PasswordType;
use Ogan\Form\Types\TextareaType;
use Ogan\Form\Types\SubmitType;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => true,
                'min' => 8,
                'attr' => ['class' => 'form-control']
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'rows' => 5,
                'attr' => ['class' => 'form-control']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
}
```

---

## Types de Champs de Base

### TextType - Champ Texte

```php
->add('name', TextType::class, [
    'label' => 'Nom',
    'required' => true,
    'placeholder' => 'Votre nom',
    'min' => 3,
    'max' => 50,
    'data' => 'Valeur par défaut',
    'attr' => ['class' => 'form-control', 'id' => 'user-name']
])
```

### EmailType - Champ Email

```php
->add('email', EmailType::class, [
    'label' => 'Email',
    'required' => true,
    'attr' => ['class' => 'form-control']
])
```

**Validation automatique** : Vérifie le format email.

### PasswordType - Champ Mot de Passe

```php
->add('password', PasswordType::class, [
    'label' => 'Mot de passe',
    'required' => true,
    'min' => 8,
    'attr' => ['class' => 'form-control']
])
```

### TextareaType - Zone de Texte

```php
->add('bio', TextareaType::class, [
    'label' => 'Biographie',
    'required' => false,
    'rows' => 5,
    'max' => 500,
    'attr' => ['class' => 'form-control']
])
```

### SubmitType - Bouton de Soumission

```php
->add('submit', SubmitType::class, [
    'label' => 'Enregistrer',
    'attr' => ['class' => 'btn btn-primary']
])
```

---

## Types de Champs Avancés

### SelectType - Liste Déroulante

```php
->add('country', SelectType::class, [
    'label' => 'Pays',
    'choices' => [
        'fr' => 'France',
        'us' => 'États-Unis',
        'uk' => 'Royaume-Uni',
        'de' => 'Allemagne'
    ],
    'placeholder' => 'Sélectionnez un pays',
    'required' => true,
    'attr' => ['class' => 'form-control']
])

// Select multiple
->add('languages', SelectType::class, [
    'label' => 'Langues parlées',
    'choices' => [
        'fr' => 'Français',
        'en' => 'Anglais',
        'es' => 'Espagnol'
    ],
    'multiple' => true,
    'required' => false
])
```

### CheckboxType - Case à Cocher

```php
->add('accept_terms', CheckboxType::class, [
    'label' => 'J\'accepte les conditions d\'utilisation',
    'required' => true,
    'value' => '1', // Valeur envoyée si coché
    'checked' => false, // État par défaut
    'attr' => ['class' => 'form-check-input']
])

// Checkbox simple (non requis)
->add('newsletter', CheckboxType::class, [
    'label' => 'Je souhaite recevoir la newsletter',
    'required' => false
])
```

### RadioType - Boutons Radio

```php
->add('gender', RadioType::class, [
    'label' => 'Genre',
    'choices' => [
        'male' => 'Homme',
        'female' => 'Femme',
        'other' => 'Autre',
        'prefer_not_to_say' => 'Préfère ne pas dire'
    ],
    'required' => true,
    'inline' => true // Afficher en ligne (horizontal)
])

// Radio en colonne (par défaut)
->add('payment_method', RadioType::class, [
    'label' => 'Méthode de paiement',
    'choices' => [
        'credit_card' => 'Carte de crédit',
        'paypal' => 'PayPal',
        'bank_transfer' => 'Virement bancaire'
    ],
    'inline' => false // Afficher en colonne (vertical)
])
```

### DateType - Date

```php
->add('birthdate', DateType::class, [
    'label' => 'Date de naissance',
    'required' => true,
    'min' => '1900-01-01',
    'max' => 'today', // Date d'aujourd'hui
    'attr' => ['class' => 'form-control']
])

// Date avec valeur par défaut
->add('event_date', DateType::class, [
    'label' => 'Date de l\'événement',
    'data' => date('Y-m-d'), // Date du jour par défaut
    'min' => date('Y-m-d'), // Pas de dates passées
    'required' => true
])
```

### NumberType - Nombre

```php
->add('age', NumberType::class, [
    'label' => 'Âge',
    'required' => true,
    'min' => 0,
    'max' => 120,
    'step' => 1,
    'placeholder' => 'Votre âge',
    'attr' => ['class' => 'form-control']
])

// Nombre décimal
->add('price', NumberType::class, [
    'label' => 'Prix',
    'required' => true,
    'min' => 0,
    'step' => 0.01, // Pour les décimales
    'placeholder' => '0.00'
])

// Note sur 10
->add('rating', NumberType::class, [
    'label' => 'Note',
    'min' => 0,
    'max' => 10,
    'step' => 0.5,
    'required' => true
])
```

### FileType - Upload de Fichier

```php
->add('avatar', FileType::class, [
    'label' => 'Photo de profil',
    'accept' => 'image/*', // Seulement les images
    'required' => false,
    'attr' => ['class' => 'form-control']
])

// Upload de PDF uniquement
->add('document', FileType::class, [
    'label' => 'Document PDF',
    'accept' => '.pdf',
    'required' => true
])

// Upload multiple d'images
->add('photos', FileType::class, [
    'label' => 'Photos',
    'accept' => 'image/*',
    'multiple' => true,
    'required' => false
])

// Types MIME spécifiques
->add('cv', FileType::class, [
    'label' => 'CV',
    'accept' => '.pdf,.doc,.docx',
    'required' => true
])
```

**Note importante :** Le formulaire ajoute automatiquement `enctype="multipart/form-data"` si un FileType est présent.

---

## Exemple Complet de Formulaire

```php
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\TextType;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\PasswordType;
use Ogan\Form\Types\SelectType;
use Ogan\Form\Types\RadioType;
use Ogan\Form\Types\CheckboxType;
use Ogan\Form\Types\DateType;
use Ogan\Form\Types\NumberType;
use Ogan\Form\Types\FileType;
use Ogan\Form\Types\TextareaType;
use Ogan\Form\Types\SubmitType;

class UserRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            // Informations personnelles
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'required' => true,
                'min' => 2,
                'max' => 100,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre nom']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => true,
                'min' => 8,
                'attr' => ['class' => 'form-control']
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => true,
                'max' => 'today',
                'attr' => ['class' => 'form-control']
            ])
            ->add('age', NumberType::class, [
                'label' => 'Âge',
                'required' => false,
                'min' => 18,
                'max' => 120,
                'attr' => ['class' => 'form-control']
            ])
            ->add('gender', RadioType::class, [
                'label' => 'Genre',
                'choices' => [
                    'male' => 'Homme',
                    'female' => 'Femme',
                    'other' => 'Autre'
                ],
                'required' => true,
                'inline' => true
            ])
            ->add('country', SelectType::class, [
                'label' => 'Pays',
                'choices' => [
                    'fr' => 'France',
                    'us' => 'États-Unis',
                    'uk' => 'Royaume-Uni',
                    'de' => 'Allemagne'
                ],
                'placeholder' => 'Sélectionnez un pays',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'rows' => 5,
                'max' => 500,
                'attr' => ['class' => 'form-control']
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Photo de profil',
                'accept' => 'image/*',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('accept_terms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'required' => true
            ])
            ->add('newsletter', CheckboxType::class, [
                'label' => 'Je souhaite recevoir la newsletter',
                'required' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'S\'inscrire',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
}
```

---

## Utilisation dans un Contrôleur

```php
<?php

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;
use App\Form\UserFormType;

class UserController extends AbstractController
{
    #[Route(path: '/user/create', methods: ['GET', 'POST'], name: 'user_create')]
    public function create()
    {
        // Créer le formulaire
        $form = $this->formFactory->create(UserFormType::class, [
            'action' => '/user/create',
            'method' => 'POST'
        ]);

        // Si le formulaire est soumis
        if ($this->request->isMethod('POST')) {
            $form->handleRequest($this->request);

            if ($form->isValid()) {
                // Récupérer les données validées
                $data = $form->getData();

                // Traiter les données (ex: sauvegarder en DB)
                // $user = new User();
                // $user->name = $data['name'];
                // $user->email = $data['email'];
                // $user->save();

                // Gérer l'upload de fichier
                if (isset($data['avatar']) && $data['avatar']) {
                    $file = $data['avatar'];
                    // Déplacer le fichier uploadé
                    // move_uploaded_file($file['tmp_name'], '/path/to/uploads/' . $file['name']);
                }

                // Rediriger
                return $this->redirect('/user/list');
            }
        }

        // Rendre la vue avec le formulaire
        return $this->render('user/create.html.php', [
            'form' => $form->createView()
        ]);
    }
}
```

---

## Rendu dans la Vue

### Option 1 : Rendre tout le formulaire

```php
<!-- templates/user/create.html.php -->
<h1>Créer un utilisateur</h1>

<?= $form->createView()->render() ?>
```

### Option 2 : Rendre champ par champ (plus de contrôle)

```php
<!-- templates/user/create.html.php -->
<h1>Créer un utilisateur</h1>

<form method="POST" action="/user/create">
    <?= $form['name']->render() ?>
    <?= $form['email']->render() ?>
    <?= $form['password']->render() ?>
    <?= $form['bio']->render() ?>
    <?= $form['submit']->render() ?>
</form>

<!-- Afficher les erreurs globales -->
<?php if (!empty($form->getErrors())): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($form->getErrors() as $field => $errors): ?>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
```

---

## Validation

La validation est automatique selon les options :

- `required` → Vérifie que le champ n'est pas vide
- `email` → Vérifie le format email (pour EmailType)
- `min:X` → Vérifie la longueur/valeur minimale
- `max:X` → Vérifie la longueur/valeur maximale

Les erreurs sont automatiquement affichées sous chaque champ.

---

## Options et Personnalisation

### Options Communes à Tous les Types

```php
[
    'label' => 'Label du champ',        // Label affiché
    'required' => true,                 // Champ requis
    'attr' => [                         // Attributs HTML
        'class' => 'form-control',
        'id' => 'custom-id',
        'data-custom' => 'value'
    ]
]
```

### Options Spécifiques par Type

#### SelectType
- `choices` : Tableau associatif [valeur => label]
- `placeholder` : Texte de l'option placeholder
- `multiple` : true pour sélection multiple

#### RadioType
- `choices` : Tableau associatif [valeur => label]
- `inline` : true pour affichage horizontal

#### CheckboxType
- `value` : Valeur envoyée si coché (défaut: '1')
- `checked` : État par défaut (true/false)

#### DateType
- `min` : Date minimale (format 'Y-m-d' ou 'today')
- `max` : Date maximale (format 'Y-m-d' ou 'today')
- `data` : Valeur par défaut (DateTime ou string)

#### NumberType
- `min` : Valeur minimale
- `max` : Valeur maximale
- `step` : Incrément (1 pour entiers, 0.01 pour décimales)
- `placeholder` : Texte du placeholder

#### FileType
- `accept` : Types MIME acceptés ('image/*', '.pdf', etc.)
- `multiple` : true pour upload multiple

### Personnalisation CSS

Les champs génèrent des classes CSS par défaut :
- `.form-group` : Conteneur du champ
- `.errors` : Conteneur des erreurs
- `.error` : Message d'erreur individuel
- `.required` : Astérisque pour les champs requis

Vous pouvez personnaliser via les attributs `attr` :

```php
'attr' => [
    'class' => 'form-control custom-class',
    'data-custom' => 'value'
]
```

---

## Liste Complète des Types Disponibles

1. **TextType** - Champ texte
2. **EmailType** - Champ email (validation automatique)
3. **PasswordType** - Champ mot de passe
4. **TextareaType** - Zone de texte multiligne
5. **SelectType** - Liste déroulante
6. **CheckboxType** - Case à cocher
7. **RadioType** - Boutons radio
8. **DateType** - Date
9. **NumberType** - Nombre
10. **FileType** - Upload de fichier
11. **SubmitType** - Bouton de soumission

---

**Pour plus d'informations, consultez la [documentation du framework](../reference/framework-api.md).**

