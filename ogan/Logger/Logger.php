<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 LOGGER - Implémentation PSR-3 du Logger
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Logger simple qui écrit dans des fichiers.
 * Compatible avec PSR-3.
 * 
 * FONCTIONNALITÉS :
 * -----------------
 * - 8 niveaux de log (emergency, alert, critical, error, warning, notice, info, debug)
 * - Écriture dans des fichiers séparés par niveau
 * - Rotation automatique des logs (optionnel)
 * - Format de log personnalisable
 * - Support du contexte (variables additionnelles)
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * 
 * $logger = new Logger(__DIR__ . '/../var/log');
 * 
 * $logger->info('Utilisateur connecté', ['user_id' => 123]);
 * $logger->error('Erreur de connexion DB', ['error' => $e->getMessage()]);
 * $logger->debug('Requête SQL exécutée', ['query' => $sql]);
 * 
 * FORMAT DES LOGS :
 * -----------------
 * 
 * [2024-01-15 10:30:45] INFO: Utilisateur connecté {"user_id":123}
 * [2024-01-15 10:31:20] ERROR: Erreur de connexion DB {"error":"Connection refused"}
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Logger;

class Logger implements LoggerInterface
{
    /**
     * Niveaux de log (du plus critique au moins critique)
     */
    private const LEVELS = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    /**
     * @var string Répertoire où écrire les logs
     */
    private string $logPath;

    /**
     * @var string Niveau minimum de log (les logs en dessous seront ignorés)
     */
    private string $minLevel;

    /**
     * @var string Channel/catégorie actuel (app, security, queries, etc.)
     */
    private string $channel;

    /**
     * @var bool Utiliser le format JSON au lieu du format texte
     */
    private bool $jsonFormat;

    /**
     * @var int Taille max du fichier en octets (10 Mo par défaut)
     */
    private int $maxFileSize;

