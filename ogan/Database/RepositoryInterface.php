<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 REPOSITORYINTERFACE - Interface pour les Repositories
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Interface pour le pattern Repository (Data Mapper).
 * Sépare la logique métier de la persistance.
 * 
 * REPOSITORY PATTERN :
 * --------------------
 * 
 * Le Repository Pattern sépare :
 * - Les entités (modèles métier)
 * - La persistance (accès à la base de données)
 * 
 * Avantages :
 * - Testabilité : on peut créer un FakeRepository pour les tests
 * - Flexibilité : on peut changer de DB sans modifier les entités
 * - Séparation des responsabilités (SOLID)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database;

interface RepositoryInterface
{
    /**
     * Trouver une entité par ID
     * 
     * @param int $id ID de l'entité
     * @return object|null Instance de l'entité ou null
     */
    public function find(int $id): ?object;

    /**
     * Trouver toutes les entités
     * 
     * @return array Tableau d'entités
     */
    public function findAll(): array;

    /**
     * Trouver des entités par critères
     * 
     * @param array $criteria Critères de recherche
     * @return array Tableau d'entités
     */
    public function findBy(array $criteria): array;

    /**
     * Trouver une entité par critères (première trouvée)
     * 
     * @param array $criteria Critères de recherche
     * @return object|null Instance de l'entité ou null
     */
    public function findOneBy(array $criteria): ?object;

    /**
     * Sauvegarder une entité (INSERT ou UPDATE)
     * 
     * @param object $entity Entité à sauvegarder
     * @return bool TRUE si succès
     */
    public function save(object $entity): bool;

    /**
     * Supprimer une entité
     * 
     * @param object $entity Entité à supprimer
     * @return bool TRUE si succès
     */
    public function delete(object $entity): bool;
}
