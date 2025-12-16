# 🛠️ Génération de Code - Ogan Framework

Le framework Ogan inclut un système de génération de code pour créer rapidement des contrôleurs, FormTypes et modèles, inspiré de Symfony.

## 📋 Table des matières

- [Introduction](#introduction)
- [Générer un contrôleur](#générer-un-contrôleur)
- [Générer un FormType](#générer-un-formtype)
- [Générer un modèle](#générer-un-modèle)
- [Générer tout en une commande](#générer-tout-en-une-commande)
- [Options](#options)

---

## 🎯 Introduction

### Pourquoi générer du code ?

✅ **Rapidité** : Créez des fichiers structurés en quelques secondes  
✅ **Cohérence** : Tous les fichiers suivent les mêmes conventions  
✅ **Productivité** : Moins de code répétitif à écrire  
✅ **Erreurs réduites** : Structure correcte dès le départ  

### Commandes disponibles

```bash
php bin/console make:controller <Name>   # Générer un contrôleur CRUD
php bin/console make:form <Name>         # Générer un FormType
php bin/console make:model [Name]        # Générer un modèle (mode interactif)
php bin/console make:all [Name]          # Générer tout (modèle + repository + form + contrôleur)
```

### Aide intégrée

Chaque commande supporte `--help` ou `-h` pour afficher l'aide :

```bash
php bin/console make:controller --help
php bin/console make:form -h
php bin/console make:model --help
php bin/console make:all --help
```

---

## 🎮 Générer un contrôleur

### Commande

```bash
php bin/console make:controller User
# ou
php bin/console make:controller PostController
```

### Ce qui est généré

Le générateur crée un contrôleur complet avec :
- ✅ Méthodes CRUD complètes (list, show, create, store, edit, update, delete)
- ✅ Routes avec attributs PHP 8
- ✅ Intégration avec les FormTypes
- ✅ Gestion des sessions et messages flash
- ✅ Redirections appropriées

### Exemple de sortie

```bash
🎮 Génération du contrôleur : User

✅ Contrôleur généré avec succès : UserController.php
📁 Fichier : /path/to/src/Controller/UserController.php
```

### Structure générée

```php
<?php

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;
use App\Model\User;
use App\Form\UserFormType;

class UserController extends AbstractController
{
    #[Route(path: '/users', methods: ['GET'], name: 'user_list')]
    public function list() { ... }

    #[Route(path: '/user/{id}', methods: ['GET'], name: 'user_show')]
    public function show(int $id) { ... }

    #[Route(path: '/user/create', methods: ['GET'], name: 'user_create')]
    public function create() { ... }

    #[Route(path: '/user/store', methods: ['POST'], name: 'user_store')]
    public function store() { ... }

    // ... edit, update, delete
}
```

---

## 📚 Générer un repository

### Commande

```bash
php bin/console make:repository User
```

### Génération automatique

**Important** : Le repository est **automatiquement généré** lorsque vous créez un modèle avec `php bin/make model` ou `php bin/make all`. Vous n'avez généralement pas besoin de le générer séparément.

Utilisez `php bin/make repository` uniquement si :
- Vous voulez générer un repository pour un modèle existant qui n'en a pas
- Vous voulez régénérer un repository

### Ce qui est généré

Le générateur crée un repository avec :
- ✅ Extension de `AbstractRepository`
- ✅ Configuration de la classe d'entité
- ✅ Configuration du nom de table
- ✅ Structure prête pour des requêtes personnalisées

### Exemple de sortie

```bash
📚 Génération du repository : User

✅ Repository généré avec succès : UserRepository.php
📁 Fichier : /path/to/src/Repository/UserRepository.php
```

### Structure générée

```php
<?php

namespace App\Repository;

use Ogan\Database\AbstractRepository;
use App\Model\User;

class UserRepository extends AbstractRepository
{
    protected string $entityClass = User::class;
    protected string $table = 'users';

    // Ajoutez vos méthodes personnalisées ici
}
```

### Quand utiliser un repository ?

**Utilisez un repository si :**
- ✅ Vous avez beaucoup de requêtes complexes
- ✅ Vous voulez séparer la logique de requête de la logique métier
- ✅ Vous préférez le pattern Data Mapper au pattern Active Record

**Restez dans le Model si :**
- ✅ Vos requêtes sont simples
- ✅ Vous préférez le pattern Active Record (plus simple)
- ✅ C'est une petite application

Voir la [Documentation des modèles](./model-architecture.md) pour plus de détails.

---

## 📝 Générer un FormType

### Commande

```bash
php bin/console make:form User
# ou
php bin/console make:form UserFormType
# ou
php bin/console make:form UserForm   # Génère UserFormType (pas de doublon)
```

### Ce qui est généré

Le générateur crée un FormType avec :
- ✅ Champs de base (name, email)
- ✅ Types de champs appropriés (TextType, EmailType)
- ✅ Classes Tailwind CSS par défaut
- ✅ Labels et placeholders
- ✅ Bouton de soumission

### Exemple de sortie

```bash
📝 Génération du FormType : User

✅ FormType généré avec succès : UserFormType.php
📁 Fichier : /path/to/src/Form/UserFormType.php
```

### Structure générée

```php
<?php

namespace App\Form;

use Ogan\Form\AbstractType;
use Ogan\Form\FormBuilder;
use Ogan\Form\Types\TextType;
use Ogan\Form\Types\EmailType;
use Ogan\Form\Types\SubmitType;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                // ...
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                // ...
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                // ...
            ]);
    }
}
```

---

## 📦 Générer un modèle

### Commande

```bash
php bin/console make:model User
# ou
php bin/console make:model  # Mode interactif complet
```

### Ce qui est généré

Le générateur crée un modèle avec :
- ✅ Propriétés privées avec types
- ✅ Getters et setters publics
- ✅ Structure compatible avec l'ORM
- ✅ Propriétés de base (id, name, createdAt, updatedAt)

### Exemple de sortie

```bash
📦 Génération du modèle : User

✅ Modèle généré avec succès : User.php
📁 Fichier : /path/to/src/Model/User.php
💡 N'oubliez pas de générer la migration : php bin/migrate make User
```

### Structure générée

```php
<?php

namespace App\Model;

use Ogan\Database\Model;

class User extends Model
{
    protected static ?string $primaryKey = 'id';

    // Propriétés
    private ?int $id = null;
    private ?string $name = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    // ...

    // Setters
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    // ...
}
```

---

## 🚀 Générer tout en une commande

### Commande

```bash
php bin/console make:all User
# ou
php bin/console make:all  # Mode interactif complet
```

### Mode interactif

La commande `all` utilise le **mode interactif** pour créer le modèle avec toutes ses propriétés et relations, puis génère automatiquement tous les fichiers nécessaires.

### Ce qui est généré

Cette commande génère automatiquement :
1. ✅ Le modèle (`User.php`) - **en mode interactif avec propriétés et relations**
2. ✅ Le repository (`UserRepository.php`) - **automatiquement**
3. ✅ Le FormType (`UserFormType.php`)
4. ✅ Le contrôleur (`UserController.php`)
5. ✅ Les relations inverses dans les modèles liés

### Exemple de sortie

```bash
$ php bin/make all Product

🛠️  Génération complète

🎨 Mode interactif activé

[Mode interactif pour créer le modèle avec propriétés et relations...]

📦 Génération du modèle : Product
   ✅ Modèle généré : Product.php

🔄 Génération des relations inverses...
   ✅ Relation inverse ajoutée dans Category : OneToMany vers Product

📚 Génération du repository...
   ✅ Repository généré : ProductRepository.php

📝 Génération du FormType...
   ✅ FormType généré : ProductFormType.php

🎮 Génération du contrôleur...
   ✅ Contrôleur généré : ProductController.php

✅ Génération complète terminée !
💡 N'oubliez pas de générer la migration : php bin/migrate make Product
```

### Workflow recommandé

```bash
# 1. Générer tout le code en mode interactif
php bin/make all Post
# → Mode interactif : ajouter propriétés et relations
# → Génère : Post.php + PostRepository.php + PostFormType.php + PostController.php

# 2. Générer la migration
php bin/migrate make Post

# 3. Exécuter la migration
php bin/migrate

# 4. Personnaliser le code généré selon vos besoins
```

---

## ⚙️ Options

### Option `--force`

Force la création même si le fichier existe déjà (écrase l'ancien fichier).

```bash
php bin/console make:controller User --force
php bin/console make:form User --force
php bin/console make:model User --force
php bin/console make:all User --force
```

**Note** : En mode interactif, si vous modifiez un modèle existant, le système force automatiquement l'écrasement pour préserver les nouvelles propriétés.

---

## 📝 Personnalisation

### Après génération

Les fichiers générés sont des **templates de base**. Vous devez :

1. **Modèles** : Ajouter vos propriétés spécifiques
2. **FormTypes** : Ajouter vos champs spécifiques
3. **Contrôleurs** : Compléter les méthodes TODO

### Exemple de personnalisation

```php
// Modèle généré
class Post extends Model
{
    // Ajoutez vos propriétés
    private ?string $title = null;
    private ?string $content = null;
    // ...
}

// FormType généré
class PostFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [...])
            ->add('content', TextareaType::class, [...])  // Ajoutez vos champs
            // ...
    }
}
```

---

## 💡 Bonnes pratiques

### 1. Ordre de génération

```bash
# 1. Modèle d'abord (mode interactif)
php bin/console make:model User

# 2. Migration
php bin/console migrate:make User

# 3. FormType
php bin/console make:form User

# 4. Contrôleur
php bin/console make:controller User
```

### 2. Utiliser `all` pour un démarrage rapide

```bash
# Génère tout d'un coup
php bin/console make:all User
php bin/console migrate:make User
php bin/console migrate
```

### 3. Personnaliser après génération

Les fichiers générés sont des **bases**. Personnalisez-les selon vos besoins :
- Ajoutez des propriétés au modèle
- Ajoutez des champs au FormType
- Complétez les méthodes du contrôleur

---

## 🔍 Détails techniques

### Normalisation des noms

Le générateur normalise automatiquement les noms :

- `user` → `UserController`
- `UserController` → `UserController`
- `user_controller` → `UserController`
- `PostFormType` → `PostFormType`

### Chemins par défaut

- **Contrôleurs** : `src/Controller/`
- **FormTypes** : `src/Form/`
- **Modèles** : `src/Model/`

### Conventions

- **Contrôleurs** : Suffixe `Controller` (ex: `UserController`)
- **FormTypes** : Suffixe `FormType` (ex: `UserFormType`)
- **Modèles** : Pas de suffixe (ex: `User`)

---

## 🎓 Concepts pédagogiques

### Pattern Generator

Le système utilise le **pattern Generator** :
- Classe abstraite `AbstractGenerator` pour les fonctionnalités communes
- Générateurs spécialisés pour chaque type de fichier
- Réutilisabilité et extensibilité

### DRY (Don't Repeat Yourself)

Au lieu de copier-coller du code, le générateur crée des templates cohérents.

### Convention over Configuration

Le générateur suit les conventions du framework, réduisant les erreurs.

---

## 📚 Ressources

- [Documentation des migrations](./migrations.md)
- [Documentation des FormTypes](./form-types.md)
- [Documentation des modèles](./model-architecture.md)
- [Architecture du framework](../architecture/)

