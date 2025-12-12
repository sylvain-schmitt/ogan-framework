<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🎨 VIEWINTERFACE - Interface pour le Moteur de Templates
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE DE CETTE INTERFACE
 * -----------------------
 * Définit le CONTRAT pour le système de templates (vues).
 * 
 * Un moteur de templates est responsable de :
 * - Charger des fichiers de templates (HTML + PHP)
 * - Injecter des variables dans les templates
 * - Gérer les layouts (héritage de templates)
 * - Gérer les blocs/sections réutilisables
 * - Rendre le HTML final
 * 
 * POURQUOI UNE INTERFACE ?
 * ------------------------
 * 
 * 1. FLEXIBILITÉ :
 *    On pourrait avoir différents moteurs :
 *    - PhpView : Templates PHP natif (notre cas)
 *    - TwigView : Utilise Twig
 *    - BladeView : Utilise Blade (Laravel)
 *    - JsonView : Rendu JSON au lieu de HTML
 * 
 * 2. TESTABILITÉ :
 *    Dans les tests, on peut créer un FakeView qui retourne
 *    toujours le même HTML sans charger de fichier
 * 
 * 3. PRINCIPE SOLID "S" (Single Responsibility) :
 *    Le View se concentre sur le rendu, pas sur la logique métier
 * 
 * CONCEPTS DE TEMPLATES
 * ---------------------
 * 
 * TEMPLATE SIMPLE :
 * <h1><?= $title ?></h1>
 * 
 * LAYOUT (template parent) :
 * <html>
 *   <body>
 *     <?php $this->section('content'); ?>
 *   </body>
 * </html>
 * 
 * PAGE (template enfant) :
 * <?php $this->layout('layouts/base.html.php'); ?>
 * <?php $this->start('content'); ?>
 *   <h1>Ma page</h1>
 * <?php $this->end(); ?>
 * 
 * PARTIAL (composant réutilisable) :
 * <?= $this->partial('partials/header.html.php', ['title' => 'Ogan']) ?>
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View;

/**
 * Interface pour le moteur de templates
 */
