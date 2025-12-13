<?php

namespace Ogan\View\Compiler\Syntax;

use Ogan\View\Compiler\Utility\PlaceholderManager;

/**
 * Transforme la syntaxe de filtres (pipe) en appels de fonctions PHP
 * Exemples :
 * - variable|upper → strtoupper(variable)
 * - variable|lower → strtolower(variable)
 * - variable|first → substr(variable, 0, 1) (pour les chaînes)
 * - variable|date('Y-m-d') → variable->format('Y-m-d')
 */
class FilterTransformer
{
    private PlaceholderManager $placeholderManager;

    // Mappage des filtres vers les fonctions PHP
    private const FILTERS = [
        'upper' => 'strtoupper',
        'lower' => 'strtolower',
        'capitalize' => 'ucfirst',
        'json' => 'json_encode',
        'trim' => 'trim',
        'nl2br' => 'nl2br',
        'e' => '$this->e',
        'escape' => '$this->e',
    ];

    public function __construct(PlaceholderManager $placeholderManager)
    {
        $this->placeholderManager = $placeholderManager;
    }

    /**
     * Transforme les filtres dans une expression
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
                $placeholder = '##STRING_FILTER_' . $placeholderIndex . '##';
                $placeholders[$placeholder] = $matches[0];
                $placeholderIndex++;
                return $placeholder;
            },
            $expression
        );

        // Protéger les placeholders existants (générés par d'autres transformateurs si nécessaire)
        // Note: Normalement, ExpressionParser appelle les transformateurs séquentiellement
        // et chaque transformateur gère ses propres placeholders ou utilise le PlaceholderManager partagé.
        // Ici on recrée une protection locale pour simplification.

        // Analyser l'expression pour trouver les pipes
        // On doit le faire de manière intelligente pour ne pas casser les OU logiques (||)
        // Heureusement, en PHP le OU logique est || ou OR, le bitwise OR est |
        // Dans les templates, on assume que | est un filtre sauf si c'est clairement un bitwise (rare en template)
        
        // On sépare par le caractère pipe, mais attention aux parenthèses et autres structures
        $parts = $this->splitByPipe($expression);
        
        if (count($parts) <= 1) {
            // Pas de filtre, on restaure et on retourne
            return $this->restoreStrings($expression, $placeholders);
        }

        // Le premier élément est la valeur
        $value = trim(array_shift($parts));
        
        // Les éléments suivants sont les filtres
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Analyser le filtre (nom et arguments)
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(?:\((.*)\))?$/', $part, $matches)) {
                $filterName = $matches[1];
                $args = $matches[2] ?? null; // Null si pas de parenthèses

                $value = $this->applyFilter($value, $filterName, $args);
            }
        }

        return $this->restoreStrings($value, $placeholders);
    }

    private function applyFilter(string $value, string $filterName, ?string $args): string
    {
        // Filtres spéciaux avec logique personnalisée
        if ($filterName === 'first') {
            // Pour l'instant on suppose que c'est une chaîne, comme demandé pour user.name|first
            // Si on voulait supporter les tableaux, il faudrait une fonction helper qui vérifie le type à l'exécution
            return 'substr(' . $value . ', 0, 1)';
        }
        
        if ($filterName === 'date') {
            // On suppose que la valeur est un DateTime
            $format = $args ?? "'Y-m-d H:i:s'";
            return $value . '->format(' . $format . ')';
        }

        // Filtres standards mappés
        if (isset(self::FILTERS[$filterName])) {
            $phpFunc = self::FILTERS[$filterName];
            
            if ($args !== null && trim($args) !== '') {
                return $phpFunc . '(' . $value . ', ' . $args . ')';
            } else {
                return $phpFunc . '(' . $value . ')';
            }
        }

        // Filtre inconnu : on retourne une fonction PHP du même nom
        if ($args !== null && trim($args) !== '') {
            return $filterName . '(' . $value . ', ' . $args . ')';
        } else {
            return $filterName . '(' . $value . ')';
        }
    }

    private function splitByPipe(string $expression): array
    {
        $parts = [];
        $buffer = '';
        $parenDepth = 0;
        $bracketDepth = 0;
        $braceDepth = 0;
        $inString = false;
        $stringChar = null;
        $len = strlen($expression);

        for ($i = 0; $i < $len; $i++) {
            $char = $expression[$i];
            
            // Gestion des chaînes (bien que déjà protégées, on garde la logique robuste)
            // Comme on a remplacé les chaînes par des placeholders, on ne devrait pas rencontrer de " ou ' réels
            // sauf s'ils font partie de la syntaxe environnante non protégée (rare)
            // Mais les placeholders contiennent des caractères sûrs (A-Z, 0-9, _)

            // Gestion des parenthèses/crochets/accolades
            if ($char === '(') $parenDepth++;
            elseif ($char === ')') $parenDepth--;
            elseif ($char === '[') $bracketDepth++;
            elseif ($char === ']') $bracketDepth--;
            elseif ($char === '{') $braceDepth++;
            elseif ($char === '}') $braceDepth--;

            // Détection du pipe séparateur
            if ($char === '|' && $parenDepth === 0 && $bracketDepth === 0 && $braceDepth === 0) {
                // Vérifier que ce n'est pas un || (OR logique)
                $nextChar = ($i + 1 < $len) ? $expression[$i + 1] : null;
                $prevChar = ($i > 0) ? $expression[$i - 1] : null;

                if ($nextChar === '|' || $prevChar === '|') {
                    $buffer .= $char;
                } else {
                    $parts[] = $buffer;
                    $buffer = '';
                }
            } else {
                $buffer .= $char;
            }
        }
        
        if ($buffer !== '') {
            $parts[] = $buffer;
        }

        return $parts;
    }

    private function restoreStrings(string $expression, array $placeholders): string
    {
        foreach ($placeholders as $placeholder => $original) {
            $expression = str_replace($placeholder, $original, $expression);
        }
        return $expression;
    }
}
