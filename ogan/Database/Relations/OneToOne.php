<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 ONETOONE - Relation Un-à-Un
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Représente une relation où un modèle parent a exactement un
 * modèle enfant.
 * 
 * EXEMPLE :
 * ---------
 * User (1) → (1) Profile
 * 
 * Un utilisateur a exactement un profil.
 * 
 * STRUCTURE :
 * -----------
 * Table `users` : id, name, email
 * Table `profiles` : id, bio, avatar, user_id (clé étrangère)
 * 
 * UTILISATION :
 * -------------
 * // Dans User.php
 * public function getProfile(): OneToOne
 * {
 *     return $this->oneToOne(Profile::class, 'user_id');
 * }
 * 
 * // Utilisation
 * $user = User::find(1);
 * $profile = $user->getProfile()->getResults(); // Instance de Profile ou null
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Relations;

use Ogan\Database\Model;

class OneToOne extends Relation
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

