<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️ CONTAINEREXCEPTIONINTERFACE - Exception Base du Container (PSR-11)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * QU'EST-CE QU'UNE EXCEPTION ?
 * ----------------------------
 * Une exception est un signal d'ERREUR. Quand quelque chose se passe mal dans
 * ton code, au lieu de continuer et faire n'importe quoi, tu "lances" une
 * exception pour dire : "STOP ! Il y a un problème !"
 * 
 * ANALOGIE :
 * ---------
 * Imagine que tu conduis une voiture :
 * - Voyant moteur s'allume = EXCEPTION lancée
 * - Tu t'arrêtes pour vérifier = EXCEPTION catchée (gérée)
 * - Tu ignores le voyant et continues = EXCEPTION non catchée (crash !)
 * 
 * POURQUOI UNE INTERFACE D'EXCEPTION ?
 * -------------------------------------
 * PSR-11 dit : "Le container doit lancer des exceptions spécifiques"
 * 
 * Ça permet de CATCHER (attraper) les erreurs du container de manière précise :
 * 
 * ```php
 * try {
 *     $service = $container->get('inexistant');
 * } catch (NotFoundExceptionInterface $e) {
 *     // Ah, le service n'existe pas !
 * } catch (ContainerExceptionInterface $e) {
 *     // Problème plus général du container
 * }
 * ```
 * 
 * HIÉRARCHIE DES EXCEPTIONS PSR-11
 * ---------------------------------
 * 
 *                    Throwable (PHP natif)
 *                         |
 *                     Exception (PHP natif)
 *                         |
 *           ContainerExceptionInterface ← Notre interface
 *                    /          \
 *                   /            \
 *     NotFoundExceptionInterface  Autres exceptions possibles
 *         (service introuvable)   (erreur de build, config...)
 * 
 * QUAND LANCER CETTE EXCEPTION ?
 * -------------------------------
 * - Erreur lors de la création d'un service
 * - Dépendance circulaire détectée
 * - Configuration invalide
 * - Problème de Reflection
 * - Toute erreur GÉNÉRALE du container
 * 
 * NOTE IMPORTANTE :
 * -----------------
 * Cette interface HÉRITE de Throwable (indirectement via Exception).
 * Ça veut dire que toutes les exceptions du container peuvent être
 * catchées avec un simple catch(Throwable) si besoin.
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

use Throwable;

/**
 * Exception de base pour toutes les erreurs du Container
 * 
 * Conforme au standard PSR-11
 * 
 * @see https://www.php-fig.org/psr/psr-11/
 */
interface ContainerExceptionInterface extends Throwable
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * INTERFACE VIDE (Marker Interface)
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Cette interface est VIDE volontairement !
     * 
     * POURQUOI ?
     * ----------
     * C'est ce qu'on appelle une "Marker Interface" (interface marqueur).
     * 
     * Son rôle n'est PAS d'ajouter des méthodes, mais de MARQUER
     * une exception comme "exception du container".
     * 
     * AVANTAGES :
     * -----------
     * 1. CATCH PRÉCIS : On peut attraper uniquement les erreurs du container
     * 2. TYPE SAFETY : PHP vérifie que c'est bien une exception du container
     * 3. STANDARD : Tous les containers PSR-11 utilisent cette interface
     * 
     * EXEMPLE D'UTILISATION :
     * -----------------------
     * class MyContainerException extends Exception implements ContainerExceptionInterface {}
     * 
     * try {
     *     $container->get('service');
     * } catch (ContainerExceptionInterface $e) {
     *     echo "Erreur du container : " . $e->getMessage();
     * }
     * 
     * ───────────────────────────────────────────────────────────────────────
     */
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI EXTENDS THROWABLE ?
 * -----------------------------
 * 
 * En PHP, il y a une hiérarchie stricte pour les exceptions :
 * 
 * Throwable (interface de base)
 *   ├── Error (erreurs fatales PHP)
 *   └── Exception (exceptions applicatives)
 * 
 * Pour qu'une interface puisse être utilisée avec try/catch, elle DOIT
 * hériter de Throwable.
 * 
 * extends vs implements ICI
 * -------------------------
 * On dit "extends" et pas "implements" car :
 * - Throwable est une INTERFACE
 * - Une interface peut hériter (extends) d'une autre interface
 * - Une classe implémente (implements) une interface
 * 
 * MARKER INTERFACES : C'EST QUOI ?
 * ---------------------------------
 * 
 * Interfaces vides qui servent juste à "marquer" une classe.
 * 
 * Exemples célèbres en PHP :
 * - Serializable : marque qu'une classe peut être sérialisée
 * - JsonSerializable : marque qu'une classe peut être JSON-encodée
 * - Throwable : marque qu'une classe peut être lancée (throw)
 * 
 * PROCHAINES ÉTAPES
 * -----------------
 * 1. Créer NotFoundExceptionInterface (hérite de celle-ci)
 * 2. Créer les classes concrètes (ContainerException, NotFoundException)
 * 3. Utiliser ces exceptions dans Container.php
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
