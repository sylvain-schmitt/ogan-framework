<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 RELATION - Classe Abstraite pour les Relations ORM
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Classe de base pour toutes les relations entre modèles.
 * 
 * CONCEPT :
 * --------
 * Une relation lie deux modèles entre eux :
 * - User (1) → (N) Post (OneToMany)
 * - Post (N) → (1) User (ManyToOne)
 * - User (1) → (1) Profile (OneToOne)
 * - User (N) → (N) Role (ManyToMany)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Relations;

use Ogan\Database\Model;
use Ogan\Database\QueryBuilder;

abstract class Relation
{
    /**
     * @var Model Modèle parent (celui qui possède la relation)
     */
    protected Model $parent;

    /**
     * @var string Classe du modèle cible
     */
    protected string $related;

    /**
     * @var string Clé étrangère dans la table cible
     */
    protected string $foreignKey;

    /**
     * @var string Clé locale dans la table parent
     */
    protected string $localKey;

    /**
     * @var QueryBuilder|null Query builder pour la requête
     */
    protected ?QueryBuilder $query = null;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(Model $parent, string $related, string $foreignKey, string $localKey = 'id')
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE QUERY BUILDER
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function getQuery(): QueryBuilder
    {
        if ($this->query === null) {
            $this->query = QueryBuilder::table($this->getRelatedTable());
        }
        return $this->query;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE NOM DE LA TABLE DU MODÈLE CIBLE
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function getRelatedTable(): string
    {
        // Utiliser une méthode statique si disponible, sinon utiliser une propriété statique
        if (method_exists($this->related, 'getTableName')) {
            return $this->related::getTableName();
        }
        
        // Fallback : essayer d'accéder à la propriété statique $table
        $reflection = new \ReflectionClass($this->related);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        return $property->getValue();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LA VALEUR DE LA CLÉ LOCALE
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function getLocalKeyValue(): mixed
    {
        return $this->parent->{$this->localKey};
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES RÉSULTATS DE LA RELATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * À implémenter dans les classes filles
     */
    abstract public function getResults(): mixed;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER DES CONTRAINTES À LA REQUÊTE
     * ═══════════════════════════════════════════════════════════════════
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->getQuery()->where($column, $operator, $value);
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UN ORDER BY
     * ═══════════════════════════════════════════════════════════════════
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->getQuery()->orderBy($column, $direction);
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * LIMITER LE NOMBRE DE RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function limit(int $limit): self
    {
        $this->getQuery()->limit($limit);
        return $this;
    }
}

