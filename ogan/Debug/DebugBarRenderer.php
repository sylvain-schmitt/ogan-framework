<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎨 DEBUG BAR RENDERER - Rendu HTML de la barre de debug
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Génère le HTML/CSS/JS pour afficher la barre de debug en bas de page.
 * Design moderne avec panneaux dépliables.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Debug;

class DebugBarRenderer
{
    /**
     * Génère le HTML complet de la debug bar
     */
    public static function render(array $data): string
    {
        $html = self::getStyles();
        $html .= self::getBar($data);
        $html .= self::getPanels($data);
        $html .= self::getScript();
        
        return $html;
    }
    
    /**
     * Génère la barre principale
     */
    private static function getBar(array $data): string
    {
        $time = $data['time']['execution_ms'] ?? 0;
        $timeClass = $time > 200 ? 'odb-slow' : ($time > 100 ? 'odb-medium' : 'odb-fast');
        
        $memory = $data['memory']['peak'] ?? 'N/A';
        $memoryBytes = $data['memory']['peak_bytes'] ?? 0;
        $memoryClass = $memoryBytes > 50 * 1024 * 1024 ? 'odb-slow' : ($memoryBytes > 20 * 1024 * 1024 ? 'odb-medium' : 'odb-fast');
        
        $queryCount = $data['queries']['count'] ?? 0;
        $queryTime = $data['queries']['total_time_ms'] ?? 0;
        $queryClass = $queryCount > 20 ? 'odb-slow' : ($queryCount > 10 ? 'odb-medium' : 'odb-fast');
        
        $statusCode = $data['response']['status_code'] ?? 200;
        $statusClass = $statusCode >= 400 ? 'odb-slow' : ($statusCode >= 300 ? 'odb-medium' : 'odb-fast');
        
        $method = $data['request']['method'] ?? 'GET';
        $uri = $data['request']['uri'] ?? '/';
        
        // User info
        $user = $data['user'];
        $userIcon = $user ? '👤' : '👻';
        $userName = $user ? ($user['name'] ?? $user['email'] ?? 'User #' . ($user['id'] ?? '?')) : 'Guest';
        
        // Route info
        $route = $data['route'];
        $routeName = $route['name'] ?? 'N/A';
        
        return <<<HTML
<div id="ogan-debug-bar" class="odb-bar">
    <div class="odb-logo" onclick="oganDebugToggle()">🐕 Ogan</div>
    
    <div class="odb-item {$statusClass}" data-panel="request">
        <span class="odb-label">{$method}</span>
        <span class="odb-value">{$statusCode}</span>
    </div>
    
    <div class="odb-item {$timeClass}" data-panel="time">
        <span class="odb-icon">⏱️</span>
        <span class="odb-value">{$time} ms</span>
    </div>
    
    <div class="odb-item {$memoryClass}" data-panel="memory">
        <span class="odb-icon">💾</span>
        <span class="odb-value">{$memory}</span>
    </div>
    
    <div class="odb-item {$queryClass}" data-panel="queries">
        <span class="odb-icon">🗄️</span>
        <span class="odb-value">{$queryCount} queries</span>
        <span class="odb-sub">({$queryTime} ms)</span>
    </div>
    
    <div class="odb-item" data-panel="route">
        <span class="odb-icon">🛣️</span>
        <span class="odb-value">{$routeName}</span>
    </div>
    
    <div class="odb-item" data-panel="user">
        <span class="odb-icon">{$userIcon}</span>
        <span class="odb-value">{$userName}</span>
    </div>
    
    <div class="odb-item" data-panel="session">
        <span class="odb-icon">📝</span>
        <span class="odb-value">Session</span>
    </div>
    
    <div class="odb-item" data-panel="config">
        <span class="odb-icon">⚙️</span>
        <span class="odb-value">PHP {$data['config']['php_version']}</span>
    </div>
    
    <div class="odb-close" onclick="oganDebugClose()">✕</div>
</div>
HTML;
    }
    
    /**
     * Génère les panneaux détaillés
     */
    private static function getPanels(array $data): string
    {
        $html = '<div id="ogan-debug-panels" class="odb-panels">';
        
        // Panel Request
        $html .= self::renderRequestPanel($data);
        
        // Panel Time
        $html .= self::renderTimePanel($data);
        
        // Panel Memory
        $html .= self::renderMemoryPanel($data);
        
        // Panel Queries
        $html .= self::renderQueriesPanel($data);
        
        // Panel Route
        $html .= self::renderRoutePanel($data);
        
        // Panel User
        $html .= self::renderUserPanel($data);
        
        // Panel Session
        $html .= self::renderSessionPanel($data);
        
        // Panel Config
        $html .= self::renderConfigPanel($data);
        
        $html .= '</div>';
        
        return $html;
    }
    
