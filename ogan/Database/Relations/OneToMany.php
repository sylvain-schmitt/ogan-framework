<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 ONETOMANY - Relation Un-à-Plusieurs
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Représente une relation où un modèle parent peut avoir plusieurs
 * modèles enfants.
 * 
 * EXEMPLE :
 * ---------
 * User (1) → (N) Post
 * 
 * Un utilisateur peut avoir plusieurs posts.
 * 
 * STRUCTURE :
 * -----------
 * Table `users` : id, name, email
 * Table `posts` : id, title, content, user_id (clé étrangère)
 * 
 * UTILISATION :
 * -------------
 * // Dans User.php
 * public function getPosts(): OneToMany
 * {
 *     return $this->oneToMany(Post::class, 'user_id');
 * }
 * 
 * // Utilisation
 * $user = User::find(1);
 * $posts = $user->getPosts()->getResults(); // Tableau de Post
 * 
 * // Avec contraintes
 * $recentPosts = $user->getPosts()
 *     ->where('created_at', '>', '2024-01-01')
 *     ->orderBy('created_at', 'DESC')
 *     ->getResults();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Relations;

use Ogan\Database\Model;

class OneToMany extends Relation
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES RÉSULTATS DE LA RELATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne un tableau d'instances du modèle cible.
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

        $results = $this->getQuery()
            ->where($this->foreignKey, '=', $localKeyValue)
            ->get();

        return $this->hydrate($results);
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
     * COMPTER LE NOMBRE D'ÉLÉMENTS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function count(): int
    {
        $localKeyValue = $this->getLocalKeyValue();
        
        if ($localKeyValue === null) {
            return 0;
        }

        $result = $this->getQuery()
            ->where($this->foreignKey, '=', $localKeyValue)
            ->select(['COUNT(*) as count'])
            ->first();

        return (int) ($result['count'] ?? 0);
    }
}

