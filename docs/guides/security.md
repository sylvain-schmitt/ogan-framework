# 🔐 Sécurité & Contrôle d'Accès

> Protéger vos routes avec des rôles et permissions

## Table des matières

- [Configuration des rôles](#configuration-des-rôles)
- [Attribut IsGranted](#attribut-isgranted)
- [Méthodes de contrôle](#méthodes-de-contrôle)
- [Redirection après login](#redirection-après-login)
- [Désactiver des routes](#désactiver-des-routes)

---

## Configuration des rôles

Les rôles sont stockés dans le champ `roles` du User (JSON).

```php
// Modèle User
$user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
$user->hasRole('ROLE_ADMIN'); // true
```

### Création d'un admin

```bash
php bin/console make:admin
```

---

## Attribut IsGranted

### Sur une classe (toutes les routes)

```php
use Ogan\Security\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN', message: 'Accès réservé aux admins.')]
class DashboardController extends AbstractController
{
    // Toutes les routes nécessitent ROLE_ADMIN
}
```

### Sur une méthode (une seule route)

```php
#[Route('/articles/new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_AUTHOR', message: 'Vous devez être auteur.')]
public function newArticle(): Response
{
    // ...
}
```

### Comportement

| Situation | Résultat |
|-----------|----------|
| Non connecté | Redirige vers `/login` |
| Connecté sans le rôle | Affiche page 403 |
| Connecté avec le rôle | Accès autorisé ✅ |

---

## Affichage conditionnel dans les templates

Utilisez `is_granted()` pour afficher du contenu selon les rôles :

```html
{% if is_granted('ROLE_ADMIN') %}
    <nav class="admin-nav">
        <a href="/dashboard">Dashboard</a>
        <a href="/articles">Gérer les articles</a>
        <a href="/categories">Gérer les catégories</a>
    </nav>
{% endif %}

{% if is_granted('ROLE_USER') %}
    <a href="/profile">Mon profil</a>
{% endif %}

<!-- Navigation conditionnelle complète -->
<nav>
    {% if is_granted('ROLE_ADMIN') %}
        <a href="/dashboard">Admin</a>
    {% elseif is_granted('ROLE_AUTHOR') %}
        <a href="/my-articles">Mes articles</a>
    {% else %}
        <a href="/">Accueil</a>
    {% endif %}
</nav>
```

### Avantages

- ✅ **Réutilisable** : Le même layout peut servir pour admin et e-commerce
- ✅ **Sécurisé** : Le contenu est masqué côté serveur, pas juste CSS
- ✅ **Simple** : Un seul helper pour toutes les vérifications

---

## Méthodes de contrôle

### Dans un contrôleur

```php
// Vérifier un rôle
if ($this->isGranted('ROLE_ADMIN')) {
    // ...
}

// Bloquer si pas le rôle (lance AccessDeniedException)
$this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Accès admin requis.');

// Retourner une réponse 403 directement
return $this->accessDenied('Accès refusé.');
```

---

## Redirection après login

### Configuration dans `parameters.yaml`

```yaml
auth:
  login_redirect: /              # Défaut pour les utilisateurs
  logout_redirect: /login

  # Redirection par rôle (optionnel)
  role_redirects:
    ROLE_ADMIN: /dashboard       # Admins → dashboard
    ROLE_AUTHOR: /my-articles    # Auteurs → leurs articles
```

### Comment ça fonctionne

1. Après login, le système vérifie les rôles de l'utilisateur
2. Le premier rôle qui match dans `role_redirects` définit l'URL
3. Si aucun rôle ne match, `login_redirect` est utilisé

---

## Désactiver des routes

### Via `.env`

```env
REGISTRATION_ENABLED=false
CONTACT_ENABLED=false
```

### Dans le contrôleur

```php
// Méthode 1 : denyIfDisabled (recommandée)
$this->denyIfDisabled('registration', 'Les inscriptions sont fermées.');

// Méthode 2 : denyAccessIf (plus flexible)
$this->denyAccessIf(!Config::get('registration.enabled', true), 'Fermé.');

// Méthode 3 : Réponse 403 directe
if (!Config::get('registration.enabled', true)) {
    return $this->accessDenied('Inscriptions fermées.');
}
```

---

## Page 403 personnalisée

Créez `templates/errors/403.ogan` :

```html
{% extend 'layouts/base.ogan' %}

{% block body %}
<div class="error-page text-center py-20">
    <h1 class="text-4xl font-bold">🚫 403</h1>
    <p class="mt-4">{{ message }}</p>
    <a href="/" class="btn-primary mt-6">Retour à l'accueil</a>
</div>
{% endblock %}
```

La page 403 hérite du layout et a accès à :
- `{{ message }}` - Le message d'erreur
- `{{ app.user }}` - L'utilisateur connecté
- `{{ path('route_name') }}` - Les helpers de route
