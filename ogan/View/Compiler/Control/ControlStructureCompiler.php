<?php

namespace Ogan\View\Compiler\Control;

use Ogan\View\Compiler\CompilerInterface;
use Ogan\View\Compiler\Expression\ExpressionParser;

/**
 * Compile les structures de contrôle (if, for, else, elseif)
 * 
 * SYNTAXE SUPPORTÉE ({% %}):
 * - {% for item in items %} → foreach ($items as $item):
 * - {% for key, value in items %} → foreach ($items as $key => $value):
 * - {% endfor %} → endforeach;
 * - {% if condition %} → if ($condition):
 * - {% endif %} → endif;
 * - {% else %} → else:
 * - {% elseif condition %} → elseif ($condition):
 */
class ControlStructureCompiler implements CompilerInterface
{
    private ExpressionParser $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * Compile les structures de contrôle dans le contenu
     */
    public function compile(string $content): string
    {
        $phpOpen = '<' . '?php ';
        $phpClose = ' ?' . '>';

        // {% endfor %} → endforeach
        $content = preg_replace(
            '/\{%\s*endfor\s*%\}/',
            $phpOpen . 'endforeach;' . $phpClose,
            $content
        );

        // {% endif %} → endif
        $content = preg_replace(
            '/\{%\s*endif\s*%\}/',
            $phpOpen . 'endif;' . $phpClose,
            $content
        );

        // {% else %} → else
        $content = preg_replace(
            '/\{%\s*else\s*%\}/',
            $phpOpen . 'else:' . $phpClose,
            $content
        );

        // {% for item in items %} → foreach ($items as $item):
        // {% for key, value in items %} → foreach ($items as $key => $value):
        $content = preg_replace_callback(
            '/\{%\s*for\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*,?\s*([a-zA-Z_][a-zA-Z0-9_]*)?\s+in\s+([a-zA-Z_][a-zA-Z0-9_]*(?:\(\))?)\s*%\}/',
            function ($matches) use ($phpOpen, $phpClose) {
                $first = $matches[1];
                $second = $matches[2] ?? '';
                $collection = $matches[3];

                // Ajouter $ ou $this-> à la collection
                if (strpos($collection, '$') !== 0) {
                    // Si c'est une méthode (contient des parenthèses), on utilise $this->
                    if (strpos($collection, '()') !== false) {
                        $collection = '$this->' . $collection;
                    } else {
                        $collection = '$' . $collection;
                    }
                }

                if (!empty($second)) {
                    return $phpOpen . 'foreach (' . $collection . ' as $' . $first . ' => $' . $second . '):' . $phpClose;
                } else {
                    return $phpOpen . 'foreach (' . $collection . ' as $' . $first . '):' . $phpClose;
                }
            },
            $content
        );

        // {% if condition %} → if ($condition):
        $content = preg_replace_callback(
            '/\{%\s*if\s+(.+?)\s*%\}/',
            function ($matches) use ($phpOpen, $phpClose) {
                $condition = trim($matches[1]);
                $condition = $this->expressionParser->parse($condition);
                return $phpOpen . 'if (' . $condition . '):' . $phpClose;
            },
            $content
        );

        // {% elseif condition %} → elseif ($condition):
        $content = preg_replace_callback(
            '/\{%\s*elseif\s+(.+?)\s*%\}/',
            function ($matches) use ($phpOpen, $phpClose) {
                $condition = trim($matches[1]);
                $condition = $this->expressionParser->parse($condition);
                return $phpOpen . 'elseif (' . $condition . '):' . $phpClose;
            },
            $content
        );

        // {% function(...) %} → <?= $this->function(...) 
        // Pour les helpers de formulaires et autres fonctions
        $content = preg_replace_callback(
            '/\{%\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*?)\)\s*%\}/s',
            function ($matches) {
                $functionName = $matches[1];
                $args = trim($matches[2]);
                
                // Liste des fonctions qui ne doivent pas être échappées (retournent du HTML)
                $noEscapeFunctions = [
                    'formStart', 'formEnd', 'formRow', 'formLabel', 'formWidget', 'formErrors', 'formRest',
                    'component', 'csrf_input', 'section'
                ];
                
                // Parser les arguments
                if (!empty($args)) {
                    $args = $this->expressionParser->parse($args);
                }
                
                if (in_array($functionName, $noEscapeFunctions)) {
                    return '<?= $this->' . $functionName . '(' . $args . ') ?>';
                } else {
                    return '<?= $this->e($this->' . $functionName . '(' . $args . ')) ?>';
                }
            },
            $content
        );

        // {% object.method(...) %} → <?= $object->method(...) 
        // Pour form.render() et autres appels de méthodes
        $content = preg_replace_callback(
            '/\{%\s*([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*?)\)\s*%\}/s',
            function ($matches) {
                $objectName = $matches[1];
                $methodName = $matches[2];
                $args = trim($matches[3]);
                
                // Parser les arguments si présents
                if (!empty($args)) {
                    $args = $this->expressionParser->parse($args);
                }
                
                // Les méthodes render() retournent du HTML, ne pas échapper
                if ($methodName === 'render') {
                    return '<?= $' . $objectName . '->' . $methodName . '(' . $args . ') ?>';
                } else {
                    return '<?= $this->e($' . $objectName . '->' . $methodName . '(' . $args . ')) ?>';
                }
            },
            $content
        );

        return $content;
    }
}
