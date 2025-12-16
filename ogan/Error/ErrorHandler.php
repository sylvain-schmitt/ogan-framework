<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🚨 ERRORHANDLER - Gestionnaire Global d'Erreurs
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE
 * ----
 * Capture TOUTES les erreurs et exceptions de l'application et les affiche
 * de manière propre et utile.
 * 
 * TYPES D'ERREURS GÉRÉES
 * -----------------------
 * 1. Exceptions non catchées (throw new Exception())
 * 2. Erreurs fatales PHP (parse error, call to undefined function...)
 * 3. Warnings et notices PHP (si configuré)
 * 
 * MODES D'AFFICHAGE
 * -----------------
 * - **DEV** : Affichage complet (stack trace, fichier, ligne, code source, variables...)
 * - **PROD** : Page d'erreur générique sans détails techniques (sécurité)
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Error;

use Throwable;
use Ogan\Exception\RouteNotFoundException;

class ErrorHandler
{
    private bool $debug;

    /**
     * @param bool $debug Mode debug (true = dev, false = prod)
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Enregistre le handler comme gestionnaire global
     */
    public function register(): void
    {
        // Capture les exceptions non catchées
        set_exception_handler([$this, 'handleException']);

        // Convertit les erreurs PHP (warnings, notices...) en exceptions
        set_error_handler([$this, 'handleError']);

        // Capture les erreurs fatales (parse error, etc.)
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Gère les exceptions non catchées
     */
    public function handleException(Throwable $exception): void
    {
        // Détermine le code HTTP selon le type d'exception
        $statusCode = $this->getStatusCode($exception);
        http_response_code($statusCode);

        if ($this->debug) {
            $this->renderDebugPage($exception);
        } else {
            $this->renderProductionPage($exception, $statusCode);
        }

        exit(1);
    }

    /**
     * Convertit les erreurs PHP en exceptions
     */
    public function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        // Ne pas convertir les erreurs supprimées avec @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Gère les erreurs fatales
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        // Vérifier si c'est une erreur fatale
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleException(
                new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                )
            );
        }
    }

    /**
     * Détermine le code HTTP selon le type d'exception
     */
    private function getStatusCode(Throwable $exception): int
    {
        // Si l'exception a déjà un code défini
        if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
            return $exception->getCode();
        }

        // Selon le type d'exception
        if ($exception instanceof RouteNotFoundException) {
            return 404;
        }

        // Par défaut : 500 Internal Server Error
        return 500;
    }

    /**
     * Extrait le code source autour de la ligne d'erreur
     */
    private function getCodeExcerpt(string $file, int $errorLine, int $context = 8): string
    {
        if (!file_exists($file) || !is_readable($file)) {
            return '<em>Impossible de lire le fichier</em>';
        }

        $lines = file($file);
        if ($lines === false) {
            return '<em>Impossible de lire le fichier</em>';
        }

        $start = max(0, $errorLine - $context - 1);
        $end = min(count($lines), $errorLine + $context);
        
        $html = '<div class="code-excerpt">';
        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $lineContent = htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8');
            $lineContent = rtrim($lineContent);
            
            $isError = ($lineNum === $errorLine);
            $class = $isError ? 'error-line' : '';
            $marker = $isError ? '→' : '  ';
            
            $html .= sprintf(
                '<div class="code-line %s"><span class="line-marker">%s</span><span class="line-num">%d</span><span class="line-code">%s</span></div>',
                $class,
                $marker,
                $lineNum,
                $lineContent ?: ' '
            );
        }
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Génère le stack trace amélioré avec code source
     */
    private function getEnhancedStackTrace(Throwable $exception): string
    {
        $trace = $exception->getTrace();
        $html = '';
        
        foreach ($trace as $i => $frame) {
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? 0;
            $function = $frame['function'] ?? '';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            
            $shortFile = basename($file);
            $call = $class ? "{$class}{$type}{$function}()" : "{$function}()";
            
            $html .= '<div class="trace-frame">';
            $html .= '<div class="trace-header" onclick="this.nextElementSibling.classList.toggle(\'hidden\')">';
            $html .= '<span class="trace-num">#' . $i . '</span>';
            $html .= '<span class="trace-call">' . htmlspecialchars($call) . '</span>';
            $html .= '<span class="trace-location">' . htmlspecialchars($shortFile) . ':' . $line . '</span>';
            $html .= '</div>';
            
            if ($file !== 'unknown' && file_exists($file)) {
                $html .= '<div class="trace-code hidden">';
                $html .= $this->getCodeExcerpt($file, $line, 3);
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }

    /**
     * Génère l'affichage des variables de contexte
     */
    private function getContextVariables(): string
    {
        $html = '<div class="context-tabs">';
        
        // GET
        $html .= '<details class="context-section">';
        $html .= '<summary>$_GET (' . count($_GET) . ')</summary>';
        $html .= $this->renderVariableTable($_GET);
        $html .= '</details>';
        
        // POST
        $html .= '<details class="context-section">';
        $html .= '<summary>$_POST (' . count($_POST) . ')</summary>';
        $html .= $this->renderVariableTable($_POST);
        $html .= '</details>';
        
        // SESSION
        if (session_status() === PHP_SESSION_ACTIVE) {
            $html .= '<details class="context-section">';
            $html .= '<summary>$_SESSION (' . count($_SESSION) . ')</summary>';
            $html .= $this->renderVariableTable($_SESSION);
            $html .= '</details>';
        }
        
        // COOKIES
        $html .= '<details class="context-section">';
        $html .= '<summary>$_COOKIE (' . count($_COOKIE) . ')</summary>';
        $html .= $this->renderVariableTable($_COOKIE);
        $html .= '</details>';
        
        // SERVER (filtered)
        $serverFiltered = array_filter($_SERVER, function($key) {
            return in_array($key, [
                'REQUEST_METHOD', 'REQUEST_URI', 'HTTP_HOST', 'HTTP_USER_AGENT',
                'REMOTE_ADDR', 'SERVER_NAME', 'CONTENT_TYPE', 'HTTP_ACCEPT'
            ]);
        }, ARRAY_FILTER_USE_KEY);
        $html .= '<details class="context-section">';
        $html .= '<summary>$_SERVER (filtered)</summary>';
        $html .= $this->renderVariableTable($serverFiltered);
        $html .= '</details>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Rend un tableau de variables
     */
    private function renderVariableTable(array $vars): string
    {
        if (empty($vars)) {
            return '<em class="empty">Aucune donnée</em>';
        }
        
        $html = '<table class="var-table">';
        foreach ($vars as $key => $value) {
            $keyHtml = htmlspecialchars((string)$key);
            if (is_array($value) || is_object($value)) {
                $valueHtml = '<pre>' . htmlspecialchars(print_r($value, true)) . '</pre>';
            } elseif (is_bool($value)) {
                $valueHtml = $value ? '<span class="bool">true</span>' : '<span class="bool">false</span>';
            } elseif (is_null($value)) {
                $valueHtml = '<span class="null">null</span>';
            } else {
                $valueHtml = htmlspecialchars((string)$value);
            }
            $html .= "<tr><td class=\"var-key\">{$keyHtml}</td><td class=\"var-value\">{$valueHtml}</td></tr>";
        }
        $html .= '</table>';
        
        return $html;
    }

    /**
     * Affiche une page d'erreur détaillée (mode dev)
     */
    private function renderDebugPage(Throwable $exception): void
    {
        $class = get_class($exception);
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = $exception->getFile();
        $line = $exception->getLine();
        $shortClass = substr(strrchr($class, '\\'), 1) ?: $class;
        $codeExcerpt = $this->getCodeExcerpt($file, $line);
        $enhancedTrace = $this->getEnhancedStackTrace($exception);
        $contextVars = $this->getContextVariables();
        $fileHtml = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        $classHtml = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - {$shortClass}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'JetBrains Mono', 'Fira Code', Monaco, monospace; 
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d3f 100%);
            color: #cdd6f4;
            min-height: 100vh;
            line-height: 1.5;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #f38ba8 0%, #fab387 100%);
            color: #1e1e2e;
            padding: 24px 32px;
            border-radius: 12px 12px 0 0;
            margin-bottom: 0;
        }
        .header h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .header .exception-class { 
            background: rgba(0,0,0,0.2); 
            padding: 4px 12px; 
            border-radius: 6px; 
            font-size: 14px;
        }
        
        /* Message */
        .message-box {
            background: #313244;
            border-left: 4px solid #f9e2af;
            padding: 20px 24px;
            font-size: 16px;
            word-break: break-word;
        }
        
        /* Content */
        .content { background: #1e1e2e; padding: 24px; border-radius: 0 0 12px 12px; }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .info-item { background: #313244; padding: 12px 16px; border-radius: 8px; }
        .info-label { color: #a6adc8; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }
        .info-value { color: #89b4fa; font-size: 13px; word-break: break-all; }
        
        /* Code Excerpt */
        .section { margin-bottom: 24px; }
        .section-title { 
            color: #89b4fa; 
            font-size: 14px; 
            font-weight: 600; 
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .code-excerpt { 
            background: #11111b; 
            border-radius: 8px; 
            overflow: hidden;
            font-size: 12px;
        }
        .code-line { 
            display: flex; 
            padding: 2px 0;
            border-left: 3px solid transparent;
        }
        .code-line.error-line { 
            background: rgba(243, 139, 168, 0.15); 
            border-left-color: #f38ba8;
        }
        .line-marker { width: 24px; text-align: center; color: #f38ba8; }
        .line-num { 
            width: 48px; 
            text-align: right; 
            padding-right: 16px; 
            color: #6c7086;
            user-select: none;
        }
        .line-code { flex: 1; white-space: pre; overflow-x: auto; }
        
        /* Stack Trace */
        .trace-frame { 
            background: #313244; 
            margin-bottom: 4px; 
            border-radius: 6px; 
            overflow: hidden;
        }
        .trace-header { 
            display: flex; 
            gap: 12px; 
            padding: 10px 14px; 
            cursor: pointer;
            transition: background 0.2s;
        }
        .trace-header:hover { background: #45475a; }
        .trace-num { color: #f38ba8; font-weight: bold; min-width: 30px; }
        .trace-call { color: #a6e3a1; flex: 1; }
        .trace-location { color: #89b4fa; font-size: 12px; }
        .trace-code { padding: 0 14px 14px; }
        .trace-code .code-excerpt { font-size: 11px; }
        .hidden { display: none; }
        
        /* Context Variables */
        .context-section { margin-bottom: 8px; }
        .context-section summary {
            background: #313244;
            padding: 10px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        .context-section summary:hover { background: #45475a; }
        .context-section[open] summary { border-radius: 6px 6px 0 0; }
        .var-table { 
            width: 100%; 
            background: #11111b; 
            border-radius: 0 0 6px 6px;
            font-size: 12px;
        }
        .var-table td { padding: 8px 12px; border-bottom: 1px solid #313244; }
        .var-key { color: #89dceb; width: 200px; }
        .var-value { color: #f9e2af; word-break: break-all; }
        .var-value pre { margin: 0; font-size: 11px; max-height: 150px; overflow: auto; }
        .bool { color: #fab387; }
        .null { color: #6c7086; font-style: italic; }
        .empty { color: #6c7086; font-style: italic; display: block; padding: 12px; }
        
        /* Footer */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            color: #6c7086;
            font-size: 12px;
        }
        .copy-btn {
            background: #45475a;
            color: #cdd6f4;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
        }
        .copy-btn:hover { background: #585b70; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Une erreur s'est produite</h1>
            <span class="exception-class">{$shortClass}</span>
        </div>
        
        <div class="message-box">{$message}</div>
        
        <div class="content">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Fichier</div>
                    <div class="info-value">{$fileHtml}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ligne</div>
                    <div class="info-value">{$line}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Classe</div>
                    <div class="info-value">{$classHtml}</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">📄 Code Source</div>
                {$codeExcerpt}
            </div>
            
            <div class="section">
                <div class="section-title">📚 Stack Trace (cliquez pour voir le code)</div>
                {$enhancedTrace}
            </div>
            
            <div class="section">
                <div class="section-title">🔍 Variables de Contexte</div>
                {$contextVars}
            </div>
        </div>
        
        <div class="footer">
            <span>Framework Ogan 🐕 | Mode DEBUG</span>
            <button class="copy-btn" onclick="copyError()">📋 Copier l'erreur</button>
        </div>
    </div>
    
    <script>
    function copyError() {
        var text = "{$shortClass}: {$message}\\n";
        text += "File: {$fileHtml}:{$line}";
        navigator.clipboard.writeText(text).then(function() {
            alert('Erreur copiée !');
        });
    }
    </script>
</body>
</html>
HTML;
    }

    /**
     * Affiche une page d'erreur générique (mode production)
     */
    private function renderProductionPage(Throwable $exception, int $statusCode): void
    {
        // Essayer d'utiliser les templates Ogan si disponibles
        $templateName = match ($statusCode) {
            403 => 'errors/403.ogan',
            404 => 'errors/404.ogan',
            default => 'errors/500.ogan',
        };

        try {
            $templatesPath = \Ogan\Config\Config::get('view.templates_path', 'templates');
            $templateFile = rtrim($templatesPath, '/') . '/' . $templateName;
            
            if (file_exists($templateFile)) {
                $view = new \Ogan\View\View($templatesPath, true);
                $message = $statusCode === 403 
                    ? 'Accès refusé.'
                    : ($statusCode === 404 
                        ? 'La page que vous recherchez n\'existe pas.' 
                        : 'Une erreur s\'est produite.');
                        
                echo $view->render($templateName, ['message' => $message]);
                return;
            }
        } catch (Throwable $e) {
            // Fallback vers HTML inline si le template échoue
        }

        // Fallback HTML inline (quand les templates ne sont pas disponibles)
        $title = $statusCode === 404 ? 'Page non trouvée' : 'Erreur serveur';
        $message = $statusCode === 404
            ? 'La page que vous recherchez n\'existe pas.'
            : 'Une erreur s\'est produite. Veuillez réessayer plus tard.';
        $icon = $statusCode === 404 ? 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';

        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 min-h-screen flex items-center justify-center px-4">
    <div class="text-center text-white max-w-2xl">
        <div class="mb-8">
            <div class="text-9xl font-bold opacity-20 mb-4">{$statusCode}</div>
            <div class="flex justify-center mb-6">
                <svg class="w-24 h-24 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{$icon}"></path>
                </svg>
            </div>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{$title}</h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8">{$message}</p>
        <a href="/" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold px-6 py-3 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour à l'accueil
        </a>
        <div class="mt-12 text-white/60 text-sm">
            Framework Ogan 🐕
        </div>
    </div>
</body>
</html>
HTML;
    }
}
