# 📝 Logging - Ogan Framework

> Système de logging PSR-3 avec channels, format JSON et rotation automatique

## 📖 Introduction

Le framework Ogan inclut un système de logging complet :
- Compatible PSR-3 (8 niveaux de log)
- Channels multiples (app, security, database, etc.)
- Format texte ou JSON
- Rotation automatique des fichiers
- Helpers globaux disponibles partout
- Logging automatique des exceptions

## 🚀 Usage rapide

```php
// Partout dans l'application
logger()->info('User logged in', ['user_id' => 123]);
logger()->error('Database error', ['sql' => $query]);
logger()->warning('Deprecated function called');
logger()->debug('API response received', ['data' => $response]);

// Shortcuts
log_info('Message simple');
log_error('Erreur', ['details' => $e->getMessage()]);
log_warning('Attention');
log_debug('Debug info');
```

## 🔌 Channels

Séparez vos logs par catégorie :

```php
// Channel spécifique
logger('security')->warning('Failed login', ['ip' => $ip]);
logger('database')->debug('Query executed', ['sql' => $query]);
logger('api')->info('Request received', ['endpoint' => $path]);

// Crée des fichiers séparés :
// - var/log/security.log
// - var/log/database.log
// - var/log/api.log
```

## 📋 Niveaux de log

| Niveau | Méthode | Description |
|--------|---------|-------------|
| `emergency` | `->emergency()` | Système inutilisable |
| `alert` | `->alert()` | Action immédiate requise |
| `critical` | `->critical()` | Erreur critique |
| `error` | `->error()` | Erreur d'exécution |
| `warning` | `->warning()` | Avertissement |
| `notice` | `->notice()` | Notice normale |
| `info` | `->info()` | Information |
| `debug` | `->debug()` | Debug (dev uniquement) |

## 🔧 Configuration

Le logger s'adapte automatiquement à l'environnement :

| Environnement | Niveau min | Format |
|---------------|-----------|--------|
| `dev` | `debug` | Texte |
| `prod` | `info` | JSON |

### Personnalisation manuelle

```php
use Ogan\Logger\Logger;

$logger = new Logger(
    logPath: '/var/log/myapp',
    minLevel: 'info',          // Ignore debug
    channel: 'custom',
    jsonFormat: true,          // Format JSON
    maxFileSize: 10485760,     // 10 Mo avant rotation
    maxFiles: 5                // Garde 5 fichiers
);
```

## 📁 Fichiers de log

```
var/log/
├── app.log         # Tous les logs (channel par défaut)
├── error.log       # Erreurs uniquement (error, critical, alert, emergency)
├── security.log    # Channel security
├── database.log    # Channel database
└── requests.log    # Requêtes HTTP (via LoggerMiddleware)
```

## 🔄 Rotation automatique

Les fichiers sont automatiquement renommés quand ils atteignent la taille max :

```
app.log      → app.log.1 → app.log.2 → ... → app.log.5 (supprimé)
```

Configuration par défaut : 10 Mo, 5 fichiers.

## 📝 Format des logs

### Format texte (dev)

```
[2024-12-20 15:49:33] app.INFO: User logged in {"user_id":123}
[2024-12-20 15:49:34] security.WARNING: Failed login {"ip":"192.168.1.1"}
```

### Format JSON (prod)

```json
{"timestamp":"2024-12-20T15:49:33+00:00","channel":"app","level":"INFO","message":"User logged in","context":{"user_id":123},"extra":{"url":"/login","method":"POST","ip":"127.0.0.1"}}
```

## 🚨 Logging automatique des exceptions

Toutes les exceptions non catchées sont automatiquement loguées dans `error.log` :

```php
// Automatiquement logué avec :
// - Message
// - Classe d'exception
// - Fichier et ligne
// - Stack trace
// - URL et méthode HTTP
```

### Logger manuellement une exception

```php
try {
    // Code risqué
} catch (Exception $e) {
    log_exception($e);
    // ou avec un channel
    log_exception($e, 'database');
}
```

## 💡 Bonnes pratiques

### 1. Utilisez le bon niveau

```php
// ❌ Mauvais
logger()->info('Erreur fatale !');

// ✅ Bon
logger()->critical('Database connection failed', [
    'host' => $host,
    'error' => $e->getMessage()
]);
```

### 2. Ajoutez du contexte

```php
// ❌ Mauvais
logger()->info('User logged in');

// ✅ Bon
logger()->info('User logged in', [
    'user_id' => $user->getId(),
    'ip' => $request->getClientIp(),
    'user_agent' => $request->getHeader('User-Agent')
]);
```

### 3. Ne loguez jamais de données sensibles

```php
// ❌ DANGER
logger()->info('Login attempt', ['password' => $password]);

// ✅ SÉCURISÉ
logger()->info('Login attempt', ['email' => $email]);
```

### 4. Utilisez les channels appropriés

```php
logger('security')->warning('Brute force detected');
logger('payment')->info('Transaction completed');
logger('mail')->error('Failed to send email');
```

## 🔍 Analyser les logs

```bash
# Dernières 20 entrées
tail -20 var/log/app.log

# Suivre en temps réel
tail -f var/log/app.log

# Filtrer les erreurs
grep "ERROR\|CRITICAL" var/log/app.log

# Comptage par niveau
grep -o "INFO\|WARNING\|ERROR" var/log/app.log | sort | uniq -c
```

## 📚 Référence API

### Helpers globaux

| Fonction | Description |
|----------|-------------|
| `logger(?string $channel)` | Retourne le Logger |
| `log_exception($e, $channel)` | Log une exception |
| `log_info($msg, $ctx)` | Shortcut pour info |
| `log_error($msg, $ctx)` | Shortcut pour error |
| `log_warning($msg, $ctx)` | Shortcut pour warning |
| `log_debug($msg, $ctx)` | Shortcut pour debug |

### Classe Logger

| Méthode | Description |
|---------|-------------|
| `channel(string $name)` | Change de channel |
| `withJsonFormat(bool)` | Active/désactive JSON |
| `log($level, $msg, $ctx)` | Log générique |
| `emergency/alert/...($msg, $ctx)` | Niveaux PSR-3 |
