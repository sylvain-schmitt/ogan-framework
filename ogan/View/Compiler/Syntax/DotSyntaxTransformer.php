<?php

namespace Ogan\View\Compiler\Syntax;

use Ogan\View\Compiler\Utility\PlaceholderManager;

/**
 * Transforme la syntaxe point (.) en syntaxe flèche (->)
 * Exemples :
 * - form.render() → form->render()
 * - user.getId → user->getId()
 * - user.getCreatedAt.format() → user->getCreatedAt()->format()
 */
class DotSyntaxTransformer
{
    private PlaceholderManager $placeholderManager;

    public function __construct(PlaceholderManager $placeholderManager)
    {
        $this->placeholderManager = $placeholderManager;
    }

    /**
     * Transforme les points en flèches
     * 
     * @param string $expression Expression à transformer
     * @return string Expression transformée
     */
    public function transform(string $expression): string
    {
        // Protéger les chaînes entre guillemets
        $placeholders = [];
        $placeholderIndex = 0;
        $expression = preg_replace_callback(
            '/(["\'])(?:\\\\.|(?!\1)[^\\\\])*\1/',
            function ($matches) use (&$placeholders, &$placeholderIndex) {
                $placeholder = '##STRING_DOT_' . $placeholderIndex . '##';
                $placeholders[$placeholder] = $matches[0];
                $placeholderIndex++;
                return $placeholder;
            },
            $expression
        );

        // Protéger les placeholders existants
        $existingPlaceholders = [];
        $existingPlaceholderIndex = 0;
        $expression = preg_replace_callback(
            '/##[A-Z_]+_\d+##/',
            function ($matches) use (&$existingPlaceholders, &$existingPlaceholderIndex) {
                $placeholder = '##PROTECTED_DOT_' . $existingPlaceholderIndex . '##';
                $existingPlaceholders[$placeholder] = $matches[0];
                $existingPlaceholderIndex++;
                return $placeholder;
            },
            $expression
        );

        // Transformer les points en flèches de manière itérative
        $maxIterations = 10;
        $iteration = 0;
        while (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)(\s*\()?/', $expression) && $iteration < $maxIterations) {
            $expression = preg_replace_callback(
                '/([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)(\s*\()?/',
                function ($matches) {
                    $object = $matches[1];
                    $member = $matches[2];

                    // Vérifier que ce n'est pas un placeholder
                    if (strpos($object, '##') === 0 || strpos($member, '##') === 0) {
                        return $matches[0];
                    }

                    $hasParens = isset($matches[3]) && trim($matches[3]) === '(';

                    if ($hasParens) {
                        return $object . '->' . $member . '(';
                    } else {
                        // Détecter si c'est probablement une méthode
                        $isMethod = preg_match('/^(get|set|is|has|can|should|will|do|make|create|find|save|delete|update|remove|add|clear|reset|load|fetch|build|generate|render|format|toString|toArray|toJson|toXml|toYaml|toCsv|toHtml|toText|toMarkdown|toRst|toAscii|toBase64|toHex|toBinary|toOctal|toDecimal|toFloat|toInt|toBool|toBoolean)/i', $member);

                        if ($isMethod) {
                            return $object . '->' . $member . '()';
                        } else {
                            return $object . '->' . $member;
                        }
                    }
                },
                $expression,
                1
            );
            $iteration++;
        }

        // Gérer le cas où l'objet est déjà une expression (ex: user->getCreatedAt().format)
        $iteration = 0;
        while (preg_match('/([\)\]])\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)(\s*\()?/', $expression) && $iteration < $maxIterations) {
            $expression = preg_replace_callback(
                '/([\)\]])\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)(\s*\()?/',
                function ($matches) {
                    $closing = $matches[1];
                    $member = $matches[2];
                    $hasParens = isset($matches[3]) && trim($matches[3]) === '(';

                    $call = $closing . '->' . $member;
                    if ($hasParens) {
                        return $call . '(';
                    }

                    $isMethod = preg_match('/^(get|set|is|has|can|should|will|do|make|create|find|save|delete|update|remove|add|clear|reset|load|fetch|build|generate|render|format|toString|toArray|toJson|toXml|toYaml|toCsv|toHtml|toText|toMarkdown|toRst|toAscii|toBase64|toHex|toBinary|toOctal|toDecimal|toFloat|toInt|toBool|toBoolean)/i', $member);
                    return $isMethod ? $call . '()' : $call;
                },
                $expression,
                1
            );
            $iteration++;
        }

        // Restaurer les placeholders existants
        foreach ($existingPlaceholders as $placeholder => $original) {
            $expression = str_replace($placeholder, $original, $expression);
        }

        // Restaurer les chaînes
        foreach ($placeholders as $placeholder => $original) {
            $expression = str_replace($placeholder, $original, $expression);
        }

        return $expression;
    }
}