interface ViewInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * RENDRE UN TEMPLATE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Charge un fichier de template, injecte les variables, et retourne le HTML.
     * 
     * PROCESSUS :
     * 1. Charge le fichier template
     * 2. Extrait les variables dans le scope du template
     * 3. Exécute le PHP du template
     * 4. Capture le résultat (output buffering)
     * 5. Retourne le HTML généré
     * 
     * EXEMPLES :
     * // Template simple
     * $html = $view->render('home/index.html.php', [
     *     'title' => 'Accueil',
     *     'name' => 'Ogan'
     * ]);
     * 
     * // Dans le template (home/index.html.php) :
     * <h1><?= $title ?></h1>
     * <p>Bienvenue, <?= $name ?>!</p>
     * 
     * @param string $template Chemin relatif du template (ex: 'home/index.html.php')
     * @param array $data Variables à injecter dans le template
     * @return string Le HTML généré
     */
    public function render(string $template, array $data = []): string;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * DÉFINIR LE LAYOUT (template parent)
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Indique quel layout utiliser pour envelopper le contenu.
     * 
     * UTILISATION :
     * Dans un template enfant, on appelle :
     * <?php $this->layout('layouts/base.html.php'); ?>
     * 
     * Le contenu de ce template sera inséré dans le layout
     * aux endroits définis par section().
     * 
     * EXEMPLE DE LAYOUT (layouts/base.html.php) :
     * <!DOCTYPE html>
     * <html>
     * <head>
     *     <title><?= $this->section('title') ?></title>
     * </head>
     * <body>
     *     <?= $this->section('content') ?>
     * </body>
     * </html>
     * 
     * @param string $layout Chemin du layout (ex: 'layouts/base.html.php')
     * @return void
     */
    public function layout(string $layout): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * COMMENCER UN BLOC/SECTION
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Démarre la capture du contenu d'une section.
     * 
     * UTILISATION :
     * <?php $this->start('content'); ?>
     *   <h1>Mon contenu</h1>
     * <?php $this->end(); ?>
     * 
     * Le contenu entre start() et end() sera stocké et pourra être
     * affiché dans le layout avec section('content').
     * 
     * @param string $name Nom de la section
     * @return void
     */
    public function start(string $name): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * TERMINER UN BLOC/SECTION
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Termine la capture du contenu et le stocke.
     * 
     * Doit être appelé après start().
     * 
     * @return void
     */
    public function end(): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * AFFICHER UNE SECTION
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Affiche le contenu d'une section capturée avec start()/end().
     * 
     * UTILISATION :
     * Dans le layout :
     * <?= $this->section('content') ?>
     * 
     * Affichera le contenu défini dans le template enfant entre
     * start('content') et end().
     * 
     * @param string $name Nom de la section à afficher
     * @return string Le contenu de la section
     */
    public function section(string $name): string;

    /**
     * Génère une URL pour un asset (CSS, JS, Image)
     */
    public function asset(string $path): string;

    /**
     * Génère une URL depuis un nom de route
     * 
     * @param string $name Nom de la route
     * @param array $params Paramètres de la route
     * @param bool $absolute Générer une URL absolue
     * @return string URL générée
     */
    public function route(string $name, array $params = [], bool $absolute = false): string;

    /**
     * Génère une URL absolue ou relative
     * 
     * @param string $path Chemin
     * @param bool $absolute Générer une URL absolue
     * @return string URL générée
     */
    public function url(string $path = '', bool $absolute = false): string;

    /**
     * Génère une balise <link> pour un fichier CSS
     * 
     * @param string $path Chemin vers le fichier CSS
     * @param array $attributes Attributs additionnels
     * @return string Balise <link>
     */
    public function css(string $path, array $attributes = []): string;

    /**
     * Génère une balise <script> pour un fichier JS
     * 
     * @param string $path Chemin vers le fichier JS
     * @param array $attributes Attributs additionnels
     * @return string Balise <script>
     */
    public function js(string $path, array $attributes = []): string;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * MÉTHODES À AJOUTER PLUS TARD (Phase 5)
 * ---------------------------------------
 * 
 * Pour enrichir le système de templates :
 * 
 * - escape(string $value): string
 *   → Échapper automatiquement les variables (sécurité XSS)
 * 
 * - partial(string $path, array $data): string
 *   → Inclure un partial (composant réutilisable)
 * 
 * - exists(string $template): bool
 *   → Vérifier si un template existe
 * 
 * - extend(string $parent): void
 *   → Alias de layout() (plus intuitif)
 * 
 * - addGlobal(string $key, $value): void
 *   → Ajouter une variable disponible dans TOUS les templates
 * 
 * PATTERN : TEMPLATE METHOD
 * -------------------------
 * 
 * Le système layout/section utilise le "Template Method Pattern" :
 * 
 * 1. Le layout définit la STRUCTURE (le squelette)
 * 2. Les templates enfants définissent le CONTENU (la chair)
 * 3. Le moteur assemble les deux
 * 
 * C'est comme un formulaire à remplir :
 * - Le layout = le formulaire vide
 * - Les sections = les champs remplis
 * 
 * DIFFÉRENCE render() vs partial()
 * ---------------------------------
 * 
 * render() :
 * - Méthode principale
 * - Peut utiliser un layout
 * - Retourne le HTML complet (avec <html>, <body>...)
 * 
 * partial() (à ajouter) :
 * - Pour petits composants
 * - Pas de layout
 * - Retourne un fragment HTML
 * - Réutilisable partout
 * 
 * SÉCURITÉ XSS
 * ------------
 * 
 * IMPORTANT : Dans les templates PHP, TOUJOURS échapper les variables :
 * 
 * ❌ DANGEREUX :
 * <h1><?= $title ?></h1>
 * Si $title = "<script>alert('XSS')</script>", le script s'exécute !
 * 
 * ✅ SÉCURISÉ :
 * <h1><?= htmlspecialchars($title) ?></h1>
 * Le script devient du texte affiché, pas exécuté.
 * 
 * Plus tard, on ajoutera une méthode escape() pour simplifier :
 * <h1><?= $this->escape($title) ?></h1>
 * 
 * PROCHAINES ÉTAPES
 * -----------------
 * 1. Modifier View.php pour implémenter cette interface
 * 2. Vérifier que toutes les méthodes sont présentes
 * 3. Tester le rendu des templates
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