    private static function renderRequestPanel(array $data): string
    {
        $req = $data['request'];
        $method = htmlspecialchars($req['method']);
        $uri = htmlspecialchars($req['uri']);
        $ip = htmlspecialchars($req['ip']);
        $ua = htmlspecialchars(substr($req['user_agent'], 0, 100));
        $status = $data['response']['status_code'];
        
        $get = !empty($req['get']) ? self::renderKeyValue($req['get']) : '<em>Aucune donnée GET</em>';
        $post = !empty($req['post']) ? self::renderKeyValue($req['post']) : '<em>Aucune donnée POST</em>';
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-request">
    <h3>🌍 Request / Response</h3>
    <table class="odb-table">
        <tr><td>Méthode</td><td><strong>{$method}</strong></td></tr>
        <tr><td>URI</td><td><code>{$uri}</code></td></tr>
        <tr><td>Status</td><td><span class="odb-badge">{$status}</span></td></tr>
        <tr><td>IP</td><td>{$ip}</td></tr>
        <tr><td>User-Agent</td><td class="odb-small">{$ua}</td></tr>
    </table>
    <h4>GET</h4>
    {$get}
    <h4>POST</h4>
    {$post}
</div>
HTML;
    }
    
    private static function renderTimePanel(array $data): string
    {
        $time = $data['time']['execution_ms'];
        $formatted = $data['time']['execution_formatted'];
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-time">
    <h3>⏱️ Performance</h3>
    <div class="odb-big-value">{$time} <small>ms</small></div>
    <p>Temps total d'exécution de la requête</p>
</div>
HTML;
    }
    
    private static function renderMemoryPanel(array $data): string
    {
        $current = $data['memory']['current'];
        $peak = $data['memory']['peak'];
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-memory">
    <h3>💾 Mémoire</h3>
    <table class="odb-table">
        <tr><td>Mémoire actuelle</td><td><strong>{$current}</strong></td></tr>
        <tr><td>Pic mémoire</td><td><strong>{$peak}</strong></td></tr>
    </table>
</div>
HTML;
    }
    
    private static function renderQueriesPanel(array $data): string
    {
        $count = $data['queries']['count'];
        $totalTime = $data['queries']['total_time_ms'];
        
        $queriesHtml = '';
        foreach ($data['queries']['list'] as $i => $q) {
            $sql = htmlspecialchars($q['sql']);
            $time = round($q['time'] * 1000, 2);
            $trace = htmlspecialchars($q['backtrace'] ?? '');
            $timeClass = $time > 50 ? 'odb-slow' : ($time > 10 ? 'odb-medium' : 'odb-fast');
            
            $queriesHtml .= <<<HTML
<div class="odb-query">
    <div class="odb-query-header">
        <span class="odb-query-num">#{$i}</span>
        <span class="odb-query-time {$timeClass}">{$time} ms</span>
    </div>
    <code class="odb-query-sql">{$sql}</code>
    <div class="odb-query-trace">{$trace}</div>
</div>
HTML;
        }
        
        if (empty($queriesHtml)) {
            $queriesHtml = '<p><em>Aucune requête SQL exécutée</em></p>';
        }
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-queries">
    <h3>🗄️ Requêtes SQL ({$count})</h3>
    <p>Temps total : <strong>{$totalTime} ms</strong></p>
    <div class="odb-queries-list">{$queriesHtml}</div>
</div>
HTML;
    }
    
    private static function renderRoutePanel(array $data): string
    {
        $route = $data['route'];
        
        if (!$route) {
            return <<<HTML
<div class="odb-panel" id="odb-panel-route">
    <h3>🛣️ Route</h3>
    <p><em>Aucune information de route disponible</em></p>
</div>
HTML;
        }
        
        $name = htmlspecialchars($route['name'] ?? 'N/A');
        $controller = htmlspecialchars($route['controller'] ?? 'N/A');
        $action = htmlspecialchars($route['action'] ?? 'N/A');
        $path = htmlspecialchars($route['path'] ?? 'N/A');
        $params = !empty($route['params']) ? self::renderKeyValue($route['params']) : '<em>Aucun paramètre</em>';
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-route">
    <h3>🛣️ Route</h3>
    <table class="odb-table">
        <tr><td>Nom</td><td><strong>{$name}</strong></td></tr>
        <tr><td>Pattern</td><td><code>{$path}</code></td></tr>
        <tr><td>Controller</td><td>{$controller}</td></tr>
        <tr><td>Action</td><td>{$action}</td></tr>
    </table>
    <h4>Paramètres</h4>
    {$params}
</div>
HTML;
    }
    
