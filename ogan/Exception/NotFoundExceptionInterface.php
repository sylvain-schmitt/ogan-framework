<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🔍 NOTFOUNDEXCEPTIONINTERFACE - Service Introuvable (PSR-11)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE SPÉCIFIQUE DE CETTE EXCEPTION
 * -----------------------------------
 * Cette exception est lancée quand on demande un service qui N'EXISTE PAS
 * et que le container ne peut PAS le créer automatiquement.
 * 
 * DIFFÉRENCE AVEC ContainerExceptionInterface
 * --------------------------------------------
 * 
 * ContainerExceptionInterface :
 * - Erreur GÉNÉRALE du container
 * - Exemple : Impossible de construire le service (dépendance manquante)
 * - Exemple : Erreur de configuration
 * 
 * NotFoundExceptionInterface :
 * - Erreur SPÉCIFIQUE : le service n'existe tout simplement pas
 * - Exemple : $container->get('service_qui_existe_pas')
 * - Exemple : Tentative de get() d'une classe qui n'existe pas
 * 
 * ANALOGIE
 * --------
 * Imagine un restaurant :
 * 
 * NotFoundExceptionInterface = "Ce plat n'est pas au menu"
 *   → Le restaurant ne propose pas ce plat du tout
 * 
 * ContainerExceptionInterface = "On ne peut pas préparer votre plat"
 *   → Le plat existe au menu mais il y a un problème en cuisine
 *   
 * POURQUOI SÉPARER CES 2 EXCEPTIONS ?
 * ------------------------------------
 * 1. GESTION DIFFÉRENTE :
 *    - NotFound → on peut proposer une alternative ou retourner NULL
 *    - ContainerError → c'est plus grave, il faut corriger le code
 * 
 * 2. CODE PLUS CLAIR :
 *    ```php
 *    try {
 *        $service = $container->get($id);
 *    } catch (NotFoundExceptionInterface $e) {
 *        // On sait exactement : le service n'existe pas
 *        $service = new DefaultService(); // Fallback
 *    } catch (ContainerExceptionInterface $e) {
 *        // Erreur plus grave, on log et on stop
 *        log_error($e);
 *        throw $e;
 *    }
 *    ```
 * 
 * 3. STANDARD PSR-11 :
 *    Tous les containers pros (Symfony, Laravel...) respectent cette séparation
 * 
 * HÉRITAGE
 * --------
 * Cette interface HÉRITE de ContainerExceptionInterface.
 * 
 * Ça veut dire :
 * - NotFoundExceptionInterface EST UNE ContainerExceptionInterface
 * - On peut catch NotFoundExceptionInterface spécifiquement
 * - OU catch ContainerExceptionInterface pour attraper toutes les erreurs
 * 
 * Hiérarchie :
 * 
 *     ContainerExceptionInterface (erreurs générales)
 *            ↑
 *            |
 *     NotFoundExceptionInterface (sous-cas spécifique)
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

/**
 * Exception lancée quand un service n'est pas trouvé dans le container
 * 
 * Conforme au standard PSR-11
 * 
 * Cette exception est lancée par Container::get() quand :
 * - L'ID demandé n'existe pas dans le container
 * - La classe n'existe pas
 * - Aucune factory n'est définie pour cet ID
 * 
 * @see https://www.php-fig.org/psr/psr-11/
 */
interface NotFoundExceptionInterface extends ContainerExceptionInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * INTERFACE VIDE (Marker Interface)
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Comme ContainerExceptionInterface, cette interface est vide.
     * 
     * Son rôle est de MARQUER plus précisément le type d'erreur :
     * "Ce n'est pas juste une erreur de container, c'est spécifiquement
     *  une erreur 'service non trouvé'."
     * 
     * QUAND LANCER CETTE EXCEPTION ?
     * -------------------------------
     * Dans Container::get($id) :
     * 
     * 1. L'ID n'est pas enregistré dans $services
     * 2. L'ID n'est pas une classe existante
     * 3. Aucun moyen de créer ce service
     * 
     * QUAND NE PAS LANCER CETTE EXCEPTION ?
     * --------------------------------------
     * Si le service EXISTE mais qu'il y a une erreur de création :
     * → Lancer ContainerExceptionInterface à la place
     * 
     * Exemple :
     * - Service existe mais dépendance manquante → ContainerException
     * - Service n'existe tout simplement pas → NotFoundException
     * 
     * EXEMPLE DE CODE :
     * -----------------
     * ```php
     * public function get(string $id) {
     *     if (!$this->has($id)) {
     *         throw new NotFoundException("Service '$id' not found");
     *     }
     *     
     *     try {
     *         return $this->build($id);
     *     } catch (\Exception $e) {
     *         throw new ContainerException("Cannot build '$id'", 0, $e);
     *     }
     * }
     * ```
     * 
     * ───────────────────────────────────────────────────────────────────────
     */
}
