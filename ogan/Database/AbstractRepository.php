<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 ABSTRACTREPOSITORY - Implémentation de Base pour les Repositories
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Implémentation de base du Repository Pattern.
 * Fournit les méthodes CRUD de base.
 * 
 * EXEMPLE D'UTILISATION :
 * -----------------------
 * 
 * class UserRepository extends AbstractRepository {
 *     protected string $entityClass = User::class;
 *     protected string $table = 'users';
 *     
 *     public function findByEmail(string $email): ?User {
 *         return $this->findOneBy(['email' => $email]);
 *     }
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var string Classe de l'entité (doit être définie dans les classes filles)
     */
    protected string $entityClass;

    /**
     * @var string Nom de la table (doit être définie dans les classes filles)
     */
    protected string $table;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER UNE ENTITÉ PAR ID
     * ═══════════════════════════════════════════════════════════════════
     */
    public function find(int $id): ?object
    {
        $result = QueryBuilder::table($this->table)
            ->where('id', '=', $id)
            ->first();

        if ($result === null) {
            return null;
        }

        return $this->hydrate($result);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER TOUTES LES ENTITÉS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function findAll(): array
    {
        $results = QueryBuilder::table($this->table)->get();
        return array_map([$this, 'hydrate'], $results);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER DES ENTITÉS PAR CRITÈRES
     * ═══════════════════════════════════════════════════════════════════
     */
    public function findBy(array $criteria): array
    {
        $query = QueryBuilder::table($this->table);

        foreach ($criteria as $column => $value) {
            $query->where($column, '=', $value);
        }

        $results = $query->get();
        return array_map([$this, 'hydrate'], $results);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER UNE ENTITÉ PAR CRITÈRES
     * ═══════════════════════════════════════════════════════════════════
     */
    public function findOneBy(array $criteria): ?object
    {
        $query = QueryBuilder::table($this->table);

        foreach ($criteria as $column => $value) {
            $query->where($column, '=', $value);
        }

        $result = $query->first();

        if ($result === null) {
            return null;
        }

        return $this->hydrate($result);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SAUVEGARDER UNE ENTITÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    public function save(object $entity): bool
    {
        // Si l'entité est un Model, utiliser sa méthode save()
        if ($entity instanceof Model) {
            return $entity->save();
        }

        // Sinon, convertir en tableau et insérer/mettre à jour
        $data = $this->entityToArray($entity);

        if (isset($data['id']) && $data['id'] > 0) {
            // UPDATE
            return QueryBuilder::table($this->table)
                ->where('id', '=', $data['id'])
                ->update($data) > 0;
        } else {
            // INSERT
            $id = QueryBuilder::table($this->table)->insert($data);
            if ($id > 0 && method_exists($entity, 'setId')) {
                $entity->setId($id);
            }
            return $id > 0;
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUPPRIMER UNE ENTITÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    public function delete(object $entity): bool
    {
        // Si l'entité est un Model, utiliser sa méthode delete()
        if ($entity instanceof Model) {
            return $entity->delete();
        }

        // Sinon, extraire l'ID et supprimer
        $id = $this->getEntityId($entity);
        if ($id === null) {
            return false;
        }

        return QueryBuilder::table($this->table)
            ->where('id', '=', $id)
            ->delete() > 0;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HYDRATER UN RÉSULTAT EN ENTITÉ
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array $result Résultat SQL
     * @return object Instance de l'entité
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function hydrate(array $result): object
    {
        return new $this->entityClass($result);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UNE ENTITÉ EN TABLEAU
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param object $entity Entité à convertir
     * @return array Tableau de données
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function entityToArray(object $entity): array
    {
        if ($entity instanceof Model) {
            return $entity->toArray();
        }

        // Utiliser la réflexion pour extraire les propriétés
        $reflection = new \ReflectionClass($entity);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $data[$property->getName()] = $property->getValue($entity);
        }

        return $data;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER L'ID D'UNE ENTITÉ
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param object $entity Entité
     * @return int|null ID de l'entité
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function getEntityId(object $entity): ?int
    {
        if ($entity instanceof Model) {
            return $entity->id ?? null;
        }

        if (property_exists($entity, 'id')) {
            return $entity->id;
        }

        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }

        return null;
    }
}
