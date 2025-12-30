# 🔐 Sécurité & Authentification

> Guide complet sur la sécurisation de vos applications Ogan : authentification, rôles, et contrôle d'accès.

## 📋 Table des matières

- [Authentification (Auth)](#authentification-auth)
    - [Génération automatique (`make:auth`)](#génération-automatique-makeauth)
    - [Configuration](#configuration)
    - [Utilisateurs & Rôles](#utilisateurs--rôles)
- [Contrôle d'Accès (Authorization)](#contrôle-dacces-authorization)
    - [Attribut `IsGranted`](#attribut-isgranted)
    - [Dans les Contrôleurs](#dans-les-contrôleurs)
    - [Dans les Templates](#dans-les-templates)
    - [Désactiver des routes](#désactiver-des-routes)
- [Support HTMX](#support-htmx)

---

## Authentification (Auth)

Le framework Ogan inclut un générateur complet pour mettre en place un système d'authentification robuste en quelques secondes.

### Génération automatique (`make:auth`)

La commande `make:auth` génère tout le nécessaire : Modèles, Contrôleurs, Vues, et Services.

```bash
# Générer le système d'authentification complet
php bin/console make:auth

# Option : avec support HTMX préconfiguré (recommandé)
php bin/console make:auth --htmx

# Appliquer les migrations pour créer les tables
php bin/console migrate
```

**Ce qui est généré :**
*   **Contrôleurs** : `SecurityController` (login/register/reset), `DashboardController`.
*   **Modèle** : `User` (avec gestion des rôles et hashage de mot de passe).
*   **Vues** : Pages de connexion, inscription, dashboard, profil, emails.
*   **Sécurité** : Services de vérification d'email et reset de mot de passe.

### Configuration

Les options principales se trouvent dans `config/parameters.yaml` :

```yaml
auth:
  # Envoyer un email de vérification à l'inscription (true/false)
  send_verification_email: false
  
  # Activer la fonctionnalité "Mot de passe oublié" (true/false)
  send_password_reset_email: true
  
  # Redirections
  login_redirect: /dashboard
  logout_redirect: /login
  
  # Redirections spécifiques par rôle (optionnel)
  role_redirects:
    ROLE_ADMIN: /admin
```

> **Note** : Pour l'envoi d'emails, n'oubliez pas de configurer `MAILER_DSN` dans votre fichier `.env`.

### Utilisateurs & Rôles

Les rôles sont stockés dans le champ `roles` du modèle `User` (tableau JSON).

```php
// Modèle User
$user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
$user->hasRole('ROLE_ADMIN'); // true
```

**Helper CLI : Créer un admin**
```bash
php bin/console make:admin
```

---

## Contrôle d'Accès (Authorization)

Une fois authentifiés, vous devez définir ce que les utilisateurs ont le droit de faire.

### Attribut `IsGranted`

C'est la méthode recommandée pour protéger vos contrôleurs.

**Sur une classe entière :**
```php
use Ogan\Security\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN', message: 'Espace réservé aux administrateurs.')]
class AdminController extends AbstractController
{
    // Toutes les méthodes ici nécessitent ROLE_ADMIN
}
```

**Sur une méthode spécifique :**
```php
#[Route('/articles/new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_AUTHOR')]
public function create(): Response
{
    // ...
}
```

### Dans les Contrôleurs

Vous pouvez vérifier les droits dynamiquement dans vos méthodes :

```php
public function edit(int $id): Response
{
    $article = Article::find($id);

    // Vérification explicite
    if (!$this->isGranted('ROLE_ADMIN') && $article->getAuthorId() !== $this->getUser()->getId()) {
        throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet article.');
    }

    // Version courte (lance une exception 403 si faux)
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
    // ...
}
```

### Dans les Templates

Utilisez la fonction `is_granted()` pour afficher du contenu conditionnel.

```html
<!-- Cacher un lien aux non-admins -->
{% if is_granted('ROLE_ADMIN') %}
    <a href="{{ route('admin_dashboard') }}" class="btn btn-danger">Administration</a>
{% endif %}

<!-- Affichage conditionnel complexe -->
{% if is_granted('ROLE_ADMIN') %}
    <span class="badge badge-admin">Admin</span>
{% elseif is_granted('ROLE_USER') %}
    <span class="badge badge-user">Membre</span>
{% else %}
    <span class="badge badge-guest">Visiteur</span>
{% endif %}
```

### Désactiver des routes

Il est parfois utile de désactiver temporairement des fonctionnalités (ex: maintenance ou feature flag) via la configuration.

**Dans `.env` :**
```env
REGISTRATION_ENABLED=false
```

**Dans le contrôleur :**
```php
public function register(): Response
{
    // Vérifie config('registration.enabled')
    $this->denyIfDisabled('registration', 'Les inscriptions sont temporairement fermées.');
    
    // ...
}
```

---

## Support HTMX

Le système d'authentification généré est compatible avec HTMX.

*   **Mode HTMX (`--htmx`)** : Ajoute automatiquement les scripts et configure le dashboard pour une navigation fluide (SPA-like) via AJAX.
*   **Barre de progression** : Incluse automatiquement pour les transitions de page.
*   **Formulaires** : Les formulaires de login/inscription fonctionnent de manière standard pour garantir la compatibilité maximale, mais peuvent être "boostés".

Si vous utilisez HTMX, le `HtmxHelper` injecte automatiquement les scripts nécessaires dans `layout.ogan` via `{{ htmx_script() }}`.
