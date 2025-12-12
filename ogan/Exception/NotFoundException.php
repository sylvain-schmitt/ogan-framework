<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🔍 NOTFOUNDEXCEPTION - Service Introuvable
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Exception lancée quand un service demandé n'existe PAS dans le container
 * et ne peut PAS être créé automatiquement.
 * 
 * DIFFÉRENCE AVEC ContainerException
 * -----------------------------------
 * 
 * ContainerException :
 * - Le service EXISTE mais on ne peut pas le créer
 * - Problème de construction, dépendance manquante, etc.
 * 
 * NotFoundException :
 * - Le service N'EXISTE PAS du tout
 * - Pas dans $services, pas une classe existante
 * 
 * EXEMPLES D'UTILISATION
 * ----------------------
 * 
 * try {
 *     $mailer = $container->get('mailer');
 * } catch (NotFoundException $e) {
 *     // Le service 'mailer' n'existe pas
 *     // → On peut utiliser un fallback
 *     $mailer = new DefaultMailer();
 * } catch (ContainerException $e) {
 *     // Autre erreur du container
 *     // → C'est plus grave, on log
 *     error_log($e->getMessage());
 *     throw $e;
 * }
 * 
 * QUAND LANCER CETTE EXCEPTION ?
 * -------------------------------
 * Dans Container::get($id), si :
 * 1. !isset($this->services[$id])        // Pas de factory
 * 2. !isset($this->instances[$id])       // Pas d'instance existante
 * 3. !class_exists($id)                  // Pas une classe
 * 
 * → Impossible de fournir ce service : throw NotFoundException
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

use Exception;
use Ogan\Exception\NotFoundExceptionInterface;

/**
 * Exception lancée quand un service demandé n'existe pas
 * 
 * Conforme PSR-11
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * CLASSE VIDE (POUR L'INSTANT)
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Comme ContainerException, cette classe hérite de tout ce dont elle a
     * besoin depuis Exception.
     * 
     * Son rôle principal est d'avoir un NOM et un TYPE spécifiques pour
     * permettre des catch précis.
     * 
     * AMÉLIORATION POSSIBLE
     * ---------------------
     * On pourrait ajouter l'ID du service non trouvé :
     * 
     * ```php
     * class NotFoundException extends Exception ... {
     *     private string $serviceId;
     *     
     *     public static function forService(string $id): self {
     *         $exception = new self("Service '$id' not found in container");
     *         $exception->serviceId = $id;
     *         return $exception;
     *     }
     *     
     *     public function getServiceId(): string {
     *         return $this->serviceId;
     *     }
     * }
     * 
     * // Utilisation
     * throw NotFoundException::forService($id);
     * ```
     * 
     * Mais pour l'instant, simple est mieux !
     * 
     * ───────────────────────────────────────────────────────────────────────
     */
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * HIÉRARCHIE COMPLÈTE
 * -------------------
 * 
 * ContainerExceptionInterface
 *   └── NotFoundExceptionInterface
 *         └── NotFoundException (notre classe)
 *               extends Exception
 * 
 * POURQUOI IMPLÉMENTER NotFoundExceptionInterface ?
 * --------------------------------------------------
 * 
 * Grâce à l'héritage d'interfaces :
 * - NotFoundExceptionInterface extends ContainerExceptionInterface
 * 
 * Donc NotFoundException :
 * - Implémente NotFoundExceptionInterface (explicite)
 * - Implémente AUSSI ContainerExceptionInterface (implicite)
 * 
 * CONSÉQUENCE :
 * -------------
 * 
 * ```php
 * $e = new NotFoundException("Not found");
 * 
 * // Tous ces checks retournent TRUE :
 * $e instanceof NotFoundException                  // ✅
 * $e instanceof NotFoundExceptionInterface         // ✅
 * $e instanceof ContainerExceptionInterface        // ✅
 * $e instanceof Exception                          // ✅
 * $e instanceof Throwable                          // ✅
 * ```
 * 
 * C'est comme dire :
 * - Un chat est un chat
 * - Un chat est un félin
 * - Un chat est un mammifère
 * - Un chat est un animal
 * 
 * MESSAGE D'ERREUR UTILE
 * ----------------------
 * 
 * Bon message :
 * ✅ "Service 'mailer' not found in container"
 * ✅ "Class 'App\Service\Mailer' does not exist"
 * 
 * Mauvais message :
 * ❌ "Not found"
 * ❌ "Error"
 * 
 * Un bon message aide au debugging !
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
