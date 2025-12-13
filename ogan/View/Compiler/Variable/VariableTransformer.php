<?php

namespace Ogan\View\Compiler\Variable;

use Ogan\View\Compiler\Utility\PhpKeywordChecker;
use Ogan\View\Compiler\Utility\PlaceholderManager;
use Ogan\View\Compiler\Utility\StringProtector;

/**
 * Transforme les variables sans $ en variables PHP avec $
 * 
 * Exemples :
 * - user → $user
 * - user->getId() → $user->getId()
 * - user.getCreatedAt ? user.getCreatedAt.format(...) : 'N/A' → $user->getCreatedAt() ? $user->getCreatedAt()->format(...) : 'N/A'
 */
class VariableTransformer
{
    private PhpKeywordChecker $keywordChecker;
    private VariableProtector $protector;
    private PlaceholderManager $placeholderManager;
    private StringProtector $stringProtector;

    public function __construct(
        PhpKeywordChecker $keywordChecker,
        VariableProtector $protector,
        PlaceholderManager $placeholderManager,
        StringProtector $stringProtector
    ) {
        $this->keywordChecker = $keywordChecker;
        $this->protector = $protector;
        $this->placeholderManager = $placeholderManager;
        $this->stringProtector = $stringProtector;
    }

    /**
     * Transforme les variables dans une expression
     * 
     * @param string $expression Expression à transformer
     * @return string Expression transformée
     */
    public function transform(string $expression): string
    {
        // ÉTAPE 0 : Protéger les chaînes AVANT toute transformation
        // IMPORTANT : Les chaînes doivent être protégées pour éviter que leur contenu soit transformé
        $this->placeholderManager->reset();
        $expression = $this->stringProtector->protectStrings($expression);

        // ÉTAPE 1 : Protéger les variables PHP existantes AVANT toute transformation
        $expression = $this->protector->protect($expression);

        // Variable pour les closures
        $keywordChecker = $this->keywordChecker;

        // ÉTAPE 2 : Transformer les variables suivies d'opérateurs (->, [, etc.)
        // Utiliser \b (word boundary) pour éviter de matcher dans $user
        $maxIterations = 20;
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $newExpression = preg_replace_callback(
                '/(?<!\$)(?<!##)\b([a-zA-Z_][a-zA-Z0-9_]*)((?:\s*(?:->|\[))[^\s,)\]]*?)(?=\s|$|\)|,|;|:|\]|=>)/',
                function ($matches) use ($keywordChecker) {
                    $var = $matches[1];
                    $rest = $matches[2];

                    // Ne pas transformer si c'est un mot-clé
                    if ($keywordChecker->isKeyword($var)) {
                        return $matches[0];
                    }

                    return '$' . $var . $rest;
                },
                $expression,
                -1,
                $count
            );

            if ($count === 0) {
                break;
            }

            $expression = $newExpression;

            // Protéger les variables PHP après chaque transformation
            $expression = $this->protector->protect($expression);

            $iteration++;
        }

        // ÉTAPE 3 : Transformer les variables dans les expressions ternaires (après ? ou :)
        // IMPORTANT : Ne transformer que les variables qui sont des objets, pas les méthodes
        // Pattern: " ? user->" ou " : user->" où user est un objet
        $iteration = 0;
        while ($iteration < $maxIterations) {
            $newExpression = preg_replace_callback(
                '/(\s*[?:]\s+)([a-zA-Z_][a-zA-Z0-9_]*)(\s*->)/',
                function ($matches) use ($keywordChecker) {
                    $ternaryOp = $matches[1];
                    $var = $matches[2];
                    $arrow = $matches[3];

                    // Ne pas transformer si c'est un mot-clé
                    if ($keywordChecker->isKeyword($var)) {
                        return $ternaryOp . $var . $arrow;
                    }

                    // C'est un objet (user->), donc transformer en $user->
                    return $ternaryOp . '$' . $var . $arrow;
                },
                $expression,
                -1,
                $count
            );

            if ($count === 0) {
                break;
            }

            $expression = $newExpression;

            // Protéger les variables PHP après chaque transformation
            $expression = $this->protector->protect($expression);

            $iteration++;
        }

        // ÉTAPE 4 : Transformer les variables dans les index de tableaux
        // Exemple: $colors[type ?? 'info'] → $colors[$type ?? 'info']
        // IMPORTANT : Protéger les chaînes AVANT de restaurer les variables protégées
        // pour éviter que les chaînes soient transformées
        $stringPlaceholders = [];
        $stringIndex = 0;
        $expressionWithProtectedStrings = preg_replace_callback(
            '/(["\'])(?:\\\\.|(?!\1)[^\\\\])*\1/',
            function ($matches) use (&$stringPlaceholders, &$stringIndex) {
                $placeholder = '##VAR_TRANS_STR_' . $stringIndex . '##';
                $stringPlaceholders[$placeholder] = $matches[0];
                $stringIndex++;
                return $placeholder;
            },
            $expression
        );

