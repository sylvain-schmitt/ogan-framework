<?php

namespace Ogan\View\Compiler\Expression;

use Ogan\View\Compiler\Syntax\DotSyntaxTransformer;
use Ogan\View\Compiler\Syntax\FilterTransformer;
use Ogan\View\Compiler\Variable\VariableTransformer;
use Ogan\View\Compiler\Utility\PlaceholderManager;
use Ogan\View\Compiler\Utility\StringProtector;

/**
 * Parse une expression et la convertit en code PHP
 * 
 * Détecte automatiquement les variables sans $ et les transforme
 * Exemples :
 * - user->getId() → $user->getId()
 * - user->name → $user->name
 * - user.getCreatedAt ? user.getCreatedAt.format(...) : 'N/A' → $user->getCreatedAt() ? $user->getCreatedAt()->format(...) : 'N/A'
 */
class ExpressionParser
{
    private DotSyntaxTransformer $dotSyntaxTransformer;
    private FilterTransformer $filterTransformer;
    private VariableTransformer $variableTransformer;
    private StringProtector $stringProtector;
    private PlaceholderManager $placeholderManager;

    public function __construct(
        DotSyntaxTransformer $dotSyntaxTransformer,
        FilterTransformer $filterTransformer,
        VariableTransformer $variableTransformer,
        StringProtector $stringProtector,
        PlaceholderManager $placeholderManager
    ) {
        $this->dotSyntaxTransformer = $dotSyntaxTransformer;
        $this->filterTransformer = $filterTransformer;
        $this->variableTransformer = $variableTransformer;
        $this->stringProtector = $stringProtector;
        $this->placeholderManager = $placeholderManager;
    }