    private static function renderUserPanel(array $data): string
    {
        $user = $data['user'];
        
        if (!$user) {
            return <<<HTML
<div class="odb-panel" id="odb-panel-user">
    <h3>👻 Utilisateur</h3>
    <p><strong>Non connecté</strong></p>
</div>
HTML;
        }
        
        $content = self::renderKeyValue($user);
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-user">
    <h3>👤 Utilisateur</h3>
    <p><span class="odb-badge odb-badge-success">Connecté</span></p>
    {$content}
</div>
HTML;
    }
    
    private static function renderSessionPanel(array $data): string
    {
        $session = $data['session'];
        
        if ($session['status'] === 'inactive') {
            return <<<HTML
<div class="odb-panel" id="odb-panel-session">
    <h3>📝 Session</h3>
    <p><em>Session inactive</em></p>
</div>
HTML;
        }
        
        $id = htmlspecialchars($session['id'] ?? '');
        $content = !empty($session['data']) ? self::renderKeyValue($session['data']) : '<em>Session vide</em>';
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-session">
    <h3>📝 Session</h3>
    <p><small>ID: {$id}</small></p>
    {$content}
</div>
HTML;
    }
    
    private static function renderConfigPanel(array $data): string
    {
        $config = $data['config'];
        $includes = $data['includes']['count'];
        
        return <<<HTML
<div class="odb-panel" id="odb-panel-config">
    <h3>⚙️ Configuration</h3>
    <table class="odb-table">
        <tr><td>Framework</td><td><strong>{$config['framework_version']}</strong></td></tr>
        <tr><td>PHP Version</td><td>{$config['php_version']}</td></tr>
        <tr><td>Environnement</td><td><span class="odb-badge">{$config['env']}</span></td></tr>
        <tr><td>Timezone</td><td>{$config['timezone']}</td></tr>
        <tr><td>Fichiers inclus</td><td>{$includes}</td></tr>
    </table>
</div>
HTML;
    }
    
    /**
     * Rend un tableau clé/valeur
     */
    private static function renderKeyValue(array $data): string
    {
        $html = '<table class="odb-table odb-table-small">';
        foreach ($data as $key => $value) {
            $keyHtml = htmlspecialchars((string)$key);
            if (is_array($value)) {
                $valueHtml = '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
            } elseif (is_bool($value)) {
                $valueHtml = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $valueHtml = '<em>null</em>';
            } else {
                $valueHtml = htmlspecialchars((string)$value);
            }
            $html .= "<tr><td>{$keyHtml}</td><td>{$valueHtml}</td></tr>";
        }
        $html .= '</table>';
        return $html;
    }
    
