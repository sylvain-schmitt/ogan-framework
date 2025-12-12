<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔍 REGEX CONSTRAINT (Regular Expression Validation)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Valide qu'un paramètre de route correspond à une expression régulière.
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * // ID numérique uniquement
 * new RegexConstraint('\d+')
 * Route : /users/{id:\d+}
 * Valide : /users/123
 * Invalide : /users/abc
 * 
 * // Slug alphanumérique avec tirets
 * new RegexConstraint('[a-z0-9-]+')
 * Route : /posts/{slug:[a-z0-9-]+}
 * Valide : /posts/my-first-post
 * Invalide : /posts/Mon Post! (espaces et majuscules)
 * 
 * // Code postal français
 * new RegexConstraint('\d{5}')
 * Route : /cities/{zipcode:\d{5}}
 * Valide : /cities/75001
 * Invalide : /cities/750
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Router\Constraint;

class RegexConstraint implements ConstraintInterface
{
    /**
     * @param string $pattern Expression régulière (sans délimiteurs ni ancres)
     *                        Exemples : '\d+', '[a-z]+', '[0-9]{4}'
     */
    public function __construct(
        private string $pattern
    ) {}

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LA VALEUR CORRESPOND AU PATTERN
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utilise preg_match() pour tester l'expression régulière.
     * 
     * @param string $value Valeur à valider
     * @return bool TRUE si le pattern matche, FALSE sinon
     * 
     * DÉTAILS :
     * ---------
     * - On ajoute les délimiteurs '~' car preg_match() les requiert
     * - On ajoute les ancres ^ et $ pour matcher la chaîne entière
     *   (sinon 'abc123' matcherait '\d+' car il contient des chiffres)
     * - 'u' : modifier Unicode pour supporter les caractères UTF-8
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function matches(string $value): bool
    {
        // Construit le pattern complet : ~^PATTERN$~u
        // ^ : début de chaîne
        // $ : fin de chaîne
        // u : mode Unicode
        $fullPattern = '~^' . $this->pattern . '$~u';
        
        // preg_match retourne 1 si match, 0 sinon, false en cas d'erreur
        return (bool) preg_match($fullPattern, $value);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OBTENIR LE PATTERN REGEX
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne le pattern tel qu'il a été défini (sans délimiteurs).
     * Utilisé pour construire le pattern complet de la route.
     * 
     * @return string Le pattern regex
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * EXPRESSIONS RÉGULIÈRES COURANTES :
 * -----------------------------------
 * \d      : Un chiffre (0-9)
 * \d+     : Un ou plusieurs chiffres
 * \d{5}   : Exactement 5 chiffres
 * [a-z]   : Une lettre minuscule
 * [a-z]+  : Une ou plusieurs lettres minuscules
 * [a-z0-9-]+ : Lettres, chiffres et tirets
 * [A-Za-z]   : Lettre majuscule ou minuscule
 * \w      : Caractère alphanumérique ou underscore [a-zA-Z0-9_]
 * .       : N'importe quel caractère
 * .*      : N'importe quelle chaîne (greedy)
 * 
 * ANCRES :
 * --------
 * ^       : Début de la chaîne
 * $       : Fin de la chaîne
 * 
 * Sans ancres : 'abc123' matcherait '\d+' (car il contient '123')
 * Avec ancres : 'abc123' ne matcherait PAS '^\d+$' (car ne contient pas QUE des chiffres)
 * 
 * QUANTIFICATEURS :
 * -----------------
 * *       : 0 ou plus
 * +       : 1 ou plus
 * ?       : 0 ou 1 (optionnel)
 * {n}     : Exactement n fois
 * {n,}    : Au moins n fois
 * {n,m}   : Entre n et m fois
 * 
 * EXEMPLES PRATIQUES :
 * --------------------
 * // UUID
 * [0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}
 * 
 * // Français : lettres avec accents
 * [a-zA-ZÀ-ÿ]+
 * 
 * // Email (simplifié)
 * [a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}
 * 
 * // Date YYYY-MM-DD
 * \d{4}-\d{2}-\d{2}
 * 
 * POURQUOI ~ COMME DÉLIMITEUR ?
 * ------------------------------
 * PHP requiert des délimiteurs pour les regex (/, ~, #, etc.)
 * On utilise ~ car il est rarement utilisé dans les patterns,
 * donc pas besoin d'échapper.
 * 
 * Alternatives :
 * - / : Nécessite d'échapper les / dans le pattern
 * - # : Nécessite d'échapper les # dans le pattern
 * - ~ : Rarement utilisé, donc moins de conflits
 * 
 * MODIFICATEURS :
 * ---------------
 * i : Insensible à la casse (a = A)
 * m : Multiligne (^ et $ matchent chaque ligne)
 * s : Dot matche les retours à la ligne
 * u : Mode Unicode (UTF-8)
 * x : Mode étendu (permet commentaires et espaces)
 * 
 * TESTS :
 * -------
 * $constraint = new RegexConstraint('\d+');
 * var_dump($constraint->matches('123'));   // true
 * var_dump($constraint->matches('abc'));   // false
 * var_dump($constraint->matches('12ab'));  // false (ancres ^ et $)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
