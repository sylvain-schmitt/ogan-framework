<?php

namespace Ogan\View\Compiler\Utility;

/**
 * Protège les chaînes de caractères pendant la compilation
 */
class StringProtector
{
    private PlaceholderManager $placeholderManager;

    public function __construct(PlaceholderManager $placeholderManager)
    {
        $this->placeholderManager = $placeholderManager;
    }

    /**
     * Protège toutes les chaînes (guillemets simples et doubles) dans une expression
     * 
     * @param string $expression Expression à protéger
     * @return string Expression avec chaînes protégées
     */
    public function protectStrings(string $expression): string
    {
        $offset = 0;

        while ($offset < strlen($expression)) {
            $posSingle = strpos($expression, "'", $offset);
            $posDouble = strpos($expression, '"', $offset);

            $pos = false;
            if ($posSingle !== false && $posDouble !== false) {
                $pos = min($posSingle, $posDouble);
            } elseif ($posSingle !== false) {
                $pos = $posSingle;
            } elseif ($posDouble !== false) {
                $pos = $posDouble;
            }

            if ($pos === false) {
                break;
            }

            $quote = $expression[$pos];
            $start = $pos;
            $end = $start + 1;

            // Chercher la fin de la chaîne en gérant les échappements
            while ($end < strlen($expression)) {
                if ($expression[$end] === $quote) {
                    // Vérifier si c'est échappé
                    $escapeCount = 0;
                    $checkPos = $end - 1;
                    while ($checkPos >= $start && $expression[$checkPos] === '\\') {
                        $escapeCount++;
                        $checkPos--;
                    }
                    // Si le nombre de backslashes est pair, la chaîne se termine ici
                    if ($escapeCount % 2 === 0) {
                        $end++;
                        break;
                    }
                }
                $end++;
            }

            // Extraire la chaîne complète
            $string = substr($expression, $start, $end - $start);

            // Protéger avec le PlaceholderManager
            $expression = $this->placeholderManager->protect($expression, $string, 'STRING');
            $offset = $start + strlen('##STRING_' . ($this->placeholderManager->getPlaceholders() ? count($this->placeholderManager->getPlaceholders()) - 1 : 0) . '##');
        }

        return $expression;
    }
}
