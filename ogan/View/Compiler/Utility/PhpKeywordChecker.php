<?php

namespace Ogan\View\Compiler\Utility;

/**
 * Vérifie si un identifiant est un mot-clé PHP
 */
class PhpKeywordChecker
{
    /**
     * Liste des mots-clés PHP à ne pas transformer en variables
     */
    private const KEYWORDS = [
        'true',
        'false',
        'null',
        'isset',
        'empty',
        'count',
        'strlen',
        'array_key_exists',
        'array',
        'json_encode',
        'json_decode',
        'htmlspecialchars',
        'date',
        'time',
        'md5',
        'sha1',
        'if',
        'else',
        'elseif',
        'foreach',
        'for',
        'while',
        'do',
        'switch',
        'case',
        'default',
        'break',
        'continue',
        'return',
        'function',
        'class',
        'interface',
        'trait',
        'namespace',
        'use',
        'as',
        'and',
        'or',
        'xor',
        'not',
        'in'
    ];

    /**
     * Vérifie si un identifiant est un mot-clé PHP
     * 
     * @param string $identifier Identifiant à vérifier
     * @return bool True si c'est un mot-clé PHP
     */
    public function isKeyword(string $identifier): bool
    {
        return in_array(strtolower($identifier), self::KEYWORDS);
    }

    /**
     * Retourne la liste des mots-clés PHP
     * 
     * @return array Liste des mots-clés
     */
    public function getKeywords(): array
    {
        return self::KEYWORDS;
    }
}
