<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 MANYTOMANY - Relation Plusieurs-à-Plusieurs
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Représente une relation où plusieurs modèles sont liés à plusieurs
 * autres modèles via une table pivot.
 * 
 * EXEMPLE :
 * ---------
 * User (N) → (N) Role
 * 
 * Un utilisateur peut avoir plusieurs rôles, et un rôle peut être
 * assigné à plusieurs utilisateurs.
 * 
 * STRUCTURE :
 * -----------
 * Table `users` : id, name, email
 * Table `roles` : id, name
 * Table `user_role` (pivot) : user_id, role_id
 * 
 * UTILISATION :
 * -------------
 * // Dans User.php
 * public function getRoles(): ManyToMany
 * {
 *     return $this->manyToMany(Role::class, 'user_role', 'user_id', 'role_id');
 * }
 * 
 * // Utilisation
 * $user = User::find(1);
 * $roles = $user->getRoles()->getResults(); // Tableau de Role
 * 
 * // Attacher un rôle
 * $user->getRoles()->attach($roleId);
 * 
 * // Détacher un rôle
 * $user->getRoles()->detach($roleId);
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Relations;

use Ogan\Database\Model;
use Ogan\Database\QueryBuilder;

class ManyToMany extends Relation
{
    /**
     * @var string Nom de la table pivot
     */
    protected string $pivotTable;

    /**
     * @var string Clé étrangère vers le modèle parent dans la table pivot
     */
    protected string $pivotForeignKey;

    /**
     * @var string Clé étrangère vers le modèle cible dans la table pivot
     */
    protected string $pivotRelatedKey;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(
        Model $parent,
        string $related,
        string $pivotTable,
        string $pivotForeignKey,
        string $pivotRelatedKey,
        string $localKey = 'id'
    ) {
        parent::__construct($parent, $related, '', $localKey);
        $this->pivotTable = $pivotTable;
        $this->pivotForeignKey = $pivotForeignKey;
        $this->pivotRelatedKey = $pivotRelatedKey;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES RÉSULTATS DE LA RELATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne un tableau d'instances du modèle cible via la table pivot.
     * 
     * @return array Tableau d'instances du modèle cible
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getResults(): array
    {
        $localKeyValue = $this->getLocalKeyValue();
        
        if ($localKeyValue === null) {
            return [];
        }

        // Requête avec JOIN sur la table pivot
        $relatedTable = $this->getRelatedTable();
        $relatedKey = $this->getRelatedKey();

        $results = QueryBuilder::table($relatedTable)
            ->select([$relatedTable . '.*'])
            ->join($this->pivotTable, $relatedTable . '.' . $relatedKey, '=', $this->pivotTable . '.' . $this->pivotRelatedKey)
            ->where($this->pivotTable . '.' . $this->pivotForeignKey, '=', $localKeyValue)
            ->get();

        return $this->hydrate($results);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LA CLÉ PRIMAIRE DU MODÈLE CIBLE
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function getRelatedKey(): string
    {
        return $this->related::getPrimaryKeyName();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HYDRATER LES RÉSULTATS EN INSTANCES DE MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function hydrate(array $results): array
    {
        $models = [];
        foreach ($results as $result) {
            $model = new $this->related($result);
            $model->exists = true;
            $models[] = $model;
        }
        return $models;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ATTACHER UN MODÈLE À LA RELATION
     * ═══════════════════════════════════════════════════════════════════
     */
    public function attach(int $relatedId, array $pivotData = []): bool
    {
        $localKeyValue = $this->getLocalKeyValue();
        
        if ($localKeyValue === null) {
            return false;
        }

        $data = array_merge([
            $this->pivotForeignKey => $localKeyValue,
            $this->pivotRelatedKey => $relatedId,
        ], $pivotData);

        return QueryBuilder::table($this->pivotTable)->insert($data);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉTACHER UN MODÈLE DE LA RELATION
     * ═══════════════════════════════════════════════════════════════════
     */
    public function detach(int $relatedId = null): bool
    {
        $localKeyValue = $this->getLocalKeyValue();
        
        if ($localKeyValue === null) {
            return false;
        }

        $query = QueryBuilder::table($this->pivotTable)
            ->where($this->pivotForeignKey, '=', $localKeyValue);

        if ($relatedId !== null) {
            $query->where($this->pivotRelatedKey, '=', $relatedId);
        }

        return $query->delete();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COMPTER LE NOMBRE D'ÉLÉMENTS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function count(): int
    {
        $localKeyValue = $this->getLocalKeyValue();
        
        if ($localKeyValue === null) {
            return 0;
        }

        $result = QueryBuilder::table($this->pivotTable)
            ->where($this->pivotForeignKey, '=', $localKeyValue)
            ->select(['COUNT(*) as count'])
            ->first();

        return (int) ($result['count'] ?? 0);
    }
}

