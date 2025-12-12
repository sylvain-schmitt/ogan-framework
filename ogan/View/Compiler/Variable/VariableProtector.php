<?php

namespace Ogan\View\Compiler\Variable;

use Ogan\View\Compiler\Utility\PlaceholderManager;

/**
 * Protège les variables PHP existantes pour éviter qu'elles soient retransformées
 * 
 * Exemple : $user → ##VAR_PARSE_X## pour éviter que $user soit transformé en $u$s$e$r
 */
class VariableProtector
{
    private PlaceholderManager $placeholderManager;

    public function __construct(PlaceholderManager $placeholderManager)
    {
        $this->placeholderManager = $placeholderManager;
    }

    /**
     * Protège toutes les variables PHP (commençant par $) dans une expression
     * 
     * @param string $expression Expression à protéger
     * @return string Expression avec variables PHP protégées
     */
    public function protect(string $expression): string
    {
        $offset = 0;
        $maxIterations = 50; // Limite de sécurité
        $iteration = 0;

        while (($pos = strpos($expression, '$', $offset)) !== false && $iteration < $maxIterations) {
            // Vérifier que ce n'est pas déjà dans un placeholder
            $checkStart = max(0, $pos - 50);
            $before = substr($expression, 0, $pos);
            if (preg_match('/##(VAR|PROTECTED|STRING)_PARSE_\d+##/', $before)) {
                $offset = $pos + 1;
                continue;
            }

            // Trouver la fin de la variable PHP
            $varStart = $pos;
            $i = $pos + 1;
            $inString = false;
            $stringChar = null;
            $bracketDepth = 0;
            $parenDepth = 0;

            // Capturer le nom de la variable
            while ($i < strlen($expression) && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                $i++;
            }

            // Si on n'a pas de nom de variable valide, continuer
            if ($i === $pos + 1) {
                $offset = $pos + 1;
                continue;
            }

            // Capturer les accès de tableaux et de méthodes
            while ($i < strlen($expression)) {
                $char = $expression[$i];

                // Gérer les chaînes
                if (!$inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($inString && $char === $stringChar) {
                    if ($i > 0 && $expression[$i - 1] !== '\\') {
                        $inString = false;
                        $stringChar = null;
                    }
                }

                if ($inString) {
                    $i++;
                    continue;
                }

                // Gérer les crochets
                if ($char === '[') {
                    $bracketDepth++;
                } elseif ($char === ']') {
                    $bracketDepth--;
                }

                // Gérer les parenthèses
                if ($char === '(') {
                    $parenDepth++;
                } elseif ($char === ')') {
                    $parenDepth--;
                }

                // Si on a un opérateur ->, continuer
                if ($char === '-' && $i + 1 < strlen($expression) && $expression[$i + 1] === '>') {
                    $i += 2;
                    // Capturer le nom de la méthode/propriété
                    while ($i < strlen($expression) && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                        $i++;
                    }
                    // Si c'est une méthode, capturer les parenthèses
                    if ($i < strlen($expression) && $expression[$i] === '(') {
                        $parenDepth++;
                        $i++;
                        continue;
                    }
                    continue;
                }

                // Si on est dans des crochets ou parenthèses, continuer
                if ($bracketDepth > 0 || $parenDepth > 0) {
                    $i++;
                    continue;
                }

                // Si on arrive à un caractère qui n'est pas un opérateur valide, arrêter
                if (!in_array($char, ['[', ']', '(', ')', '-', '>', ' ', "\t", "\n", "\r", ',', ';', ':', '?', '|', '&', '='])) {
                    break;
                }

                $i++;
            }

            $varEnd = $i;
            $var = substr($expression, $varStart, $varEnd - $varStart);

            // Vérifier que c'est une variable valide (pas juste un $)
            if (strlen($var) > 1 && preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*/', $var)) {
                $expression = $this->placeholderManager->protect($expression, $var, 'VAR_PARSE');
                $offset = $varStart + strlen('##VAR_PARSE_' . (count($this->placeholderManager->getPlaceholders()) - 1) . '##');
            } else {
                $offset = $pos + 1;
            }

            $iteration++;
        }

        return $expression;
    }

    /**
     * Restaure les variables protégées
     * 
     * @param string $expression Expression avec placeholders
     * @return string Expression avec variables restaurées
     */
    public function restore(string $expression): string
    {
        return $this->placeholderManager->restore($expression);
    }
}