    /**
     * Parse une expression et la convertit en code PHP
     * 
     * @param string $expression Expression à parser
     * @return string Expression convertie en PHP
     */
    public function parse(string $expression): string
    {
        $expression = trim($expression);

        // Réinitialiser les placeholders pour cette expression
        // (la protection/déprotection des chaînes est gérée dans VariableTransformer)
        $this->placeholderManager->reset();

        // Si c'est déjà du PHP valide (commence par $), on le retourne tel quel
        if (preg_match('/^\$/', $expression)) {
            return $expression;
        }

        // ÉTAPE 0 : Transformer la syntaxe app.xxx en $this->app()->getXxx()
        // IMPORTANT: Utilise une liste blanche des propriétés valides pour éviter
        // de transformer des chemins de fichiers comme 'app.css'
        // Propriétés supportées: user, session, request, flashes, debug, environment
        $appProperties = ['user', 'session', 'request', 'flashes', 'debug', 'environment'];
        $appPropertiesPattern = implode('|', $appProperties);
        
        $expression = preg_replace_callback(
            '/\bapp\.(' . $appPropertiesPattern . ')\b/',
            function ($matches) {
                $property = $matches[1];
                $getter = 'get' . ucfirst($property);
                return '$this->app()->' . $getter . '()';
            },
            $expression
        );

        // ÉTAPE 1 : Transformer les filtres (pipes)
        // Exemple: val|upper → strtoupper(val)
        // On le fait au début pour que les transformations suivantes s'appliquent au résultat
        $expression = $this->filterTransformer->transform($expression);

        // ÉTAPE 2 : Gérer les appels de fonctions (section, route, path, component, etc.)
        // Ces fonctions doivent être transformées en $this->functionName(...)
        $thisMethods = ['section', 'route', 'path', 'url', 'asset', 'css', 'js', 'component', 'e', 'escape', 'block', 'csrf_token', 'csrf_input', 'cssFramework', 'extend', 'start', 'end', 'hasFlash', 'getFlash', 'get', 'set', 'has', 'app'];

        // Détecter les appels de fonctions même après protection des chaînes
        // Le pattern doit aussi matcher les placeholders de chaînes (##STRING_X##)
        // Détecter aussi les appels qui commencent par une parenthèse : (get('key'))
        $expressionToCheck = $expression;
        if (preg_match('/^\(\s*(.+)\s*\)$/', $expression, $parenMatch)) {
            // Si l'expression est entourée de parenthèses, vérifier le contenu
            $expressionToCheck = trim($parenMatch[1]);
        }

        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $expressionToCheck, $matches)) {
            $functionName = $matches[1];

            // Trouver la position de l'ouverture de parenthèse dans l'expression originale
            // Si l'expression commence par (, chercher après la première (
            $openParenPos = strpos($expression, '(');
            if ($openParenPos === 0) {
                // L'expression commence par (, chercher la fonction après
                $openParenPos = strpos($expression, $functionName . '(', 1);
                if ($openParenPos === false) {
                    $openParenPos = strpos($expression, '(', strlen($functionName));
                } else {
                    $openParenPos = $openParenPos + strlen($functionName);
                }
            } else {
                $openParenPos = strpos($expression, '(', strlen($functionName));
            }
            if ($openParenPos !== false) {
                $parenDepth = 0;
                $bracketDepth = 0;
                $braceDepth = 0;
                $inString = false;
                $stringChar = null;
                $argsStart = $openParenPos + 1;
                $argsEnd = $argsStart;
                $found = false;

                for ($i = $openParenPos; $i < strlen($expression); $i++) {
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
                        continue;
                    }

                    // Compter les délimiteurs
                    if ($char === '(') {
                        $parenDepth++;
                    } elseif ($char === ')') {
                        $parenDepth--;
                        if ($parenDepth === 0) {
                            $argsEnd = $i;
                            $found = true;
                            break;
                        }
                    } elseif ($char === '[') {
                        $bracketDepth++;
                    } elseif ($char === ']') {
                        $bracketDepth--;
                    } elseif ($char === '{') {
                        $braceDepth++;
                    } elseif ($char === '}') {
                        $braceDepth--;
                    }
                }

                if ($found) {
                    $args = substr($expression, $argsStart, $argsEnd - $argsStart);
                    $args = trim($args);

                    // NOTE : On ne restaure pas les chaînes ici car VariableTransformer le fait déjà
                    // Si on restaure ici, VariableTransformer fait reset() et on perd les placeholders

                    // Transformer les appels de méthodes $this dans les arguments EN PREMIER
                    // Exemple: getFlash('success') → $this->getFlash('success')
                    $args = $this->transformThisMethodsInArguments($args, $thisMethods);

                    // Transformer la syntaxe point (.) en flèche (->) dans les arguments
                    // DotSyntaxTransformer gère ses propres placeholders
                    $args = $this->dotSyntaxTransformer->transform($args);

                    // Transformer les variables (ajout de $) dans les arguments
                    // VariableTransformer protège et restaure les chaînes lui-même
                    $args = $this->variableTransformer->transform($args);

                    // Si c'est une méthode de $this, utiliser $this->methodName(...)
                    if (in_array($functionName, $thisMethods)) {
                        return '$this->' . $functionName . '(' . $args . ')';
                    }

                    // Sinon, c'est une fonction PHP native
                    return $functionName . '(' . $args . ')';
                }
            }

            // Pas d'arguments ou extraction échouée
            if (in_array($functionName, $thisMethods)) {
                return '$this->' . $functionName . '()';
            }
            return $functionName . '()';
        }

        // ÉTAPE 3 : Transformer la syntaxe point (.) en flèche (->)
        $expression = $this->dotSyntaxTransformer->transform($expression);

        // ÉTAPE 4 : Transformer les variables (ajout de $)
        // VariableTransformer protège et restaure les chaînes lui-même
        $expression = $this->variableTransformer->transform($expression);

        // NOTE : Pas besoin de restaurer les chaînes ici car VariableTransformer l'a déjà fait

        return $expression;
    }

    /**
     * Transforme les appels de méthodes $this dans les arguments
     * Exemple: getFlash('success') → $this->getFlash('success')
     * 
     * @param string $args Arguments à transformer
     * @param array $thisMethods Liste des méthodes de $this
     * @return string Arguments transformés
     */
    private function transformThisMethodsInArguments(string $args, array $thisMethods): string
    {
        $offset = 0;
        while (($pos = strpos($args, '(', $offset)) !== false) {
            // Trouver le nom de la fonction avant la parenthèse
            $beforeParen = substr($args, 0, $pos);
            if (preg_match('/([a-zA-Z_][a-zA-Z0-9_]*)\s*$/', $beforeParen, $funcMatch)) {
                $functionName = $funcMatch[1];

                // Si c'est une méthode de $this et qu'elle n'est pas déjà transformée
                // Vérifier ce qui précède le nom de la fonction
                $startOfFunc = $pos - strlen($funcMatch[0]);
                $beforeFunc = substr($args, 0, $startOfFunc);
                
                if (
                    in_array($functionName, $thisMethods) &&
                    !preg_match('/\$this->\s*$/', $beforeFunc)
                ) {

                    // Trouver la parenthèse fermante correspondante
                    $openPos = $pos;
                    $parenDepth = 0;
                    $inString = false;
                    $stringChar = null;
                    $found = false;

                    for ($i = $openPos; $i < strlen($args); $i++) {
                        $char = $args[$i];

                        if (!$inString && ($char === '"' || $char === "'")) {
                            $inString = true;
                            $stringChar = $char;
                        } elseif ($inString && $char === $stringChar) {
                            if ($i > 0 && $args[$i - 1] !== '\\') {
                                $inString = false;
                                $stringChar = null;
                            }
                        }

                        if ($inString) continue;

                        if ($char === '(') $parenDepth++;
                        elseif ($char === ')') {
                            $parenDepth--;
                            if ($parenDepth === 0) {
                                $found = true;
                                $endPos = $i + 1;
                                break;
                            }
                        }
                    }

                    if ($found) {
                        // Remplacer getFlash(...) par $this->getFlash(...)
                        $functionCall = substr($args, $pos - strlen($functionName), $endPos - ($pos - strlen($functionName)));
                        $replacement = '$this->' . $functionCall;
                        $args = substr_replace($args, $replacement, $pos - strlen($functionName), $endPos - ($pos - strlen($functionName)));
                        $offset = $pos - strlen($functionName) + strlen($replacement);
                        continue;
                    }
                }
            }
            $offset = $pos + 1;
        }

        return $args;
    }
}
