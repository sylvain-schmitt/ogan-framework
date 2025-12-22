/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎮 OGANSTIMULUS - Système de Contrôleurs JavaScript Léger
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Inspiré de Stimulus (Hotwire), mais ultra-léger et sans dépendances.
 *
 * UTILISATION :
 * -------------
 * HTML :
 *   <div data-controller="flash" data-flash-timeout="5000">
 *     <span data-flash-target="message">Hello</span>
 *     <button data-action="click->flash#dismiss">×</button>
 *   </div>
 *
 * JavaScript :
 *   class FlashController extends Controller {
 *     static targets = ['message'];
 *
 *     connect() { this.autoDismiss(); }
 *     dismiss() { this.element.remove(); }
 *   }
 *
 * ═══════════════════════════════════════════════════════════════════════
 */

/**
 * Classe de base pour tous les contrôleurs
 */
export class Controller {
    static targets = [];

    constructor(element, application) {
        this.element = element;
        this.application = application;
        this._targets = {};
        this._bindTargets();
        this._bindActions();
    }

    /**
     * Appelé quand l'élément est connecté au DOM
     */
    connect() { }

    /**
     * Appelé quand l'élément est déconnecté du DOM
     */
    disconnect() { }

    /**
     * Accède à un data attribute du contrôleur
     * @param {string} key - Nom de l'attribut (sans le préfixe controller-)
     * @returns {string|null}
     */
    data(key) {
        const controllerName = this.constructor.identifier;
        return this.element.dataset[`${controllerName}${this._capitalize(key)}`] || null;
    }

    /**
     * Définit un data attribute
     */
    setData(key, value) {
        const controllerName = this.constructor.identifier;
        this.element.dataset[`${controllerName}${this._capitalize(key)}`] = value;
    }

    /**
     * Lie les targets (data-[controller]-target="name")
     */
    _bindTargets() {
        const controllerName = this.constructor.identifier;
        const targets = this.constructor.targets || [];

        targets.forEach(targetName => {
            // Cherche les éléments avec data-[controller]-target="targetName"
            const selector = `[data-${controllerName}-target="${targetName}"]`;

            // Définit un getter pour accéder au premier élément
            Object.defineProperty(this, `${targetName}Target`, {
                get: () => this.element.querySelector(selector),
                configurable: true
            });

            // Définit un getter pour accéder à tous les éléments
            Object.defineProperty(this, `${targetName}Targets`, {
                get: () => Array.from(this.element.querySelectorAll(selector)),
                configurable: true
            });

            // Vérifie si le target existe
            Object.defineProperty(this, `has${this._capitalize(targetName)}Target`, {
                get: () => this.element.querySelector(selector) !== null,
                configurable: true
            });
        });
    }

    /**
     * Lie les actions (data-action="event->controller#method")
     */
    _bindActions() {
        const controllerName = this.constructor.identifier;
        const actionElements = this.element.querySelectorAll('[data-action]');

        actionElements.forEach(el => {
            const actions = el.dataset.action.split(' ');

            actions.forEach(action => {
                const match = action.match(/^(\w+)->(\w+)#(\w+)$/);
                if (!match) return;

                const [, eventName, targetController, methodName] = match;

                if (targetController !== controllerName) return;
                if (typeof this[methodName] !== 'function') {
                    console.warn(`OganStimulus: Method "${methodName}" not found in ${controllerName}`);
                    return;
                }

                el.addEventListener(eventName, (e) => {
                    this[methodName](e);
                });
            });
        });

        // Actions sur l'élément racine
        if (this.element.dataset.action) {
            const actions = this.element.dataset.action.split(' ');

            actions.forEach(action => {
                const match = action.match(/^(\w+)->(\w+)#(\w+)$/);
                if (!match) return;

                const [, eventName, targetController, methodName] = match;

                if (targetController !== controllerName) return;
                if (typeof this[methodName] !== 'function') return;

                this.element.addEventListener(eventName, (e) => {
                    this[methodName](e);
                });
            });
        }
    }

    _capitalize(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
}

/**
 * Application principale OganStimulus
 */
export class Application {
    constructor() {
        this.controllers = new Map();
        this.instances = new WeakMap();
    }

    /**
     * Démarre l'application (point d'entrée)
     */
    static start() {
        const app = new Application();

        // Initialise quand le DOM est prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => app.connect());
        } else {
            app.connect();
        }

        return app;
    }

    /**
     * Enregistre un contrôleur
     * @param {string} name - Nom du contrôleur (utilisé dans data-controller)
     * @param {typeof Controller} controllerClass - Classe du contrôleur
     */
    register(name, controllerClass) {
        controllerClass.identifier = name;
        this.controllers.set(name, controllerClass);

        // Connecte les éléments déjà présents
        this._connectController(name);
    }

    /**
     * Connecte tous les contrôleurs au DOM
     */
    connect() {
        this.controllers.forEach((_, name) => {
            this._connectController(name);
        });

        // Observer les mutations DOM pour les éléments ajoutés dynamiquement
        this._observeMutations();
    }

    /**
     * Rafraîchit tous les contrôleurs (utile après HTMX swap)
     */
    refresh() {
        this.controllers.forEach((_, name) => {
            this._connectController(name);
        });
    }

    /**
     * Connecte un contrôleur spécifique
     */
    _connectController(name) {
        const ControllerClass = this.controllers.get(name);
        if (!ControllerClass) return;

        const elements = document.querySelectorAll(`[data-controller~="${name}"]`);

        elements.forEach(element => {
            // Évite de reconnecter un élément déjà connecté
            if (this._hasInstance(element, name)) return;

            const instance = new ControllerClass(element, this);
            this._setInstance(element, name, instance);
            instance.connect();
        });
    }

    /**
     * Vérifie si un élément a déjà une instance
     */
    _hasInstance(element, name) {
        const instances = this.instances.get(element);
        return instances && instances.has(name);
    }

    /**
     * Stocke une instance
     */
    _setInstance(element, name, instance) {
        if (!this.instances.has(element)) {
            this.instances.set(element, new Map());
        }
        this.instances.get(element).set(name, instance);
    }

    /**
     * Observe les mutations DOM pour les éléments dynamiques
     */
    _observeMutations() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                // Nouveaux éléments ajoutés
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;

                    // Vérifie si le nœud lui-même a un data-controller
                    if (node.dataset && node.dataset.controller) {
                        this._connectElementControllers(node);
                    }

                    // Vérifie les descendants
                    if (node.querySelectorAll) {
                        node.querySelectorAll('[data-controller]').forEach(el => {
                            this._connectElementControllers(el);
                        });
                    }
                });

                // Éléments supprimés
                mutation.removedNodes.forEach(node => {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;
                    this._disconnectElementControllers(node);
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Connecte tous les contrôleurs d'un élément
     */
    _connectElementControllers(element) {
        const controllerNames = (element.dataset.controller || '').split(/\s+/);

        controllerNames.forEach(name => {
            if (!name || !this.controllers.has(name)) return;
            if (this._hasInstance(element, name)) return;

            const ControllerClass = this.controllers.get(name);
            const instance = new ControllerClass(element, this);
            this._setInstance(element, name, instance);
            instance.connect();
        });
    }

    /**
     * Déconnecte tous les contrôleurs d'un élément
     */
    _disconnectElementControllers(element) {
        const instances = this.instances.get(element);
        if (!instances) return;

        instances.forEach(instance => {
            if (typeof instance.disconnect === 'function') {
                instance.disconnect();
            }
        });

        this.instances.delete(element);
    }
}

// Export par défaut
export default { Application, Controller };
