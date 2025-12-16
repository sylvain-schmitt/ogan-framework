# 🔐 Sécurité & Autorisation - Guide

Le framework Ogan inclut un système RBAC (Role-Based Access Control) complet.

## 🎯 Concepts clés

| Composant | Description |
|-----------|-------------|
| **Rôle** | Permission globale (ex: `ROLE_ADMIN`) |
| **Voter** | Classe qui décide l'accès à une ressource |
| **IsGranted** | Attribut pour protéger une route |

---

## 🔑 Vérification des rôles

### Dans un contrôleur

```php
class AdminController extends AbstractController
{
    public function dashboard()
    {
        // Méthode 1: Vérification simple
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->accessDenied('Réservé aux administrateurs');
        }

        // Méthode 2: Exception automatique
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        return $this->render('admin/dashboard.ogan', ['user' => $user]);
    }
}
```

### Avec l'attribut #[IsGranted]

```php
use Ogan\Security\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // Toutes les méthodes nécessitent ROLE_ADMIN
}

class PostController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    public function create() { /* ... */ }

    #[IsGranted('edit', subject: 'post')]
    public function edit(Post $post) { /* ... */ }
}
```

---

## 📊 Hiérarchie des rôles

Configurez dans `config/parameters.yaml` :

```yaml
security:
  role_hierarchy:
    ROLE_ADMIN: [ROLE_USER]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN]
```

Un utilisateur avec `ROLE_ADMIN` aura automatiquement `ROLE_USER`.

---

## 🗳️ Créer un Voter personnalisé

```php
<?php

namespace App\Security\Voter;

use Ogan\Security\Authorization\AbstractVoter;
use Ogan\Security\UserInterface;
use App\Model\Post;

class PostVoter extends AbstractVoter
{
    public function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['edit', 'delete']) 
            && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, UserInterface $user): bool
    {
        /** @var Post $post */
        $post = $subject;

        return match ($attribute) {
            'edit' => $post->getAuthorId() === $user->getId(),
            'delete' => $user->hasRole('ROLE_ADMIN') || $post->getAuthorId() === $user->getId(),
            default => false,
        };
    }
}
```

### Enregistrer le Voter

```php
$checker = new AuthorizationChecker($user);
$checker->addVoter(new PostVoter());

if ($checker->isGranted('edit', $post)) {
    // Autorisé à modifier ce post
}
```

---

## 🚫 Page Access Denied

Template `templates/errors/403.ogan` affiché automatiquement.

```php
// Retourner une réponse 403 personnalisée
return $this->accessDenied('Vous n\'avez pas accès à cette ressource');
```

---

## ⚙️ Configuration complète

```yaml
# config/parameters.yaml
security:
  user_class: App\Model\User
  role_hierarchy:
    ROLE_ADMIN: [ROLE_USER]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN]
  access_denied_url: /login
```

---

## 📚 Ressources

- [Documentation Authentification](./authentication.md)
- [Documentation Middleware](./middlewares.md)
