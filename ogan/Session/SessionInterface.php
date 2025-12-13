<?php

namespace Ogan\Session;

/**
 * Interface pour la gestion de session
 */
interface SessionInterface
{
    /**
     * Démarre la session
     */
    public function start(): void;

    /**
     * Récupère une valeur de la session
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Définit une valeur dans la session
     */
    public function set(string $key, mixed $value): void;

    /**
     * Vérifie si une clé existe
     */
    public function has(string $key): bool;

    /**
     * Supprime une clé
     */
    public function remove(string $key): void;

    /**
     * Vide la session
     */
    public function clear(): void;

    /**
     * Détruit complètement la session (vide + supprime le cookie)
     */
    public function destroy(): void;

    /**
     * Ajoute un message flash (qui ne dure qu'une requête)
     */
    public function setFlash(string $type, string $message): void;

    /**
     * Récupère les messages flash d'un type et les supprime
     */
    public function getFlashes(string $type): array;

    /**
     * Vérifie si un message flash existe pour un type donné
     */
    public function hasFlash(string $type): bool;

    /**
     * Récupère le premier message flash d'un type et le supprime
     * 
     * @param string $type Type du message flash
     * @param string|null $default Valeur par défaut si aucun flash
     * @return string|null Le message flash ou la valeur par défaut
     */
    public function getFlash(string $type, ?string $default = null): ?string;

    /**
     * Récupère TOUS les messages flash de tous les types et les supprime
     * 
     * Retourne un tableau associatif :
     * [
     *     'success' => ['Message 1', 'Message 2'],
     *     'error' => ['Erreur 1'],
     *     'warning' => ['Attention !']
     * ]
     * 
     * @return array<string, array<string>> Tableau des messages par type
     */
    public function getAllFlashes(): array;

    /**
     * Régénère l'ID de session (sécurité anti-fixation)
     */
    public function migrate(): void;

    /**
     * Alias de migrate() - Régénère l'ID de session
     */
    public function regenerate(): void;

    /**
     * Récupère l'ID actuel
     */
    public function getId(): string;
}
