# 👑 Commande make:admin

> Créer un utilisateur administrateur en ligne de commande

## Utilisation

### Mode interactif (recommandé)

```bash
php bin/console make:admin
```

Le système vous demandera :
- 📧 Email de l'admin
- 👤 Nom de l'admin
- 🔒 Mot de passe (avec confirmation)

### Mode ligne de commande

```bash
php bin/console make:admin --email=admin@example.com --name="John Doe" --password=secret123
```

---

## Exemple

```bash
$ php bin/console make:admin

╔══════════════════════════════════════════════════════════════╗
║  👑 Création d'un utilisateur administrateur                 ║
╚══════════════════════════════════════════════════════════════╝

📧 Email de l'admin : admin@monsite.com
👤 Nom de l'admin : Admin
🔒 Mot de passe : ********
🔒 Confirmer : ********

╔══════════════════════════════════════════════════════════════╗
║  ✅ Administrateur créé avec succès !                        ║
╠══════════════════════════════════════════════════════════════╣
║  📧 Email : admin@monsite.com                                ║
║  👤 Nom   : Admin                                            ║
║  🔑 Rôles : ROLE_ADMIN, ROLE_USER                            ║
╚══════════════════════════════════════════════════════════════╝
```

---

## Fonctionnalités

| Fonction | Description |
|----------|-------------|
| Validation email | Vérifie le format et l'unicité |
| Hashage mot de passe | Utilise `PasswordHasher` |
| Rôles | Ajoute automatiquement `ROLE_ADMIN` + `ROLE_USER` |
| Vérification email | Marque l'admin comme vérifié |

---

## Prérequis

- Le modèle `User` doit exister (`make:auth` exécuté)
- La table `users` doit exister (migrations exécutées)
