<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 LOGGER MIDDLEWARE (Request Logging)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Enregistre chaque requête HTTP dans un fichier de log.
 * Utile pour :
 * - Debugger les problèmes
 * - Analyser le trafic
 * - Audit de sécurité
 * - Mesurer les temps de réponse
 * 
 * INFORMATIONS LOGUÉES :
 * ----------------------
 * - Date et heure
 * - Méthode HTTP (GET, POST, etc.)
 * - URI demandée
 * - Adresse IP du client
 * - Status code de la réponse
 * - Temps d'exécution
 * 
 * EXEMPLE DE LOG :
 * ----------------
 * [2024-12-05 15:30:45] GET /users/123 - IP: 127.0.0.1 - Status: 200 - Time: 0.045s
 * [2024-12-05 15:31:12] POST /api/login - IP: 192.168.1.100 - Status: 401 - Time: 0.023s
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware\Examples;

use Ogan\Middleware\MiddlewareInterface;
use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;

class LoggerMiddleware implements MiddlewareInterface
{
    /**
     * Chemin du fichier de log
     */
    private string $logFile;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string|null $logFile Chemin du fichier de log (optionnel)
     *                             Par défaut : var/log/requests.log
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(?string $logFile = null)
    {
        // Utilise le chemin fourni ou un chemin par défaut
        $this->logFile = $logFile ?? __DIR__ . '/../../../var/log/requests.log';
        
        // Crée le dossier de logs s'il n'existe pas
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * LOGGER LA REQUÊTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * FLUX :
     * ------
     * 1. Enregistre le temps de début
     * 2. Appelle le contrôleur
     * 3. Calcule le temps d'exécution
     * 4. Écrit dans le log
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Enregistrer le temps de début
        // ─────────────────────────────────────────────────────────────
        $startTime = microtime(true);

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Appeler le middleware suivant / contrôleur
        // ─────────────────────────────────────────────────────────────
        $response = $next($request);

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Calculer le temps d'exécution
        // ─────────────────────────────────────────────────────────────
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 3); // en secondes, 3 décimales

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 4 : Écrire dans le fichier de log
        // ─────────────────────────────────────────────────────────────
        $this->log($request, $response, $executionTime);

