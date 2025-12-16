# 🐞 Outils de Debug - Guide

Le framework Ogan inclut des outils de debug puissants pour faciliter le développement.

## 🔧 dump() et dd()

```php
// Affiche et continue l'exécution
dump($user);
dump($request, $response);

// Affiche et STOP (Die & Dump)
dd($user);
dd($a, $b, $c);

// Retourne le HTML sans l'afficher
$html = d($variable);
```

**Fonctionnalités :**
- ✅ Coloration syntaxique par type
- ✅ Arrays et objets dépliables
- ✅ Affichage du fichier et ligne d'appel
- ✅ Propriétés privées/protégées visibles

### Dans les templates (.ogan)

```twig
{# Dump une variable dans le template #}
{{ dump(user) }}

{# Dump plusieurs variables #}
{{ dump(users, request) }}
```

---

## 📊 Debug Bar

La barre de debug s'affiche automatiquement en bas de page en mode dev.

### Panneaux disponibles

| Icône | Panneau | Contenu |
|-------|---------|---------|
| ⏱️ | Temps | Temps d'exécution (ms) |
| 💾 | Mémoire | Mémoire utilisée/peak |
| 🗄️ | Queries | Requêtes SQL avec durée |
| 🛣️ | Route | Controller, action, params |
| 👤 | Utilisateur | Connecté/Guest, email |
| 📝 | Session | Données en session |
| ⚙️ | Config | PHP version, env |

### Activation/Désactivation

Dans `config/parameters.yaml` :

```yaml
debug:
  enabled: true      # Activer les outils de debug
  debug_bar: true    # Afficher la debug bar
```

La debug bar s'affiche uniquement si :
- `app.env = dev`
- `debug.enabled = true`
- `debug.debug_bar = true`

---

## 🚨 ErrorHandler amélioré

En mode dev, les erreurs affichent :
- ✅ **Code source** autour de l'erreur avec highlighting
- ✅ **Stack trace cliquable** (cliquez pour voir le code)
- ✅ **Variables de contexte** ($_GET, $_POST, $_SESSION, $_COOKIE, $_SERVER)
- ✅ **Bouton copier** l'erreur

---

## ⚙️ Configuration complète

```yaml
# config/parameters.yaml
debug:
  enabled: true           # Activer les outils de debug
  debug_bar: true         # Afficher la debug bar
  collect_queries: true   # Logger les requêtes SQL
  max_dump_depth: 10      # Profondeur max pour dump()
```

---

## 🔌 API Debug Bar (usage avancé)

```php
use Ogan\Debug\DebugBar;

// Ajouter un message personnalisé
DebugBar::addMessage('Mon message', 'info');

// Définir les infos utilisateur
DebugBar::setUser([
    'id' => $user->getId(),
    'email' => $user->getEmail()
]);

// Définir les infos de route
DebugBar::setRoute([
    'name' => 'user_profile',
    'controller' => 'UserController',
    'action' => 'profile'
]);
```
