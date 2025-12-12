# 🔐 Sessions et Cookies - Ogan Framework

> Guide complet sur la gestion des sessions et des cookies

## 📋 Vue d'ensemble

Ogan Framework utilise les sessions PHP natives avec une configuration sécurisée et personnalisable.

---

## 🍪 Cookies dans votre navigateur

### Cookies du Framework Ogan

**`PHPSESSID` ou `OGAN_SESSION`** (selon la configuration)
- **Type** : Cookie de session
- **Durée** : Session (supprimé à la fermeture du navigateur) ou selon `SESSION_LIFETIME`
- **Sécurité** : HttpOnly, SameSite=Lax
- **Contenu** : ID de session PHP (ex: `gog3gonvtl9s5atddfn4dv383u`)

### Cookies d'autres applications

Les cookies suivants **ne sont PAS** créés par Ogan Framework :

- **`pma_lang`** : Cookie de phpMyAdmin (interface MySQL)
- **`remember_me`** / **`REMEMBERME`** : Cookies d'une autre application (peut-être Symfony ou une autre app)

---

## ⚙️ Configuration

### Dans `config/parameters.php`

```php
'session' => [
    'name' => 'OGAN_SESSION',      // Nom du cookie
    'lifetime' => 7200,             // Durée (2h)
    'path' => '/',                  // Chemin
    'domain' => '',                 // Domaine
    'secure' => false,              // HTTPS uniquement
    'httponly' => true,             // Pas accessible en JS
    'samesite' => 'Lax',            // Protection CSRF
],
```

### Dans `.env`

```env
SESSION_NAME=OGAN_SESSION
SESSION_LIFETIME=7200
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE=false
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax
```

---

## 🔒 Paramètres de Sécurité

### `httponly` (Recommandé: `true`)

**Protection contre XSS** : Le cookie n'est pas accessible via JavaScript.

```php
// ❌ JavaScript ne peut PAS lire le cookie
document.cookie; // Ne contient pas PHPSESSID

// ✅ Seul PHP peut y accéder
$_SESSION['user_id'];
```

### `secure` (Production: `true`, Dev: `false`)

**HTTPS uniquement** : Le cookie n'est envoyé que via HTTPS.

```php
// En production
'SESSION_SECURE=true'  // Cookie uniquement via HTTPS

// En développement
'SESSION_SECURE=false' // Cookie via HTTP et HTTPS
```

### `samesite` (Recommandé: `Lax`)

**Protection CSRF** : Empêche l'envoi du cookie depuis d'autres sites.

- **`Strict`** : Cookie jamais envoyé depuis un autre site (le plus sécurisé)
- **`Lax`** : Cookie envoyé pour les liens GET depuis d'autres sites (équilibre sécurité/UX)
- **`None`** : Cookie toujours envoyé (nécessite `secure=true`)

---

## 💻 Utilisation dans le Code

### Dans un Contrôleur

```php
class UserController extends AbstractController
{
    public function login()
    {
        // Stocker dans la session
        $this->session->set('user_id', $user->id);
        $this->session->set('user_name', $user->name);
        
        // Récupérer
        $userId = $this->session->get('user_id');
        
        // Vérifier
        if ($this->session->has('user_id')) {
            // Utilisateur connecté
        }
        
        // Messages flash
        $this->session->setFlash('success', 'Connexion réussie !');
        $message = $this->session->getFlash('success');
        
        // Détruire la session
        $this->session->destroy();
    }
}
```

### Dans une Vue

```php
<?php if ($this->session->get('user_id')): ?>
    <p>Connecté en tant que : <?= $this->e($this->session->get('user_name')) ?></p>
<?php endif; ?>

<?php if ($this->session->hasFlash('success')): ?>
    <div class="alert">
        <?= $this->e($this->session->getFlash('success')) ?>
    </div>
<?php endif; ?>
```

---

## 🔍 Vérification des Cookies

### Dans le Navigateur

1. **Chrome/Edge** : F12 → Application → Cookies
2. **Firefox** : F12 → Stockage → Cookies
3. **Safari** : Développeur → Stockage → Cookies

### Vérifier la Configuration

```php
// Dans un contrôleur temporaire
public function debugSession()
{
    $session = $this->session;
    
    return $this->json([
        'session_id' => $session->getId(),
        'session_name' => session_name(),
        'cookie_params' => session_get_cookie_params(),
        'session_data' => $_SESSION,
    ]);
}
```

---

## 🛡️ Bonnes Pratiques

### 1. Régénérer l'ID de Session après Connexion

```php
public function login()
{
    // ... validation ...
    
    $this->session->set('user_id', $user->id);
    
    // Régénérer l'ID (protection contre fixation de session)
    $this->session->migrate();
}
```

### 2. Détruire la Session à la Déconnexion

```php
public function logout()
{
    $this->session->destroy();
    return $this->redirect('/login');
}
```

### 3. Ne Pas Stocker de Données Sensibles

```php
// ❌ Éviter
$this->session->set('password', $password);

// ✅ Préférer
$this->session->set('user_id', $user->id);
// Récupérer les autres données depuis la DB si nécessaire
```

### 4. Configurer pour la Production

```env
# Production
SESSION_SECURE=true      # HTTPS uniquement
SESSION_HTTPONLY=true    # Pas accessible en JS
SESSION_SAMESITE=Strict  # Protection maximale
SESSION_LIFETIME=3600    # 1 heure
```

---

## 🐛 Dépannage

### Le cookie n'apparaît pas

1. Vérifier que la session est démarrée : `$this->session->start()`
2. Vérifier la configuration : `session_get_cookie_params()`
3. Vérifier les headers : Le cookie doit être envoyé avant tout output

### Le cookie est supprimé trop tôt

1. Vérifier `SESSION_LIFETIME` dans `.env`
2. Vérifier `session.gc_maxlifetime` dans `php.ini`
3. Vérifier que le serveur ne redémarre pas trop souvent

### Erreur "Headers already sent"

Le cookie doit être configuré **avant** tout output HTML.

```php
// ✅ Correct
session_start();
echo "Hello";

// ❌ Erreur
echo "Hello";
session_start(); // Headers already sent!
```

---

## 📚 Ressources

- [PHP Sessions](https://www.php.net/manual/fr/book.session.php)
- [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [SameSite Cookies](https://developer.mozilla.org/fr/docs/Web/HTTP/Headers/Set-Cookie/SameSite)

---

## ✅ Checklist

- [ ] Session configurée dans `config/parameters.php`
- [ ] Variables d'environnement définies dans `.env`
- [ ] `httponly` activé (sécurité)
- [ ] `secure` activé en production
- [ ] `samesite` configuré (Lax ou Strict)
- [ ] ID de session régénéré après connexion
- [ ] Session détruite à la déconnexion

---

**Les sessions sont maintenant configurées de manière sécurisée !** 🔐

