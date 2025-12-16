<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔍 DUMPER - Affichage élégant des variables
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Classe inspirée de Symfony VarDumper pour afficher les variables
 * de manière lisible et stylisée.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Debug;

class Dumper
{
    private static bool $stylesRendered = false;
    private static int $dumpCount = 0;
    
    /**
     * Génère le HTML pour afficher une variable
     */
    public static function dump(mixed $var, ?string $file = null, ?int $line = null): string
    {
        self::$dumpCount++;
        $id = 'dump-' . self::$dumpCount . '-' . uniqid();
        
        $html = '';
        
        // Ajouter les styles une seule fois
        if (!self::$stylesRendered) {
            $html .= self::getStyles();
            self::$stylesRendered = true;
        }
        
        $html .= '<div class="ogan-dump" id="' . $id . '">';
        
        // Header avec fichier et ligne
        if ($file && $line) {
            $shortFile = basename($file);
            $html .= '<div class="ogan-dump-header">';
            $html .= '<span class="ogan-dump-file">' . htmlspecialchars($shortFile) . '</span>';
            $html .= '<span class="ogan-dump-line">:' . $line . '</span>';
            $html .= '</div>';
        }
        
        // Contenu
        $html .= '<div class="ogan-dump-content">';
        $html .= self::renderValue($var, 10, 0);
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Rend une valeur avec coloration syntaxique
     */
    public static function renderValue(mixed $var, int $maxDepth, int $currentDepth): string
    {
        if ($currentDepth >= $maxDepth) {
            return '<span class="ogan-type-max">... (max depth)</span>';
        }
        
        if ($var === null) {
            return '<span class="ogan-type-null">null</span>';
        }
        
        if (is_bool($var)) {
            $val = $var ? 'true' : 'false';
            return '<span class="ogan-type-bool">' . $val . '</span>';
        }
        
        if (is_int($var)) {
            return '<span class="ogan-type-int">' . $var . '</span>';
        }
        
        if (is_float($var)) {
            return '<span class="ogan-type-float">' . $var . '</span>';
        }
        
        if (is_string($var)) {
            $escaped = htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
            $len = strlen($var);
            return '<span class="ogan-type-string">"' . $escaped . '"</span><span class="ogan-type-length">(' . $len . ')</span>';
        }
        
        if (is_array($var)) {
            return self::renderArray($var, $maxDepth, $currentDepth);
        }
        
        if (is_object($var)) {
            return self::renderObject($var, $maxDepth, $currentDepth);
        }
        
        if (is_resource($var)) {
            return '<span class="ogan-type-resource">resource(' . get_resource_type($var) . ')</span>';
        }
        
        return '<span class="ogan-type-unknown">' . gettype($var) . '</span>';
    }
    
    /**
     * Rend un tableau
     */
    private static function renderArray(array $arr, int $maxDepth, int $currentDepth): string
    {
        $count = count($arr);
        $id = 'arr-' . uniqid();
        
        if ($count === 0) {
            return '<span class="ogan-type-array">array</span><span class="ogan-type-count">(0)</span> []';
        }
        
        $html = '<span class="ogan-type-array">array</span><span class="ogan-type-count">(' . $count . ')</span> ';
        $html .= '<span class="ogan-toggle" onclick="oganToggle(\'' . $id . '\')">[▼]</span>';
        $html .= '<div class="ogan-nested" id="' . $id . '">';
        
        foreach ($arr as $key => $value) {
            $keyHtml = is_int($key) 
                ? '<span class="ogan-key-int">' . $key . '</span>'
                : '<span class="ogan-key-string">"' . htmlspecialchars((string)$key) . '"</span>';
            
            $html .= '<div class="ogan-item">';
            $html .= $keyHtml . ' <span class="ogan-arrow">=></span> ';
            $html .= self::renderValue($value, $maxDepth, $currentDepth + 1);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Rend un objet
     */
    private static function renderObject(object $obj, int $maxDepth, int $currentDepth): string
    {
        $class = get_class($obj);
        $shortClass = basename(str_replace('\\', '/', $class));
        $id = 'obj-' . uniqid();
        
        // Récupérer les propriétés
        $reflection = new \ReflectionObject($obj);
        $properties = $reflection->getProperties();
        $propCount = count($properties);
        
        $html = '<span class="ogan-type-object">' . htmlspecialchars($shortClass) . '</span>';
        $html .= '<span class="ogan-type-count">{' . $propCount . '}</span> ';
        
        if ($propCount === 0) {
            return $html . '{}';
        }
        
        $html .= '<span class="ogan-toggle" onclick="oganToggle(\'' . $id . '\')">[▼]</span>';
        $html .= '<div class="ogan-nested" id="' . $id . '">';
        
        foreach ($properties as $prop) {
            $prop->setAccessible(true);
            $name = $prop->getName();
            $visibility = $prop->isPrivate() ? '-' : ($prop->isProtected() ? '#' : '+');
            
            try {
                $value = $prop->getValue($obj);
            } catch (\Throwable $e) {
                $value = '(uninitialized)';
            }
            
            $html .= '<div class="ogan-item">';
            $html .= '<span class="ogan-visibility">' . $visibility . '</span>';
            $html .= '<span class="ogan-prop-name">' . htmlspecialchars($name) . '</span>';
            $html .= ' <span class="ogan-arrow">:</span> ';
            
            if (is_string($value) && $value === '(uninitialized)') {
                $html .= '<span class="ogan-type-null">' . $value . '</span>';
            } else {
                $html .= self::renderValue($value, $maxDepth, $currentDepth + 1);
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Retourne les styles CSS
     */
    private static function getStyles(): string
    {
        return <<<'CSS'
<style>
.ogan-dump {
    font-family: 'JetBrains Mono', 'Fira Code', Monaco, Consolas, monospace;
    font-size: 12px;
    line-height: 1.6;
    background: linear-gradient(135deg, #1e1e2e 0%, #2d2d3f 100%);
    color: #cdd6f4;
    padding: 16px;
    margin: 10px 0;
    border-radius: 8px;
    border-left: 4px solid #f38ba8;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    overflow-x: auto;
    position: relative;
}
.ogan-dump-header {
    background: rgba(0,0,0,0.2);
    margin: -16px -16px 12px -16px;
    padding: 8px 16px;
    border-radius: 8px 8px 0 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.ogan-dump-file { color: #89b4fa; font-weight: 600; }
.ogan-dump-line { color: #f9e2af; }
.ogan-dump-content { white-space: pre-wrap; word-wrap: break-word; }
.ogan-type-null { color: #6c7086; font-style: italic; }
.ogan-type-bool { color: #fab387; font-weight: bold; }
.ogan-type-int { color: #a6e3a1; }
.ogan-type-float { color: #94e2d5; }
.ogan-type-string { color: #f9e2af; }
.ogan-type-length { color: #6c7086; font-size: 10px; margin-left: 4px; }
.ogan-type-array { color: #89b4fa; font-weight: bold; }
.ogan-type-object { color: #cba6f7; font-weight: bold; }
.ogan-type-count { color: #6c7086; margin-left: 2px; }
.ogan-type-resource { color: #f38ba8; }
.ogan-type-max { color: #f38ba8; font-style: italic; }
.ogan-type-unknown { color: #9399b2; }
.ogan-key-int { color: #a6e3a1; }
.ogan-key-string { color: #89dceb; }
.ogan-arrow { color: #6c7086; }
.ogan-visibility { color: #f38ba8; margin-right: 2px; font-weight: bold; }
.ogan-prop-name { color: #89dceb; }
.ogan-toggle { 
    color: #89b4fa; 
    cursor: pointer; 
    user-select: none;
    transition: transform 0.2s;
    display: inline-block;
}
.ogan-toggle:hover { color: #b4befe; }
.ogan-nested { 
    margin-left: 20px; 
    padding-left: 12px;
    border-left: 1px dashed rgba(255,255,255,0.1);
}
.ogan-item { margin: 2px 0; }
.ogan-collapsed { display: none; }
</style>
<script>
function oganToggle(id) {
    var el = document.getElementById(id);
    if (el) {
        el.classList.toggle('ogan-collapsed');
    }
}
</script>
CSS;
    }
    
    /**
     * Reset les styles (utile pour les tests)
     */
    public static function reset(): void
    {
        self::$stylesRendered = false;
        self::$dumpCount = 0;
    }
}
