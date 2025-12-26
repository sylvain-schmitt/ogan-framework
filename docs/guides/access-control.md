# 🚫 Contrôle d'Accès aux Routes

> Désactiver des fonctionnalités via configuration

## Configuration dans `.env`

```env
# Désactiver les inscriptions
REGISTRATION_ENABLED=false

# Désactiver le formulaire de contact
CONTACT_ENABLED=false

# Désactiver le reset de mot de passe
PASSWORD_RESET_ENABLED=false
```

---

## Utilisation dans les contrôleurs

### Méthode 1 : `denyIfDisabled()` (Recommandée)

```php
#[Route('/register', 'register')]
public function register(Request $request): Response
{
    // Bloque si REGISTRATION_ENABLED=false dans .env
    $this->denyIfDisabled('registration', 'Les inscriptions sont fermées.');
    
    // ... reste du code
}
```

### Méthode 2 : `denyAccessIf()` (Plus flexible)

```php
#[Route('/register', 'register')]
public function register(Request $request): Response
{
    // Condition personnalisée
    $this->denyAccessIf(
        !Config::get('registration.enabled', true),
        'Les inscriptions sont fermées.'
    );
    
    // ... reste du code
}
```

### Méthode 3 : Réponse 403 directe

```php
#[Route('/register', 'register')]
public function register(Request $request): Response
{
    if (!Config::get('registration.enabled', true)) {
        return $this->accessDenied('Les inscriptions sont fermées.');
    }
    
    // ... reste du code
}
```

---

## Template 403 personnalisé

Modifiez `templates/errors/403.ogan` :

```html
{% extend 'layout.ogan' %}

{% block body %}
<div class="error-page">
    <h1>🚫 Accès refusé</h1>
    <p>{{ message }}</p>
    <a href="/">Retour à l'accueil</a>
</div>
{% endblock %}
```

---

## Résumé des méthodes

| Méthode | Usage |
|---------|-------|
| `denyIfDisabled('feature')` | Vérifie `FEATURE_ENABLED` dans config |
| `denyAccessIf(condition)` | Condition booléenne personnalisée |
| `accessDenied(message)` | Retourne directement une Response 403 |

---

## Réactiver une fonctionnalité

Il suffit de changer la valeur dans `.env` :

```env
# Avant (désactivé)
REGISTRATION_ENABLED=false

# Après (réactivé)
REGISTRATION_ENABLED=true
```

Aucun code à modifier ! 🎉
