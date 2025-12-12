<?php

namespace Ogan\Config;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📄 YAML PARSER MAISON
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Parser YAML minimaliste supportant :
 * - Scalaires (string, int, bool, null)
 * - Tableaux et objets imbriqués
 * - Commentaires (#)
 * - Variables d'environnement (%env(VAR)%)
 * 
 * LIMITATIONS :
 * - Pas de support des ancres/références (&, *)
 * - Pas de support des types complexes (dates, etc.)
 * - Indentation : 2 ou 4 espaces
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
class YamlParser
{
    /**
     * Parse un fichier YAML
     */
    public static function parseFile(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new \Exception("Fichier YAML non trouvé : {$filepath}");
        }

        $content = file_get_contents($filepath);
        // Obtenir le chemin absolu puis remonter à la racine du projet
        $absolutePath = realpath($filepath);
        $projectDir = dirname($absolutePath, 2); // Remonter de config/ vers la racine
        
        return self::parse($content, $projectDir);
    }

    /**
     * Parse une chaîne YAML
     */
    public static function parse(string $yaml, ?string $projectDir = null): array
    {
        $lines = explode("\n", $yaml);
        $result = [];
        $stack = [&$result];
        $indentStack = [-1];

        foreach ($lines as $lineNum => $line) {
            // Supprimer les commentaires
            if (str_contains($line, '#')) {
                $line = preg_replace('/#.*$/', '', $line);
            }

            // Ignorer les lignes vides
            if (trim($line) === '') {
                continue;
            }

            // Calculer l'indentation
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $line = ltrim($line);

            // Gérer le niveau d'indentation
            while ($indent <= end($indentStack) && count($indentStack) > 1) {
                array_pop($stack);
                array_pop($indentStack);
            }

            // Parser la ligne
            if (preg_match('/^([a-zA-Z0-9_-]+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2]);

                if ($value === '') {
                    // Clé sans valeur = objet
                    $current = &$stack[count($stack) - 1];
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                } else {
                    // Clé avec valeur
                    $current = &$stack[count($stack) - 1];
                    $current[$key] = self::parseValue($value, $projectDir);
                }
            } elseif (preg_match('/^-\s+(.+)$/', $line, $matches)) {
                // Élément de liste
                $value = trim($matches[1]);
                $current = &$stack[count($stack) - 1];
                
                if (!is_array($current) || !array_is_list($current)) {
                    throw new \Exception("Erreur de syntaxe YAML ligne " . ($lineNum + 1));
                }
                
                $current[] = self::parseValue($value, $projectDir);
            }
        }

        return $result;
    }

    /**
     * Parse une valeur YAML
     */
    private static function parseValue(string $value, ?string $projectDir = null): mixed
    {
        $value = trim($value);

        // Variables d'environnement : %env(VAR)%
        if (preg_match('/^%env\(([A-Z_]+)\)%$/', $value, $matches)) {
            return $_ENV[$matches[1]] ?? getenv($matches[1]) ?: null;
        }
        
        // Variable kernel.project_dir : %kernel.project_dir%
        if ($projectDir && str_contains($value, '%kernel.project_dir%')) {
            $value = str_replace('%kernel.project_dir%', $projectDir, $value);
        }

        // Booléens
        if (in_array(strtolower($value), ['true', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', 'no', 'off'])) {
            return false;
        }

        // Null
        if (in_array(strtolower($value), ['null', '~', ''])) {
            return null;
        }

        // Nombres
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        // Chaînes entre guillemets
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
            return $matches[1];
        }

        // Chaîne simple
        return $value;
    }

    /**
     * Convertit un tableau PHP en YAML
     */
    public static function dump(array $data, int $indent = 0): string
    {
        $yaml = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    // Liste
                    $yaml .= "{$spaces}{$key}:\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= "{$spaces}  - \n";
                            $yaml .= self::dump($item, $indent + 2);
                        } else {
                            $yaml .= "{$spaces}  - " . self::dumpValue($item) . "\n";
                        }
                    }
                } else {
                    // Objet
                    $yaml .= "{$spaces}{$key}:\n";
                    $yaml .= self::dump($value, $indent + 1);
                }
            } else {
                $yaml .= "{$spaces}{$key}: " . self::dumpValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * Convertit une valeur en YAML
     */
    private static function dumpValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_string($value) && (str_contains($value, ':') || str_contains($value, '#'))) {
            return "'{$value}'";
        }
        return (string)$value;
    }
}
