<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📦 CONTAINERINTERFACE - Interface PSR-11
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * QU'EST-CE QU'UNE INTERFACE ?
 * ----------------------------
 * Une interface est un CONTRAT. Elle définit QUELLES méthodes une classe doit
 * avoir, mais PAS COMMENT elles fonctionnent.
 * 
 * ANALOGIE :
 * ---------
 * Imagine une prise électrique :
 * - L'INTERFACE = la forme de la prise (2 ou 3 trous)
 * - L'IMPLÉMENTATION = ce qu'il y a derrière le mur (câbles, circuit...)
 * 
 * Tant que ton appareil respecte la forme de la prise (l'interface),
 * tu peux le brancher, peu importe ce qu'il y a derrière le mur.
 * 
 * POURQUOI UNE INTERFACE ICI ?
 * -----------------------------
 * 1. PRINCIPE SOLID "D" (Dependency Inversion) :
 *    "Dépendre d'abstractions, pas d'implémentations concrètes"
 *    
 *    ❌ AVANT :
 *    class UserController {
 *        public function __construct(Container $container) {}
 *        // Dépend de la classe concrète Container
 *    }
 *    
 *    ✅ APRÈS :
 *    class UserController {
 *        public function __construct(ContainerInterface $container) {}
 *        // Dépend de l'interface, pas de l'implémentation
 *    }
 * 
 * 2. TESTABILITÉ :
 *    On peut créer un "fake" container pour les tests qui implémente
 *    la même interface, sans avoir besoin du vrai Container complexe.
 * 
 * 3. INTERCHANGEABILITÉ :
 *    Tu pourrais remplacer ton Container par Symfony Container ou PHP-DI
 *    tant qu'ils implémentent cette interface !
 * 
 * 4. STANDARD PSR-11 :
 *    C'est un standard PHP officiel. Tous les containers professionnels
 *    (Symfony, Laravel, PHP-DI) respectent cette interface.
 * 
 * PSR-11 : QU'EST-CE QUE C'EST ?
 * -------------------------------
 * PSR = PHP Standard Recommendation
 * C'est un groupe (PHP-FIG) qui définit des standards pour que tous les
 * frameworks PHP puissent parler le même langage.
 * 
 * PSR-11 définit comment un container d'injection de dépendances doit
 * fonctionner. Avec 2 méthodes simples :
 * - get($id)  : Récupère un service
 * - has($id)  : Vérifie si un service existe
 * 
 * DANS LE CODE CI-DESSOUS :
 * --------------------------
 * On définit UNIQUEMENT les méthodes que le Container DOIT avoir.
 * On ne dit PAS COMMENT elles fonctionnent (ça, c'est le job du Container).
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\DependencyInjection;

use Ogan\Exception\ContainerExceptionInterface;
use Ogan\Exception\NotFoundExceptionInterface;

/**
 * Interface pour le Container d'Injection de Dépendances
 * 
 * Conforme au standard PSR-11 (Container Interface)
 * 
 * @see https://www.php-fig.org/psr/psr-11/
 */
interface ContainerInterface
{
    
    /**
     * ───────────────────────────────────────────────────────────────────────
     * RÉCUPÉRER UN SERVICE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Trouve et retourne une instance du service demandé.
     * 
     * EXEMPLES D'UTILISATION :
     * ------------------------
     * $router = $container->get(Router::class);
     * $db = $container->get('database');
     * $mailer = $container->get('mailer');
     * 
     * COMPORTEMENT ATTENDU :
     * ----------------------
     * 1. Si le service existe déjà → le retourner (singleton)
     * 2. Si le service n'existe pas mais peut être créé → le créer et le retourner
     * 3. Si impossible de créer → lancer une exception
     * 
     * @param string $id Identifiant du service (souvent le nom de la classe)
     * 
     * @return mixed L'instance du service
     * 
     * @throws ContainerExceptionInterface
     *         Erreur générale du container (problème de création, etc.)
     * 
     * @throws NotFoundExceptionInterface
     *         Le service n'existe pas et ne peut pas être créé
     * 
     * ───────────────────────────────────────────────────────────────────────
     */
    public function get(string $id);

    /**
     * ───────────────────────────────────────────────────────────────────────
     * VÉRIFIER SI UN SERVICE EXISTE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Vérifie si le container peut fournir le service demandé.
     * 
     * EXEMPLES D'UTILISATION :
     * ------------------------
     * if ($container->has('mailer')) {
     *     $mailer = $container->get('mailer');
     *     $mailer->send(...);
     * }
     * 
     * COMPORTEMENT ATTENDU :
     * ----------------------
     * - Retourne TRUE si get($id) réussirait
     * - Retourne FALSE si get($id) lancerait une NotFoundExceptionInterface
     * 
     * IMPORTANT :
     * -----------
     * Cette méthode ne doit PAS lancer d'exception.
     * Elle retourne juste true/false.
     * 
     * @param string $id Identifiant du service
     * 
     * @return bool TRUE si le service existe, FALSE sinon
     * 
     * ───────────────────────────────────────────────────────────────────────
     */
    public function has(string $id): bool;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * DIFFÉRENCE INTERFACE vs CLASSE ABSTRAITE
 * -----------------------------------------
 * 
 * INTERFACE :
 * - Définit UNIQUEMENT des signatures de méthodes
 * - Pas de code, pas d'implémentation
 * - Une classe peut implémenter PLUSIEURS interfaces
 * - Utilise : implements
 * 
 * CLASSE ABSTRAITE :
 * - Peut avoir du code ET des méthodes abstraites
 * - Peut avoir des propriétés
 * - Une classe ne peut hériter que d'UNE classe abstraite
 * - Utilise : extends
 * 
 * QUAND UTILISER UNE INTERFACE ?
 * -------------------------------
 * ✅ Quand tu veux définir un CONTRAT
 * ✅ Quand plusieurs classes différentes doivent avoir les mêmes méthodes
 * ✅ Quand tu veux rendre ton code testable
 * ✅ Quand tu veux respecter SOLID
 * 
 * PROCHAINES ÉTAPES
 * -----------------
 * 1. Créer les exceptions (ContainerExceptionInterface, NotFoundExceptionInterface)
 * 2. Modifier Container.php pour implémenter cette interface
 * 3. Créer les autres interfaces (RequestInterface, RouterInterface...)
 * 
 * RESSOURCES
 * ----------
 * - PSR-11 officiel : https://www.php-fig.org/psr/psr-11/
 * - SOLID Principles : https://en.wikipedia.org/wiki/SOLID
 * - PHP Interfaces : https://www.php.net/manual/fr/language.oop5.interfaces.php
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
