<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📋 ENUM CONSTRAINT (Enumeration Validation)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Valide qu'un paramètre fait partie d'une liste de valeurs autorisées.
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * // Langue : fr ou en uniquement
 * new EnumConstraint(['fr', 'en'])
 * Route : /articles/{lang:fr|en}
 * Valide : /articles/fr, /articles/en
 * Invalide : /articles/es, /articles/de
 * 
 * // Status : active, inactive ou pending
 * new EnumConstraint(['active', 'inactive', 'pending'])
 * Route : /users/{status:active|inactive|pending}
 * Valide : /users/active
 * Invalide : /users/deleted
 * 
 * // Tri : asc ou desc
 * new EnumConstraint(['asc', 'desc'])
 * Route : /products/{sort:asc|desc}
 * Valide : /products/asc
 * Invalide : /products/ascending
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Router\Constraint;

class EnumConstraint implements ConstraintInterface
{
    /**
     * @param array<string> $allowedValues Liste des valeurs autorisées
     * @param bool $caseSensitive Sensible à la casse ? (défaut : true)
     */
    public function __construct(
        private array $allowedValues,
        private bool $caseSensitive = true
    ) {}

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LA VALEUR EST DANS LA LISTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utilise in_array() pour vérifier la présence.
     * 
     * @param string $value Valeur à valider
     * @return bool TRUE si dans la liste, FALSE sinon
     * 
     * COMPORTEMENT :
     * --------------
     * Si caseSensitive = true :
     *   'Active' != 'active' → false
     * 
     * Si caseSensitive = false :
     *   'Active' == 'active' → true
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function matches(string $value): bool
    {
        // ─────────────────────────────────────────────────────────────
        // Mode sensible à la casse (par défaut)
        // ─────────────────────────────────────────────────────────────
        if ($this->caseSensitive) {
            // Troisième paramètre 'true' : comparaison stricte (===)
            return in_array($value, $this->allowedValues, true);
        }

        // ─────────────────────────────────────────────────────────────
        // Mode insensible à la casse
        // ─────────────────────────────────────────────────────────────
        // On convertit tout en minuscules pour comparer
        $lowercaseValue = strtolower($value);
        $lowercaseAllowed = array_map('strtolower', $this->allowedValues);
        
        return in_array($lowercaseValue, $lowercaseAllowed, true);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OBTENIR LE PATTERN REGEX ÉQUIVALENT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Convertit la liste de valeurs en une expression régulière
     * avec des alternatives (pipe |).
     * 
     * @return string Pattern regex : 'value1|value2|value3'
     * 
     * EXEMPLES :
     * ----------
     * ['fr', 'en'] → 'fr|en'
     * ['active', 'inactive'] → 'active|inactive'
     * 
     * UTILISATION :
     * -------------
     * Ce pattern est utilisé pour construire le regex final de la route :
     * Route : /articles/{lang}
     * Constraint : EnumConstraint(['fr', 'en'])
     * Pattern final : /articles/(?P<lang>fr|en)
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getPattern(): string
    {
        // Échappe chaque valeur pour éviter les problèm es avec les caractères spéciaux regex
        $escapedValues = array_map(function($value) {
            return preg_quote($value, '~');
        }, $this->allowedValues);

        // Joint avec le pipe | (OU logique en regex)
        return implode('|', $escapedValues);
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * QUAND UTILISER EnumConstraint ?
 * --------------------------------
 * - Paramètres avec un nombre limité de valeurs possibles
 * - Alternatives bien définies (langue, statut, tri, format...)
 * - Validation stricte (pas de valeurs arbitraires)
 * 
 * EXEMPLES RÉELS :
 * ----------------
 * 
 * 1. LANGUES :
 * Route : /articles/{lang:fr|en|es}
 * Constraint : new EnumConstraint(['fr', 'en', 'es'])
 * 
 * 2. FORMATS DE SORTIE :
 * Route : /export/{format:json|csv|xml}
 * Constraint : new EnumConstraint(['json', 'csv', 'xml'])
 * 
 * 3. PÉRIODES :
 * Route : /stats/{period:day|week|month|year}
 * Constraint : new EnumConstraint(['day', 'week', 'month', 'year'])
 * 
 * 4. STATUTS :
 * Route : /orders/{status:pending|confirmed|shipped|delivered}
 * Constraint : new EnumConstraint(['pending', 'confirmed', 'shipped', 'delivered'])
 * 
 * AVANTAGES :
 * -----------
 * 1. VALIDATION AUTOMATIQUE : Pas besoin de valider dans le contrôleur
 * 2. DOCUMENTATION : La route indique clairement les valeurs possibles
 * 3. SÉCURITÉ : Empêche les valeurs inattendues
 * 4. AUTO-COMPLÉTION : Les IDE peuvent suggérer les valeurs possibles
 * 
 * COMPARAISON AVEC RegexConstraint :
 * -----------------------------------
 * EnumConstraint(['fr', 'en']) 
 * ≈ 
 * RegexConstraint('fr|en')
 * 
 * Mais EnumConstraint est :
 * - Plus lisible
 * - Plus facile à générer (depuis une DB par exemple)
 * - Gère automatiquement l'échappement
 * 
 * CASE SENSITIVITY :
 * ------------------
 * Par défaut, sensible à la casse :
 * $constraint = new EnumConstraint(['active', 'inactive']);
 * $constraint->matches('Active'); // false
 * 
 * Pour ignorer la casse :
 * $constraint = new EnumConstraint(['active', 'inactive'], false);
 * $constraint->matches('Active'); // true
 * $constraint->matches('ACTIVE'); // true
 * 
 * PREG_QUOTE() :
 * --------------
 * Échappe les caractères spéciaux regex dans une chaîne.
 * 
 * Sans preg_quote :
 * ['a.b', 'c'] → 'a.b|c' → '.' matcherait n'importe quel caractère
 * 
 * Avec preg_quote :
 * ['a.b', 'c'] → 'a\.b|c' → '.' est littéral
 * 
 * UTILISATION DANS LE ROUTER :
 * -----------------------------
 * // Définition
 * $router->get('/articles/{lang}', [ArticleController::class, 'index'])
 *     ->constraint('lang', new EnumConstraint(['fr', 'en', 'es']));
 * 
 * // Matching
 * /articles/fr → OK, lang = 'fr'
 * /articles/de → 404 (pas dans la liste)
 * 
 * ÉVOLUTIONS POSSIBLES :
 * ----------------------
 * 1. Charger depuis une base de données :
 *    $languages = Language::pluck('code')->toArray();
 *    new EnumConstraint($languages);
 * 
 * 2. Enum PHP 8.1+ :
 *    enum Lang: string {
 *        case FR = 'fr';
 *        case EN = 'en';
 *    }
 *    new EnumConstraint(Lang::cases());
 * 
 * 3. Validation avec transformation :
 *    Convertir automatiquement en minuscules avant validation
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
