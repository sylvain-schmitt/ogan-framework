<?php

namespace Ogan\View\Compiler\Expression;

use Ogan\View\Compiler\CompilerInterface;

/**
 * Compile les expressions {{ expression }} en PHP
 */
class ExpressionCompiler implements CompilerInterface
{
    private ExpressionParser $parser;

    public function __construct(ExpressionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Compile le contenu en trouvant et compilant toutes les expressions {{ }}
     * 
     * @param string $content Contenu à compiler
     * @return string Contenu compilé
     */
    public function compile(string $content): string
    {
        $braceOpen = chr(123);
        $braceClose = chr(125);
        $doubleOpen = $braceOpen . $braceOpen;
        $doubleClose = $braceClose . $braceClose;

        $offset = 0;
        while (($pos = strpos($content, $doubleOpen, $offset)) !== false) {
            // Ignorer si c'est dans un <pre>
            $beforePos = substr($content, 0, $pos);
            $lastPre = strrpos($beforePos, '<pre');
            $lastPreClose = strrpos($beforePos, '</pre>');

            if ($lastPre !== false && ($lastPreClose === false || $lastPre > $lastPreClose)) {
                $nextClose = strpos($content, $doubleClose, $pos);
                if ($nextClose !== false) {
                    $offset = $nextClose + 2;
                    continue;
                } else {
                    break;
                }
            }

            // Trouver la fermeture correspondante
            $start = $pos + 2; // Après {{
            $end = $start;
            $found = false;
            $braceDepth = 0;
            $parenDepth = 0;
            $bracketDepth = 0;
            $inString = false;
            $stringChar = null;

            for ($i = $start; $i < strlen($content); $i++) {
                $char = $content[$i];
                $nextChar = ($i + 1 < strlen($content)) ? $content[$i + 1] : null;

                // Gérer les chaînes
                if (!$inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    if ($i > 0 && $content[$i - 1] !== '\\') {
                        $inString = false;
                        $stringChar = null;
                    }
                }

                if ($inString) {
                    continue;
                }

                // Compter les parenthèses
                if ($char === '(') {
                    $parenDepth++;
                } elseif ($char === ')') {
                    $parenDepth--;
                }

                // Compter les crochets
                if ($char === '[') {
                    $bracketDepth++;
                } elseif ($char === ']') {
                    $bracketDepth--;
                }

                // Compter les accolades doubles
                if ($nextChar !== null && $char === $braceClose && $nextChar === $braceClose) {
                    if ($braceDepth === 0 && $parenDepth === 0 && $bracketDepth === 0) {
                        $end = $i;
                        $found = true;
                        break;
                    } else {
                        $braceDepth--;
                        $i++; // Skip le deuxième }
                    }
                } elseif ($nextChar !== null && $char === $braceOpen && $nextChar === $braceOpen) {
                    $braceDepth++;
                    $i++; // Skip le deuxième {
                }
            }

            if (!$found) {
                $offset = $pos + 2;
                continue;
            }

            // Extraire l'expression
            $expression = substr($content, $start, $end - $start);
            $expression = trim($expression);

            if ($expression === '') {
                $offset = $end + 2;
                continue;
            }

            // Compiler l'expression
            $compiled = $this->compileSingleExpression($expression);

            // Remplacer dans le contenu
            $content = substr_replace($content, $compiled, $pos, $end + 2 - $pos);

            // Continuer après la position de remplacement
            $offset = $pos + strlen($compiled);
        }

        return $content;
    }

    /**
     * Compile une seule expression
     * 
     * @param string $expression Expression à compiler
     * @return string Expression compilée en PHP
     */
    private function compileSingleExpression(string $expression): string
    {
        $unescaped = false;

        // Détecter {{! variable }} (sans échappement)
        if (preg_match('/^!\s*(.+)$/', $expression, $unescapedMatch)) {
            $expression = trim($unescapedMatch[1]);
            $unescaped = true;
        }
        
        // Détecter le filtre |raw (sans échappement)
        if (preg_match('/\|raw\s*$/', $expression)) {
            $unescaped = true;
        }

        // Détecter les assignations de variables (ex: $var = value ou var = value)
        // Utiliser le flag 's' pour que . corresponde aussi aux retours à la ligne
        if (preg_match('/^(\$?[a-zA-Z_][a-zA-Z0-9_]*)\s*=(.*)$/s', $expression, $assignMatch)) {
            $varName = $assignMatch[1];
            $value = trim($assignMatch[2]);

            // Si la variable n'a pas de $, l'ajouter
            if (!preg_match('/^\$/', $varName)) {
                $varName = '$' . $varName;
            }

            // Transformer la valeur en utilisant l'ExpressionParser
            $value = $this->parser->parse($value);

            // Les assignations ne doivent pas être échappées, elles sont des instructions PHP
            return '<?php ' . $varName . ' = ' . $value . ' ?>';
        }

        // Si c'est une simple variable
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $expression)) {
            $noEscapeMethods = [
                'cssFramework', 'csrf_input', 'section', 'component', 'extend', 'start', 'end',
                'formStart', 'formEnd', 'formRow', 'formLabel', 'formWidget', 'formErrors', 'formRest',
                'htmx_script'
            ];

            if (in_array($expression, $noEscapeMethods)) {
                return '<?= $this->' . $expression . '() ?>';
            }

            if ($unescaped) {
                return '<?= $' . $expression . ' ?>';
            } else {
                return '<?= $this->e($' . $expression . ') ?>';
            }
        }

        // Si c'est déjà du PHP valide
        if (preg_match('/^\$/', $expression)) {
            if ($unescaped) {
                return '<?= ' . $expression . ' ?>';
            } else {
                return '<?= $this->e(' . $expression . ') ?>';
            }
        }

        // Parser l'expression
        $phpExpression = $this->parser->parse($expression);

        // Détecter si c'est un appel de méthode render()
        $isRenderCall = preg_match('/->render\s*\(/', $phpExpression);

        // Détecter les méthodes qui retournent du HTML et ne doivent pas être échappées
        $noEscapeMethods = [
            'cssFramework', 'csrf_input', 'section', 'component', 'extend', 'start', 'end',
            'formStart', 'formEnd', 'formRow', 'formLabel', 'formWidget', 'formErrors', 'formRest',
            'htmx_script'
        ];

        // Fonctions globales qui retournent du HTML (pas des méthodes $this->)
        $noEscapeGlobalFunctions = [
            'htmx_script', 'htmx_delete', 'htmx_form'
        ];

        $isNoEscapeMethod = false;
        foreach ($noEscapeMethods as $method) {
            if (preg_match('/\$this->' . preg_quote($method, '/') . '\s*\(/', $phpExpression)) {
                $isNoEscapeMethod = true;
                break;
            }
        }

        // Vérifier les fonctions globales (sans $this->)
        if (!$isNoEscapeMethod) {
            foreach ($noEscapeGlobalFunctions as $func) {
                // Matcher la fonction globale (pas précédée de ->)
                if (preg_match('/(?<![>\w])' . preg_quote($func, '/') . '\s*\(/', $phpExpression)) {
                    $isNoEscapeMethod = true;
                    break;
                }
            }
        }

        if ($unescaped || $isRenderCall || $isNoEscapeMethod) {
            return '<?= ' . $phpExpression . ' ?>';
        } else {
            return '<?= $this->e(' . $phpExpression . ') ?>';
        }
    }
}

