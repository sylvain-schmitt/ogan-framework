# 🔐 Système d'Authentification - make:auth

La commande `make:auth` génère un système d'authentification complet avec dashboard, profil utilisateur et gestion des emails.

## 🚀 Utilisation

```bash
# Générer le système d'authentification complet
php bin/console make:auth

# Régénérer tous les fichiers (écrase les existants sauf migrations)
php bin/console make:auth --force

# Générer avec le support HTMX préconfiguré
php bin/console make:auth --htmx

# Puis exécuter les migrations
php bin/console migrate
```

---

## 📁 Fichiers générés

### Modèle et Services

| Fichier | Description |
|---------|-------------|
| `src/Model/User.php` | Modèle User avec UserInterface |
| `src/Security/UserAuthenticator.php` | Service d'authentification (login/register) |
| `src/Security/EmailVerificationService.php` | Service de vérification d'email |
| `src/Security/PasswordResetService.php` | Service de réinitialisation de mot de passe |
| `src/Repository/UserRepository.php` | Repository pour les requêtes User |

### Contrôleurs

| Fichier | Routes |
|---------|--------|
| `src/Controller/SecurityController.php` | `/login`, `/logout`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email/{token}` |
| `src/Controller/DashboardController.php` | `/dashboard`, `/profile`, `/profile/edit` |

### FormTypes

| Fichier | Utilisation |
|---------|-------------|
| `src/Form/LoginFormType.php` | Formulaire de connexion |
| `src/Form/RegisterFormType.php` | Formulaire d'inscription |
| `src/Form/ForgotPasswordFormType.php` | Formulaire mot de passe oublié |
| `src/Form/ResetPasswordFormType.php` | Formulaire reset mot de passe |
| `src/Form/ProfileFormType.php` | Formulaire de profil |

### Templates

| Dossier | Fichiers |
|---------|----------|
| `templates/security/` | `login.ogan`, `register.ogan`, `forgot_password.ogan`, `reset_password.ogan` |
| `templates/dashboard/` | `layout.ogan`, `index.ogan` |
| `templates/user/` | `profile.ogan`, `edit.ogan` |
| `templates/emails/` | `verify_email.ogan`, `password_reset.ogan` |
| `templates/components/` | `flashes.ogan` |
| `templates/components/dashboard/` | `sidebar.ogan`, `navbar.ogan` |

### Assets

| Fichier | Description |
|---------|-------------|
| `public/assets/js/theme.js` | Toggle dark mode |
| `public/assets/js/flashes.js` | Auto-dismiss des messages flash |

### Migrations

| Fichier | Tables |
|---------|--------|
| `{timestamp}_create_users_table.php` | Table `users` |
| `{timestamp}_create_remember_tokens_table.php` | Table `remember_tokens` |

---

## ⚙️ Configuration

Les options d'authentification sont dans `config/parameters.yaml` :

```yaml
auth:
  # Envoyer un email de vérification à l'inscription
  send_verification_email: false
  
  # Envoyer un email pour le reset de mot de passe
  send_password_reset_email: false
  
  # Redirections après login/logout
  login_redirect: /dashboard
  logout_redirect: /login
```

### Mode Email vs Mode Direct

**`send_verification_email: false`**
- L'utilisateur est vérifié automatiquement à l'inscription
- Aucun email de confirmation n'est envoyé

**`send_verification_email: true`**
- Un email de vérification est envoyé
- L'utilisateur doit cliquer sur le lien pour activer son compte

**`send_password_reset_email: false`**
- Le lien "Mot de passe oublié" n'apparaît pas sur la page de connexion
- L'accès direct à `/forgot-password` redirige avec un message d'erreur

**`send_password_reset_email: true`**
- Le lien "Mot de passe oublié" s'affiche
- Un email est envoyé avec le lien de réinitialisation

---

---

## ⚡ Support HTMX

Le système d'authentification supporte nativement HTMX avec le flag `--htmx`.

### Ce que cela change :

1.  **Script HTMX** : Ajouté automatiquement dans le `<head>` de `layout.ogan` via `htmx_script()`.
2.  **Navigation** : Utilisation standard pour les menus (pas de `hx-boost` sur les dropdowns pour éviter les conflits).
3.  **Sidebar** : Navigation AJAX possible sur la sidebar principale (optionnel).
4.  **Formulaires** : Préparés pour une soumission classique ou AJAX selon vos préférences.

Pour activer cette fonctionnalité après coup, ajoutez simplement le script HTMX dans `templates/dashboard/layout.ogan`.

---

## 📧 Configuration Email

Pour activer l'envoi d'emails, configurez le DSN dans `.env` :

```env
# Mailhog (développement)
MAILER_DSN=smtp://localhost:1025

# Gmail
MAILER_DSN=smtp://user:password@smtp.gmail.com:587

# Variables d'expéditeur
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME="Mon Application"
```

---

## 🔧 Architecture des Générateurs

La commande utilise une architecture modulaire avec 16 générateurs spécialisés dans `ogan/Console/Generator/Auth/` :

```
ogan/Console/Generator/Auth/
├── AuthGenerator.php                 # Orchestrateur principal
├── UserModelGenerator.php            # Model User
├── UserAuthenticatorGenerator.php    # Service auth
├── EmailVerificationServiceGenerator.php
├── PasswordResetServiceGenerator.php
├── UserRepositoryGenerator.php
├── SecurityControllerGenerator.php
├── DashboardControllerGenerator.php
├── AuthFormTypeGenerator.php         # 5 FormTypes
├── AuthMigrationGenerator.php        # Migrations
├── SecurityTemplateGenerator.php
├── EmailTemplateGenerator.php
├── DashboardTemplateGenerator.php
├── DashboardComponentGenerator.php
├── ProfileTemplateGenerator.php
└── JsAssetGenerator.php
```

Chaque générateur :
- Hérite de `AbstractGenerator`
- Gère un type de fichier spécifique
- Retourne les fichiers générés/ignorés

---

## 🎨 Personnalisation

### Ajouter des champs au formulaire d'inscription

Modifier `src/Form/RegisterFormType.php` :

```php
$builder
    ->add('phone', TextType::class, [
        'label' => 'Téléphone',
        'required' => false,
    ])
    // ...
```

### Ajouter des colonnes à l'utilisateur

1. Modifier `src/Model/User.php` pour ajouter les propriétés
2. Créer une nouvelle migration : `php bin/console migrate:make add_phone_to_users`
3. Exécuter : `php bin/console migrate`

### Personnaliser le dashboard

Modifier les templates dans `templates/dashboard/` :
- `layout.ogan` - Structure générale
- `index.ogan` - Page d'accueil du dashboard

---

## 🔍 Vérification

Après génération, testez le flux complet :

1. **Inscription** : `/register`
2. **Connexion** : `/login`
3. **Dashboard** : `/dashboard`
4. **Profil** : `/profile`
5. **Édition** : `/profile/edit`
6. **Déconnexion** : `/logout`

---

## 📚 Ressources

- [Guide de Configuration](./configuration.md)
- [Génération de Code](./code-generation.md)
- [Migrations](./migrations.md)
