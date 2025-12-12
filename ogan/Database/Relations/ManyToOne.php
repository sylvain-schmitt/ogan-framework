<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 MANYTOONE - Relation Plusieurs-à-Un
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Représente une relation où plusieurs modèles enfants appartiennent
 * à un modèle parent.
 * 
 * EXEMPLE :
 * ---------
 * Post (N) → (1) User
 * 
 * Plusieurs posts appartiennent à un utilisateur.
 * 
 * STRUCTURE :
 * -----------
 * Table `users` : id, name, email
 * Table `posts` : id, title, content, user_id (clé étrangère)
 * 
 * UTILISATION :
 * -------------
 * // Dans Post.php
 * public function getUser(): ManyToOne
 * {
 *     return $this->manyToOne(User::class, 'user_id');
 * }
 * 
 * // Utilisation
 * $post = Post::find(1);
 * $user = $post->getUser()->getResults(); // Instance de User ou null
 * 
 * // Avec contraintes (peu utilisé pour ManyToOne, mais possible)
 * $user = $post->getUser()
 *     ->where('active', '=', 1)
 *     ->getResults();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Relations;

use Ogan\Database\Model;

class ManyToOne extends Relation
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE RÉSULTAT DE LA RELATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne une instance du modèle cible ou null.
     * 
     * @return Model|null Instance du modèle cible ou null
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getResults(): ?Model
    {
        $localKeyValue = $this->getLocalKeyValue();
        
        if ($localKeyValue === null) {
            return null;
        }

        $result = $this->getQuery()
            ->where($this->foreignKey, '=', $localKeyValue)
            ->first();

        if ($result === null) {
            return null;
        }

        $model = new $this->related($result);
        $model->exists = true;
        return $model;
    }
}

