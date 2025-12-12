<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 PASSWORDHASHER - Service de Hashage de Mot de Passe
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Service pour hasher et vérifier les mots de passe.
 * À utiliser dans le package Security, pas dans les modèles.
 * 
 * UTILISATION :
 * -------------
 * $hasher = new PasswordHasher();
 * $hash = $hasher->hash('mon_mot_de_passe');
 * $isValid = $hasher->verify('mon_mot_de_passe', $hash);
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security;

class PasswordHasher
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * HASHER UN MOT DE PASSE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $password Mot de passe en clair
     * @return string Mot de passe hashé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER UN MOT DE PASSE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $password Mot de passe en clair
     * @param string $hash Mot de passe hashé stocké
     * @return bool TRUE si le mot de passe est valide
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UN HASH DOIT ÊTRE RÉHASHÉ
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utile pour mettre à jour les anciens algorithmes de hash.
     * 
     * @param string $hash Mot de passe hashé
     * @return bool TRUE si le hash doit être réhashé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
}