        // Restaurer temporairement les variables protégées pour transformer les index
        // IMPORTANT : ne pas restaurer les chaînes ici, seulement les variables PHP
        $tempExpression = $this->restoreVariablesOnly($expressionWithProtectedStrings);
        $iteration = 0;
        while ($iteration < $maxIterations) {
            $newExpression = preg_replace_callback(
                '/\[([^\]]+)\]/',
                function ($matches) use ($keywordChecker) {
                    $index = $matches[1];

                    // Protéger les chaînes dans l'index
                    $stringPlaceholders = [];
                    $stringIndex = 0;
                    $protectedIndex = preg_replace_callback(
                        '/(["\'])(?:\\\\.|(?!\1)[^\\\\])*\1/',
                        function ($strMatches) use (&$stringPlaceholders, &$stringIndex) {
                            $placeholder = '##INDEX_STR_' . $stringIndex . '##';
                            $stringPlaceholders[$placeholder] = $strMatches[0];
                            $stringIndex++;
                            return $placeholder;
                        },
                        $index
                    );

                    // Transformer les variables dans l'index (sauf dans les chaînes)
                    $transformedIndex = preg_replace_callback(
                        '/(?<!\$)(?<!##)\b([a-zA-Z_][a-zA-Z0-9_]*)(?=\s|$|\)|,|;|:|\?|&|\||\[|=>|==|!=|<=|>=|<|>)/',
                        function ($varMatches) use ($keywordChecker) {
                            $var = $varMatches[1];

                            // Ne pas transformer si c'est un mot-clé
                            if ($keywordChecker->isKeyword($var)) {
                                return $var;
                            }

                            return '$' . $var;
                        },
                        $protectedIndex
                    );

                    // Restaurer les chaînes
                    foreach ($stringPlaceholders as $placeholder => $original) {
                        $transformedIndex = str_replace($placeholder, $original, $transformedIndex);
                    }

                    return '[' . $transformedIndex . ']';
                },
                $tempExpression,
                -1,
                $count
            );

            if ($count === 0) {
                break;
            }

            $tempExpression = $newExpression;
            $iteration++;
        }

        // Re-protéger les variables après transformation des index
        $expression = $this->protector->protect($tempExpression);

        // Restaurer les chaînes protégées
        foreach ($stringPlaceholders as $placeholder => $original) {
            $expression = str_replace($placeholder, $original, $expression);
        }

        // ÉTAPE 5 : Transformer les variables simples restantes
        // IMPORTANT : Ne pas transformer les méthodes (format, render, etc.) qui sont après ->
        $iteration = 0;
        while ($iteration < $maxIterations) {
            $newExpression = preg_replace_callback(
                '/(?<!\$)(?<!##)\b([a-zA-Z_][a-zA-Z0-9_]*)(?=\s|$|\)|,|;|\[|\]|=>|==|!=|<=|>=|<|>|&&|\|\||and|or)/',
                function ($matches) use ($keywordChecker, $expression) {
                    $var = $matches[1];
                    $fullMatch = $matches[0];

                    // Ne pas transformer si c'est un mot-clé
                    if ($keywordChecker->isKeyword($var)) {
                        return $var;
                    }

                    // Vérifier si c'est une méthode (précédée de -> ou [)
                    $matchPos = strpos($expression, $fullMatch);
                    if ($matchPos !== false && $matchPos > 0) {
                        $before = substr($expression, max(0, $matchPos - 10), $matchPos - max(0, $matchPos - 10));
                        // Si c'est précédé de -> ou ], c'est une méthode, ne pas transformer
                        if (preg_match('/(->|])\s*$/', $before)) {
                            return $var;
                        }
                    }

                    return '$' . $var;
                },
                $expression,
                -1,
                $count
            );

            if ($count === 0) {
                break;
            }

            $expression = $newExpression;

            // Protéger les variables PHP après chaque transformation
            $expression = $this->protector->protect($expression);

            $iteration++;
        }

        // ÉTAPE 6 : Restaurer les variables protégées
        $expression = $this->protector->restore($expression);

        // ÉTAPE 7 : Restaurer les chaînes protégées
        $expression = $this->placeholderManager->restore($expression);

        return $expression;
    }

    /**
     * Restaure uniquement les variables protégées (pas les chaînes)
     */
    private function restoreVariablesOnly(string $expression): string
    {
        foreach ($this->placeholderManager->getPlaceholders() as $placeholder => $original) {
            if (strpos($placeholder, '##STRING_') === 0) {
                continue;
            }
            $expression = str_replace($placeholder, $original, $expression);
        }
        return $expression;
    }
}
