<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ✅ CONSTRAINT INTERFACE (Strategy Pattern)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Définit le contrat pour toutes les contraintes de validation
 * des paramètres de routes.
 * 
 * CONCEPT :
 * ---------
 * Une contrainte valide qu'un paramètre de route respecte certaines règles.
 * 
 * EXEMPLES :
 * ----------
 * Route : /users/{id:\d+}
 * Contrainte : id doit être un nombre
 * 
 * Route : /posts/{slug:[a-z0-9-]+}
 * Contrainte : slug doit contenir uniquement lettres, chiffres et tirets
 * 
 * PATTERN STRATEGY :
 * ------------------
 * Chaque contrainte implémente sa propre stratégie de validation.
 * Le router peut utiliser n'importe quelle contrainte sans connaître
 * les détails d'implémentation.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Router\Constraint;

interface ConstraintInterface
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LA VALEUR RESPECTE LA CONTRAINTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Cette méthode est appelée lors du matching de la route
     * pour vérifier que le paramètre est valide.
     * 
     * @param string $value La valeur du paramètre à valider
     * @return bool TRUE si valide, FALSE sinon
     * 
     * EXEMPLES :
     * ----------
     * // RegexConstraint('\d+')
     * matches('123')   → true
     * matches('abc')   → false
     * 
     * // EnumConstraint(['active', 'inactive'])
     * matches('active')   → true
     * matches('deleted')  → false
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function matches(string $value): bool;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OBTENIR LE PATTERN REGEX DE LA CONTRAINTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne l'expression régulière qui représente cette contrainte.
     * Utilisé pour construire le pattern final de la route.
     * 
     * @return string Expression régulière (sans délimiteurs)
     * 
     * EXEMPLES :
     * ----------
     * RegexConstraint : '\d+'
     * EnumConstraint(['fr', 'en']) : 'fr|en'
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getPattern(): string;
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * PATTERN STRATEGY :
 * ------------------
 * Le pattern Strategy permet de :
 * 1. Définir une famille d'algorithmes (ici : validations)
 * 2. Encapsuler chaque algorithme dans une classe
 * 3. Les rendre interchangeables
 * 
 * Avantages :
 * - Ajouter de nouvelles contraintes sans modifier le code existant (Open/Closed)
 * - Tester chaque contrainte indépendamment
 * - Combiner plusieurs contraintes
 * 
 * TYPES DE CONTRAINTES POSSIBLES :
 * ---------------------------------
 * - RegexConstraint : Expression régulière custom
 * - IntegerConstraint : Nombre entier
 * - UuidConstraint : UUID valide
 * - EnumConstraint : Valeur dans une liste
 * - RangeConstraint : Valeur dans une plage (min/max)
 * - DateConstraint : Date valide
 * - EmailConstraint : Email valide
 * 
 * EXEMPLES D'AUTRES FRAMEWORKS :
 * -------------------------------
 * - Symfony : Requirements (regex)
 * - Laravel : where() dans les routes
 * - Express.js : Route parameters avec regex
 * - FastAPI : Path parameters avec types Python
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
