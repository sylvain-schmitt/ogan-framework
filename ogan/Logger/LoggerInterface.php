<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 LOGGERINTERFACE - Interface PSR-3 pour le Logging
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Interface standardisée pour le logging (PSR-3).
 * Permet d'utiliser n'importe quel logger compatible PSR-3.
 * 
 * POURQUOI PSR-3 ?
 * ----------------
 * 
 * PSR-3 est un standard PHP qui définit une interface commune pour le logging.
 * Avantages :
 * - Interchangeabilité : on peut changer de logger sans modifier le code
 * - Compatibilité : fonctionne avec Monolog, Psr\Log, etc.
 * - Standardisation : même API partout
 * 
 * NIVEAUX DE LOG :
 * ----------------
 * 
 * 1. EMERGENCY : Système inutilisable
 * 2. ALERT     : Action immédiate requise
 * 3. CRITICAL  : Erreur critique
 * 4. ERROR     : Erreur d'exécution
 * 5. WARNING   : Avertissement
 * 6. NOTICE    : Notice normale
 * 7. INFO      : Information
 * 8. DEBUG     : Debug (détails pour développement)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Logger;

/**
 * Interface PSR-3 pour le logging
 * 
 * Compatible avec Psr\Log\LoggerInterface
 */
interface LoggerInterface
{
    /**
     * Système inutilisable
     * 
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Action immédiate requise
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Erreur critique
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Erreur d'exécution
     */
    public function error(string $message, array $context = []): void;

    /**
     * Avertissement
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Notice normale
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Information
     */
    public function info(string $message, array $context = []): void;

    /**
     * Debug (détails pour développement)
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Logger avec un niveau arbitraire
     * 
     * @param mixed $level Niveau de log
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     */
    public function log($level, string $message, array $context = []): void;
}