    /**
     * @var int Nombre max de fichiers de rotation
     */
    private int $maxFiles;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $logPath Répertoire où écrire les logs
     * @param string $minLevel Niveau minimum (par défaut 'debug' en dev, 'info' en prod)
     * @param string $channel Canal/catégorie (app, security, queries, etc.)
     * @param bool $jsonFormat Utiliser le format JSON
     * @param int $maxFileSize Taille max avant rotation (10 Mo par défaut)
     * @param int $maxFiles Nombre de fichiers de rotation
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(
        string $logPath, 
        string $minLevel = 'debug',
        string $channel = 'app',
        bool $jsonFormat = false,
        int $maxFileSize = 10485760, // 10 Mo
        int $maxFiles = 5
    ) {
        $this->logPath = rtrim($logPath, '/');
        $this->minLevel = $minLevel;
        $this->channel = $channel;
        $this->jsonFormat = $jsonFormat;
        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Crée un logger pour un channel spécifique
     */
    public function channel(string $name): self
    {
        $logger = clone $this;
        $logger->channel = $name;
        return $logger;
    }

    /**
     * Active le format JSON
     */
    public function withJsonFormat(bool $enabled = true): self
    {
        $logger = clone $this;
        $logger->jsonFormat = $enabled;
        return $logger;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * LOGGER UN MESSAGE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Méthode générique pour logger avec un niveau arbitraire.
     * 
     * @param mixed $level Niveau de log
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function log($level, string $message, array $context = []): void
    {
        $level = strtolower((string)$level);

        // Vérifier si le niveau est valide
        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Niveau de log invalide: {$level}");
        }

        // Vérifier si on doit logger ce niveau
        if (self::LEVELS[$level] > self::LEVELS[$this->minLevel]) {
            return; // Niveau trop bas, on ignore
        }

        // Formater le message
        $formatted = $this->jsonFormat 
            ? $this->formatMessageJson($level, $message, $context)
            : $this->formatMessage($level, $message, $context);

        // Écrire dans le fichier
        $this->writeToFile($level, $formatted);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORMATER LE MESSAGE (TEXTE)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function formatMessage(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        // Remplacer les placeholders dans le message
        $message = $this->interpolate($message, $context);

        // Ajouter le contexte en JSON si présent
        $contextJson = '';
        if (!empty($context)) {
            $contextJson = ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return "[{$timestamp}] {$this->channel}.{$levelUpper}: {$message}{$contextJson}" . PHP_EOL;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FORMATER LE MESSAGE (JSON)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function formatMessageJson(string $level, string $message, array $context): string
    {
        $entry = [
            'timestamp' => date('c'), // ISO 8601
            'channel' => $this->channel,
            'level' => strtoupper($level),
            'level_name' => $level,
            'message' => $this->interpolate($message, $context),
            'context' => $context ?: new \stdClass(),
        ];

        // Ajouter des infos de requête si disponibles
        if (isset($_SERVER['REQUEST_URI'])) {
            $entry['extra'] = [
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ];
        }

        return json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * INTERPOLER LES PLACEHOLDERS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (!is_array($value) && !is_object($value)) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ÉCRIRE DANS LE FICHIER
     * ═══════════════════════════════════════════════════════════════════
     */
    private function writeToFile(string $level, string $message): void
    {
        // Fichier du channel
        $logFile = $this->logPath . '/' . $this->channel . '.log';
        
        // Rotation si nécessaire
        $this->rotateIfNeeded($logFile);
        
        // Écrire dans le fichier du channel
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);

        // Fichier d'erreurs (seulement pour les erreurs)
        if (in_array($level, ['error', 'critical', 'alert', 'emergency'])) {
            $errorLogFile = $this->logPath . '/error.log';
            $this->rotateIfNeeded($errorLogFile);
            file_put_contents($errorLogFile, $message, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ROTATION DES FICHIERS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function rotateIfNeeded(string $logFile): void
    {
        if (!file_exists($logFile)) {
            return;
        }

        if (filesize($logFile) < $this->maxFileSize) {
            return;
        }

        // Rotation : décaler les fichiers existants
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $current = $logFile . '.' . $i;
            $next = $logFile . '.' . ($i + 1);
            
            if (file_exists($current)) {
                rename($current, $next);
            }
        }

        // Renommer le fichier actuel
        rename($logFile, $logFile . '.1');
    }

    // ─────────────────────────────────────────────────────────────────
    // MÉTHODES PSR-3 (délèguent à log())
    // ─────────────────────────────────────────────────────────────────

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI PSR-3 ?
 * ----------------
 * 
 * PSR-3 est un standard PHP qui définit une interface commune pour le logging.
 * Avantages :
 * 
 * 1. INTERCHANGEABILITÉ :
 *    On peut remplacer notre Logger par Monolog sans changer le code :
 *    
 *    // Avant
 *    $logger = new Ogan\Logger\Logger('/path/to/logs');
 *    
 *    // Après (avec Monolog)
 *    $logger = new Monolog\Logger('app');
 *    // Le code qui utilise $logger fonctionne toujours !
 * 
 * 2. STANDARDISATION :
 *    Tous les frameworks PHP modernes utilisent PSR-3.
 *    Symfony, Laravel, Zend, etc. utilisent tous la même interface.
 * 
 * 3. TESTABILITÉ :
 *    On peut créer un FakeLogger pour les tests :
 *    
 *    class FakeLogger implements LoggerInterface {
 *        public function log($level, $message, $context = []) {
 *            // Ne fait rien, juste pour les tests
 *        }
 *        // ... autres méthodes
 *    }
 * 
 * NIVEAUX DE LOG
 * --------------
 * 
 * Les niveaux sont ordonnés par criticité :
 * 
 * 0. EMERGENCY : Système inutilisable (ex: base de données inaccessible)
 * 1. ALERT     : Action immédiate requise (ex: site down)
 * 2. CRITICAL  : Erreur critique (ex: exception non gérée)
 * 3. ERROR     : Erreur d'exécution (ex: échec d'une requête)
 * 4. WARNING   : Avertissement (ex: configuration manquante)
 * 5. NOTICE    : Notice normale (ex: événement important)
 * 6. INFO      : Information (ex: utilisateur connecté)
 * 7. DEBUG     : Debug (ex: requête SQL exécutée)
 * 
 * BONNES PRATIQUES
 * ----------------
 * 
 * 1. Utiliser le bon niveau :
 *    - ERROR pour les erreurs réelles
 *    - WARNING pour les problèmes non bloquants
 *    - INFO pour les événements importants
 *    - DEBUG pour les détails de développement
 * 
 * 2. Ajouter du contexte :
 *    $logger->error('Échec de connexion', [
 *        'user_id' => 123,
 *        'ip' => $request->getClientIp(),
 *        'error' => $e->getMessage()
 *    ]);
 * 
 * 3. Ne pas logger de données sensibles :
 *    // ❌ MAUVAIS
 *    $logger->info('Connexion utilisateur', ['password' => $password]);
 *    
 *    // ✅ BON
 *    $logger->info('Connexion utilisateur', ['user_id' => $userId]);
 * 
 * ROTATION DES LOGS
 * -----------------
 * 
 * Pour éviter que les fichiers de log deviennent trop gros, on peut :
 * 
 * 1. Utiliser un outil externe (logrotate sur Linux)
 * 2. Implémenter une rotation dans le Logger :
 *    - Si le fichier dépasse X Mo, le renommer en .log.1
 *    - Créer un nouveau fichier .log
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
