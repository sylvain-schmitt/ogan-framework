<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗳️ ABSTRACT VOTER - Classe de base pour les Voters
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Simplifie la création de Voters en ne demandant que:
 * - supports(): quels attributs et sujets ce Voter gère
 * - voteOnAttribute(): la logique de décision
 * 
 * EXEMPLE D'UTILISATION:
 * ----------------------
 * class PostVoter extends AbstractVoter
 * {
 *     protected function supports(string $attribute, mixed $subject): bool
 *     {
 *         return in_array($attribute, ['edit', 'delete']) 
 *             && $subject instanceof Post;
 *     }
 * 
 *     protected function voteOnAttribute(string $attribute, mixed $subject, UserInterface $user): bool
 *     {
 *         return $subject->getAuthorId() === $user->getId();
 *     }
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Authorization;

use Ogan\Security\UserInterface;

abstract class AbstractVoter implements VoterInterface
{
    /**
     * Vote pour ou contre l'accès
     */
    public function vote(?UserInterface $user, string $attribute, mixed $subject): int
    {
        // Si le Voter ne supporte pas cet attribut/sujet, s'abstenir
        if (!$this->supports($attribute, $subject)) {
            return self::ACCESS_ABSTAIN;
        }

        // Si pas d'utilisateur connecté, refuser
        if ($user === null) {
            return self::ACCESS_DENIED;
        }

        // Déléguer à la méthode de décision
        return $this->voteOnAttribute($attribute, $subject, $user)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    /**
     * Détermine si ce Voter supporte l'attribut et le sujet donnés
     */
    abstract public function supports(string $attribute, mixed $subject): bool;

    /**
     * Effectue la vérification d'accès
     * 
     * @param string $attribute L'attribut à vérifier (ex: 'edit', 'delete')
     * @param mixed $subject Le sujet (ex: instance de Post)
     * @param UserInterface $user L'utilisateur courant
     * @return bool true si accès autorisé, false sinon
     */
    abstract protected function voteOnAttribute(string $attribute, mixed $subject, UserInterface $user): bool;
}