        return $response;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ÉCRIRE DANS LE FICHIER DE LOG
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Format : [DATE] METHOD URI - IP: xxx.xxx.xxx.xxx - Status: XXX - Time: X.XXXs
     * 
     * @param RequestInterface $request La requête
     * @param ResponseInterface $response La réponse
     * @param float $executionTime Temps d'exécution en secondes
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function log(RequestInterface $request, ResponseInterface $response, float $executionTime): void
    {
        // ────────────────────────────────────────────────────────────
        // Construire le message de log
        // ────────────────────────────────────────────────────────────
        $date = date('Y-m-d H:i:s');
        $method = $request->getMethod();
        $uri = $request->getUri();
        $ip = $this->getClientIp($request);
        $status = $response->getStatusCode();

        $logMessage = sprintf(
            "[%s] %s %s - IP: %s - Status: %d - Time: %.3fs\n",
            $date,
            $method,
            $uri,
            $ip,
            $status,
            $executionTime
        );

        // ────────────────────────────────────────────────────────────
        // Écrire dans le fichier (mode append)
        // ────────────────────────────────────────────────────────────
        // FILE_APPEND : Ajoute à la fin sans écraser
        // LOCK_EX : Verrou exclusif (évite les conflits si plusieurs requêtes simultanées)
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER L'IP DU CLIENT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Essaie différentes sources pour obtenir la vraie IP :
     * 1. X-Forwarded-For (si derrière un proxy/load balancer)
     * 2. X-Real-IP (nginx)
     * 3. REMOTE_ADDR (IP directe)
     * 
     * @param RequestInterface $request
     * @return string L'adresse IP du client
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getClientIp(RequestInterface $request): string
    {
        // Si derrière un proxy (Cloudflare, nginx, load balancer...)
        $forwardedFor = $request->getHeader('X-Forwarded-For');
        if ($forwardedFor) {
            // X-Forwarded-For peut contenir plusieurs IPs : "client, proxy1, proxy2"
            // On prend la première (client réel)
            $ips = explode(',', $forwardedFor);
            return trim($ips[0]);
        }

        // Header nginx
        $realIp = $request->getHeader('X-Real-IP');
        if ($realIp) {
            return $realIp;
        }

        // IP directe depuis $_SERVER
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * UTILISATION :
 * -------------
 * // Logger toutes les requêtes
 * $router->middleware(new LoggerMiddleware());
 * 
 * // Logger avec un fichier personnalisé
 * $router->middleware(new LoggerMiddleware('/var/log/app/custom.log'));
 * 
 * // Logger uniquement les routes API
 * $router->group(['prefix' => '/api', 'middleware' => new LoggerMiddleware()], function($api) {
 *     $api->get('/users', [ApiController::class, 'index']);
 * });
 * 
 * EXEMPLE DE SORTIE (requests.log) :
 * -----------------------------------
 * [2024-12-05 15:30:45] GET /users - IP: 127.0.0.1 - Status: 200 - Time: 0.045s
 * [2024-12-05 15:30:50] POST /users - IP: 127.0.0.1 - Status: 201 - Time: 0.123s
 * [2024-12-05 15:31:00] GET /users/999 - IP: 127.0.0.1 - Status: 404 - Time: 0.012s
 * [2024-12-05 15:31:12] DELETE /users/5 - IP: 192.168.1.100 - Status: 204 - Time: 0.089s
 * 
 * ANALYSE DES LOGS :
 * ------------------
 * # Afficher les 10 dernières requêtes
 * tail -10 var/log/requests.log
 * 
 * # Suivre les logs en temps réel
 * tail -f storage/logs/requests.log
 * 
 * # Filtrer les erreurs (status >= 400)
 * grep -E "Status: [45][0-9]{2}" storage/logs/requests.log
 * 
 * # Compter les requêtes par méthode
 * grep -o "GET\|POST\|PUT\|DELETE" storage/logs/requests.log | sort | uniq -c
 * 
 * # Trouver les requêtes lentes (> 1 seconde)
 * grep -E "Time: [1-9][0-9]*\." storage/logs/requests.log
 * 
 * ÉVOLUTIONS POSSIBLES :
 * ----------------------
 * 1. Logger plus d'informations :
 *    - User-Agent (navigateur)
 *    - Referer (page précédente)
 *    - Données POST (attention aux mots de passe !)
 *    - Taille de la réponse
 * 
 * 2. Logger dans une base de données :
 *    - Permet des requêtes SQL pour analyser
 *    - Graphiques de statistiques
 * 
 * 3. Rotation des logs :
 *    - Créer un nouveau fichier chaque jour
 *    - Compresser les anciens logs
 *    - Supprimer les logs > 30 jours
 * 
 * 4. Intégration avec un service de monitoring :
 *    - Sentry (erreurs)
 *    - Datadog (métriques)
 *    - ELK Stack (Elasticsearch, Logstash, Kibana)
 * 
 * PERFORMANCE :
 * -------------
 * ⚠️  L'écriture dans un fichier peut ralentir l'application si le volume est élevé.
 * 
 * Solutions :
 * - Utiliser un logger asynchrone (queue)
 * - Logger uniquement en environnement de développement
 * - Utiliser syslog au lieu de fichiers
 * 
 * SÉCURITÉ :
 * ----------
 * ⚠️  Les logs peuvent contenir des données sensibles !
 * 
 * Bonnes pratiques :
 * - Ne JAMAIS logger les mots de passe
 * - Anonymiser les données personnelles (RGPD)
 * - Protéger l'accès aux fichiers de log (chmod 600)
 * - Supprimer les logs anciens
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
