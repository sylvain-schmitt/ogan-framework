<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️ CONTAINEREXCEPTION - Classe Concrète d'Exception
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * DIFFÉRENCE INTERFACE vs CLASSE CONCRÈTE
 * ----------------------------------------
 * 
 * INTERFACE (ContainerExceptionInterface) :
 * - Définit le CONTRAT : "Cette exception doit exister"
 * - Ne contient AUCUN code
 * - Ne peut pas être instanciée directement
 * 
 * CLASSE CONCRÈTE (ContainerException) :
 * - IMPLÉMENTE l'interface
 * - Contient le CODE réel
 * - Peut être instanciée : new ContainerException("message")
 * - C'est elle qu'on va LANCER (throw)
 * 
 * POURQUOI extends Exception ?
 * ----------------------------
 * En PHP, pour créer une exception, on DOIT hériter de la classe Exception
 * (ou d'une de ses sous-classes).
 * 
 * Exception est une classe PHP native qui fournit :
 * - Le message d'erreur
 * - Le code d'erreur
 * - La stack trace (trace d'exécution)
 * - L'exception précédente (chaînage)
 * 
 * POURQUOI implements ContainerExceptionInterface ?
 * --------------------------------------------------
 * Pour respecter le contrat PSR-11 !
 * 
 * Comme ça, notre exception :
 * 1. Hérite de Exception (pour avoir toutes les fonctionnalités)
 * 2. Implémente ContainerExceptionInterface (pour le type safety)
 * 
 * EXEMPLE D'UTILISATION
 * ---------------------
 * ```php
 * // Dans Container.php
 * if ($probleme) {
 *     throw new ContainerException("Impossible de créer le service");
 * }
 * 
 * // Dans ton code
 * try {
 *     $service = $container->get('mailer');
 * } catch (ContainerException $e) {
 *     echo "Erreur : " . $e->getMessage();
 * }
 * ```
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

use Exception;
use Ogan\Exception\ContainerExceptionInterface;

/**
 * Exception générale lancée par le Container
 * 
 * Utilisée pour toutes les erreurs du container SAUF "service non trouvé"
 * (qui utilise NotFoundException)
 * 
 * Exemples de cas d'usage :
 * - Impossible de construire le service (dépendance manquante)
 * - Dépendance circulaire détectée
 * - Erreur de Reflection
 * - Configuration invalide
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * CLASSE VIDE !
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Tu remarques que le corps de la classe est vide ?
     * 
     * POURQUOI ?
     * ----------
     * Tout le code nécessaire est DÉJÀ dans la classe parente Exception :
     * - __construct($message, $code, $previous)
     * - getMessage()
     * - getCode()
     * - getFile()
     * - getLine()
     * - getTrace()
     * - getPrevious()
     * - __toString()
     * 
     * On n'a RIEN d'autre à ajouter !
     * 
     * NOTRE CLASSE APPORTE QUOI ALORS ?
     * ----------------------------------
     * 1. Un NOM spécifique (ContainerException)
     * 2. Le TYPE (implémente ContainerExceptionInterface)
     * 3. La possibilité de catcher spécifiquement :
     *    catch (ContainerException $e)
     * 
     * SI BESOIN PLUS TARD
     * -------------------
     * On pourrait ajouter :
     * - Des propriétés spécifiques (ex: $serviceId)
     * - Des méthodes helper (ex: getServiceId())
     * - Un formatage personnalisé du message
     * 
     * Exemple :
     * ```php
     * class ContainerException extends Exception ... {
     *     private string $serviceId;
     *     
     *     public function __construct(string $message, string $serviceId) {
     *         parent::__construct($message);
     *         $this->serviceId = $serviceId;
     *     }
     *     
     *     public function getServiceId(): string {
     *         return $this->serviceId;
     *     }
     * }
     * ```
     * 
     * MAIS pour l'instant, la classe de base Exception suffit !
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
 * Throwable (interface PHP)
 *   └── Exception (classe PHP)
 *         └── ContainerException (notre classe)
 *               implements ContainerExceptionInterface (notre interface)
 * 
 * POURQUOI extends ET implements ?
 * ---------------------------------
 * 
 * extends Exception :
 * - Pour HÉRITER du code de Exception (getMessage, etc.)
 * - Une classe ne peut hériter que d'UNE classe (single inheritance)
 * 
 * implements ContainerExceptionInterface :
 * - Pour PROMETTRE qu'on respecte le contrat
 * - Une classe peut implémenter PLUSIEURS interfaces (multiple implementation)
 * 
 * COMMENT LANCER CETTE EXCEPTION ?
 * ---------------------------------
 * 
 * throw new ContainerException("Message d'erreur");
 * 
 * // Avec un code d'erreur
 * throw new ContainerException("Message", 500);
 * 
 * // Avec chaînage (exception précédente)
 * try {
 *     // ...
 * } catch (\ReflectionException $e) {
 *     throw new ContainerException("Cannot build service", 0, $e);
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