    /**
     * Styles CSS
     */
    private static function getStyles(): string
    {
        return <<<'CSS'
<style>
/* Debug Bar */
.odb-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 36px;
    background: linear-gradient(135deg, #1e1e2e 0%, #313244 100%);
    color: #cdd6f4;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 12px;
    display: flex;
    align-items: center;
    z-index: 99999;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
    border-top: 1px solid #45475a;
}
.odb-logo {
    background: linear-gradient(135deg, #89b4fa 0%, #b4befe 100%);
    color: #1e1e2e;
    font-weight: bold;
    padding: 0 16px;
    height: 100%;
    display: flex;
    align-items: center;
    cursor: pointer;
}
.odb-logo:hover { opacity: 0.9; }
.odb-item {
    padding: 0 12px;
    height: 100%;
    display: flex;
    align-items: center;
    gap: 6px;
    border-right: 1px solid #45475a;
    cursor: pointer;
    transition: background 0.2s;
}
.odb-item:hover { background: rgba(255,255,255,0.05); }
.odb-item.active { background: rgba(137, 180, 250, 0.2); }
.odb-icon { font-size: 14px; }
.odb-label { color: #a6adc8; }
.odb-value { font-weight: 600; }
.odb-sub { color: #6c7086; font-size: 10px; }
.odb-fast .odb-value { color: #a6e3a1; }
.odb-medium .odb-value { color: #f9e2af; }
.odb-slow .odb-value { color: #f38ba8; }
.odb-close {
    margin-left: auto;
    padding: 0 16px;
    cursor: pointer;
    color: #6c7086;
}
.odb-close:hover { color: #f38ba8; }

/* Panels */
.odb-panels { display: none; }
.odb-panels.active { display: block; }
.odb-panel {
    display: none;
    position: fixed;
    bottom: 36px;
    left: 0;
    right: 0;
    max-height: 50vh;
    background: #1e1e2e;
    color: #cdd6f4;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 12px;
    overflow-y: auto;
    z-index: 99998;
    padding: 16px 24px;
    border-top: 1px solid #45475a;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.4);
}
.odb-panel.active { display: block; }
.odb-panel h3 { color: #89b4fa; margin: 0 0 12px 0; font-size: 14px; }
.odb-panel h4 { color: #a6adc8; margin: 16px 0 8px 0; font-size: 12px; }
.odb-table { width: 100%; border-collapse: collapse; }
.odb-table td { padding: 4px 8px; border-bottom: 1px solid #313244; }
.odb-table td:first-child { color: #a6adc8; width: 150px; }
.odb-table-small { font-size: 11px; }
.odb-table pre { margin: 0; font-size: 10px; max-height: 100px; overflow: auto; }
.odb-badge { 
    background: #45475a; 
    padding: 2px 8px; 
    border-radius: 4px; 
    font-size: 11px; 
}
.odb-badge-success { background: #a6e3a1; color: #1e1e2e; }
.odb-big-value { font-size: 48px; font-weight: bold; color: #a6e3a1; }
.odb-big-value small { font-size: 24px; color: #6c7086; }
.odb-small { font-size: 10px; color: #6c7086; }

/* Queries */
.odb-queries-list { max-height: 300px; overflow-y: auto; }
.odb-query { 
    background: #313244; 
    padding: 8px 12px; 
    margin: 8px 0; 
    border-radius: 4px;
    border-left: 3px solid #89b4fa;
}
.odb-query-header { display: flex; justify-content: space-between; margin-bottom: 4px; }
.odb-query-num { color: #6c7086; }
.odb-query-time { font-weight: bold; }
.odb-query-sql { 
    display: block; 
    color: #f9e2af; 
    word-break: break-all; 
    font-size: 11px;
}
.odb-query-trace { color: #6c7086; font-size: 10px; margin-top: 4px; }

/* Hidden state */
.odb-bar.odb-hidden { transform: translateY(100%); }
</style>
CSS;
    }
    
    /**
     * JavaScript
     */
    private static function getScript(): string
    {
        return <<<'JS'
<script>
(function() {
    var currentPanel = null;
    
    // Click on bar items
    document.querySelectorAll('.odb-item[data-panel]').forEach(function(item) {
        item.addEventListener('click', function() {
            var panelId = 'odb-panel-' + this.dataset.panel;
            var panel = document.getElementById(panelId);
            var panels = document.getElementById('ogan-debug-panels');
            
            // Toggle active state on items
            document.querySelectorAll('.odb-item').forEach(function(i) {
                i.classList.remove('active');
            });
            
            // Toggle panel
            if (currentPanel === panelId) {
                panels.classList.remove('active');
                document.querySelectorAll('.odb-panel').forEach(function(p) {
                    p.classList.remove('active');
                });
                currentPanel = null;
            } else {
                panels.classList.add('active');
                document.querySelectorAll('.odb-panel').forEach(function(p) {
                    p.classList.remove('active');
                });
                if (panel) {
                    panel.classList.add('active');
                    this.classList.add('active');
                }
                currentPanel = panelId;
            }
        });
    });
})();

function oganDebugToggle() {
    var bar = document.getElementById('ogan-debug-bar');
    var panels = document.getElementById('ogan-debug-panels');
    bar.classList.toggle('odb-hidden');
    if (bar.classList.contains('odb-hidden')) {
        panels.classList.remove('active');
        document.querySelectorAll('.odb-panel').forEach(function(p) {
            p.classList.remove('active');
        });
    }
}

function oganDebugClose() {
    var bar = document.getElementById('ogan-debug-bar');
    var panels = document.getElementById('ogan-debug-panels');
    bar.style.display = 'none';
    panels.style.display = 'none';
}
</script>
JS;
    }
}
