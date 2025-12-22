# 🎮 OganStimulus - Système de Contrôleurs JS

OganStimulus est un système léger de contrôleurs JavaScript inspiré de [Stimulus](https://stimulus.hotwired.dev/) (Hotwire).

## Installation

Les assets sont automatiquement installés via Composer :

```bash
composer create-project ogan/skeleton mon-projet
# assets:install est exécuté automatiquement
```

Pour réinstaller manuellement :

```bash
php bin/console assets:install          # Dev (symlinks)
php bin/console assets:install --env=prod  # Production (copies)
php bin/console assets:install --update    # Met à jour HTMX
```

---

## Structure des fichiers

```
assets/js/                     # Sources (versionnées)
├── app.js                     # Point d'entrée
├── ogan-stimulus.js           # Core system
└── controllers/               # Contrôleurs
    ├── flash_controller.js
    ├── theme_controller.js
    └── sidebar_controller.js

public/assets/js/              # Symlinks (non versionnés)
├── app.js → ../../assets/js/app.js
├── htmx.min.js                # Téléchargé automatiquement
└── ...
```

---

## Utilisation

### HTML

```html
<div data-controller="flash" data-flash-timeout="5000">
    <span data-flash-target="message">Message</span>
    <button data-action="click->flash#dismiss">×</button>
</div>
```

### Syntaxe

| Attribut | Description |
|----------|-------------|
| `data-controller="nom"` | Lie l'élément à un contrôleur |
| `data-nom-target="cible"` | Définit un target accessible via `this.cibleTarget` |
| `data-action="event->controller#method"` | Lie un événement à une méthode |

---

## Créer un contrôleur

### 1. Créer le fichier

```javascript
// assets/js/controllers/modal_controller.js
import { Controller } from '../ogan-stimulus.js';

export default class ModalController extends Controller {
    static targets = ['dialog'];

    connect() {
        // Appelé quand l'élément est attaché au DOM
        console.log('Modal connecté');
    }

    open() {
        this.dialogTarget.classList.remove('hidden');
    }

    close() {
        this.dialogTarget.classList.add('hidden');
    }
}
```

### 2. Enregistrer dans app.js

```javascript
// assets/js/app.js
import ModalController from './controllers/modal_controller.js';

app.register('modal', ModalController);
```

### 3. Utiliser dans le HTML

```html
<div data-controller="modal">
    <button data-action="click->modal#open">Ouvrir</button>
    
    <div data-modal-target="dialog" class="hidden">
        <p>Contenu du modal</p>
        <button data-action="click->modal#close">Fermer</button>
    </div>
</div>
```

### 4. Recréer les symlinks

```bash
php bin/console assets:install
```

---

## API Controller

### Lifecycle

| Méthode | Description |
|---------|-------------|
| `connect()` | Appelé quand l'élément est attaché au DOM |
| `disconnect()` | Appelé quand l'élément est retiré |

### Targets

```javascript
static targets = ['message', 'button'];

// Accès
this.messageTarget;       // Premier élément
this.messageTargets;      // Tous les éléments
this.hasMessageTarget;    // Boolean
```

### Data

```html
<div data-controller="flash" data-flash-timeout="5000">
```

```javascript
this.data('timeout');       // "5000"
this.setData('timeout', 10000);
```

### Element

```javascript
this.element;  // L'élément racine du contrôleur
```

---

## Contrôleurs inclus

### flash

Auto-dismiss des messages flash après délai.

```html
<div data-controller="flash" data-flash-timeout="5000">
    <span data-flash-target="message">{{ message }}</span>
    <button data-action="click->flash#dismiss">×</button>
</div>
```

### theme

Toggle dark mode avec persistence localStorage.

```html
<button data-controller="theme" data-action="click->theme#toggle">
    <svg data-theme-target="darkIcon">...</svg>
    <svg data-theme-target="lightIcon">...</svg>
</button>
```

### sidebar

Toggle menu mobile.

```html
<button data-controller="sidebar" data-action="click->sidebar#toggle">
    Menu
</button>
```

---

## Compatibilité HTMX

OganStimulus est automatiquement compatible avec HTMX :

```javascript
// Dans app.js (déjà configuré)
document.addEventListener('htmx:afterSwap', () => app.refresh());
document.addEventListener('htmx:load', () => app.refresh());
```

Les contrôleurs sont réinitialisés après un swap HTMX.
