# ⚙️ Configuration - Ogan Framework

> Guide complet sur la configuration du framework

## 🚀 Configuration Minimale (Recommandée)

En production, vous n'avez besoin que de **4 variables** :

```env
APP_ENV=prod
APP_SECRET=votre-cle-secrete-64-caracteres-minimum
DATABASE_URL="mysql://user:pass@host:3306/database?charset=utf8mb4"
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

**Tout le reste est auto-configuré** selon `APP_ENV` ! ✨

---

## 🔄 Auto-configuration selon APP_ENV

Le framework configure automatiquement les paramètres selon l'environnement :

| Variable | `dev` (défaut) | `prod` | `test` |
|----------|----------------|--------|--------|
| `APP_DEBUG` | `true` | `false` | `true` |
| `SESSION_SECURE` | `false` | `true` | `false` |
| `SESSION_LIFETIME` | 7200 (2h) | 3600 (1h) | 7200 |
| `SESSION_SAMESITE` | `Lax` | `Strict` | `Lax` |
| `LOG_LEVEL` | `debug` | `error` | `warning` |
| `MAILER_DSN` | MailHog (1025) | - | - |

> **💡 Surcharge possible** : Ajoutez la variable dans `.env` pour remplacer le défaut automatique.

---

## 📋 Variables Disponibles

### Essentielles (4)

| Variable | Requis | Description |
|----------|--------|-------------|
| `APP_ENV` | ✅ | `dev`, `prod` ou `test` |
| `APP_SECRET` | ✅ prod | Clé secrète (CSRF, tokens) |
| `DATABASE_URL` | ✅ | URL de connexion BDD |
| `MAILER_DSN` | ⚡ | DSN du mailer (auto en dev) |

### Optionnelles

| Variable | Auto-configurée | Description |
|----------|-----------------|-------------|
| `APP_DEBUG` | ✅ | Afficher les erreurs |
| `SESSION_*` | ✅ | Configuration session |
| `LOG_LEVEL` | ✅ | Niveau de log |
| `ROUTER_BASE_PATH` | - | Préfixe des routes |

---

## 🔐 APP_SECRET

Clé obligatoire en production. Utilisée pour :
- Tokens CSRF
- Signature de cookies
- Autres sécurités cryptographiques

**Générer une clé :**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

> **⚠️ En production**, le framework refuse de démarrer si `APP_SECRET` n'est pas défini ou vaut `changeme-in-production`.

---

## 🗄️ DATABASE_URL

Format unifié style Symfony :

```env
# MySQL/MariaDB
DATABASE_URL="mysql://user:password@127.0.0.1:3306/database?charset=utf8mb4"

# PostgreSQL
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/database"

# SQLite
DATABASE_URL="sqlite:///var/db/app.db"
```

**Structure :**
```
driver://user:password@host:port/database?options
```

---

## 📧 MAILER_DSN

Format style Symfony Mailer :

```env
# Production (SMTP)
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# Gmail
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587

# Mailgun
MAILER_DSN=smtp://postmaster@domain:key@smtp.mailgun.org:587
```

> **En dev** : Si non défini, utilise automatiquement `smtp://127.0.0.1:1025` (MailHog).

---

## 🎯 Exemples Complets

### Développement (`.env`)

```env
APP_ENV=dev
APP_SECRET=dev-secret-not-important
DATABASE_URL="mysql://root:root@127.0.0.1:3306/myapp?charset=utf8mb4"
# MAILER_DSN auto → MailHog
```

### Production (`.env`)

```env
APP_ENV=prod
APP_SECRET=a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef12345678
DATABASE_URL="mysql://prod_user:S3cur3P@ss@db.example.com:3306/myapp_prod?charset=utf8mb4"
MAILER_DSN=smtp://noreply:secret@smtp.example.com:587
```

### Surcharger un défaut

```env
APP_ENV=dev
APP_DEBUG=false  # Surcharge : désactive debug même en dev
```

---

## 📊 Hiérarchie de Priorité

1. **Variables d'environnement** (`.env.local`) → Priorité maximale
2. **Variables d'environnement** (`.env`)
3. **Auto-configuration** selon `APP_ENV`
4. **Valeurs par défaut** dans le code

---

## ✅ Résumé

| Environnement | Variables minimales |
|---------------|---------------------|
| **Dev** | `APP_ENV` + `DATABASE_URL` |
| **Prod** | `APP_ENV` + `APP_SECRET` + `DATABASE_URL` + `MAILER_DSN` |
| **Test** | `APP_ENV` + `DATABASE_URL` |

**Tout le reste est automatique !** 🎉
