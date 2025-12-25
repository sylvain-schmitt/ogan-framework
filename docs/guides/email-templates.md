# ✉️ Templates Email

> Personnaliser les emails de vérification et reset de mot de passe

## Emplacement des templates

Les templates email sont générés par `make:auth` dans :

```
templates/
└── emails/
    ├── verify_email.ogan      # Vérification d'email
    └── password_reset.ogan    # Reset de mot de passe
```

---

## Variables disponibles

| Variable | Description |
|----------|-------------|
| `{{ user.name }}` | Nom de l'utilisateur |
| `{{ user.email }}` | Email de l'utilisateur |
| `{{ url }}` | Lien de vérification/reset |
| `{{ appName }}` | Nom de l'application |

---

## Exemple : verify_email.ogan

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérifiez votre email</title>
</head>
<body>
    <h1>Bienvenue {{ user.name }} !</h1>
    
    <p>Merci de vous être inscrit sur {{ appName }}.</p>
    
    <p>Cliquez sur le lien ci-dessous pour vérifier votre email :</p>
    
    <p><a href="{{ url }}">Vérifier mon email</a></p>
    
    <p>Ce lien expire dans 24 heures.</p>
</body>
</html>
```

---

## Personnalisation

Modifiez directement les fichiers `.ogan` dans `templates/emails/` pour :

- ✅ Changer le design (CSS inline recommandé pour les emails)
- ✅ Ajouter votre logo
- ✅ Modifier les textes
- ✅ Ajouter des informations supplémentaires

> **💡 Astuce** : Utilisez du CSS inline pour une meilleure compatibilité avec les clients mail.

---

## Services associés

| Service | Template utilisé |
|---------|------------------|
| `EmailVerificationService` | `emails/verify_email.ogan` |
| `PasswordResetService` | `emails/password_reset.ogan` |

Ces services sont générés dans `src/Security/` par `make:auth`.
