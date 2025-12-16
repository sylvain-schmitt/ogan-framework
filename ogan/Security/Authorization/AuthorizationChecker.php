<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 AUTHORIZATION CHECKER - Service central d'autorisation
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Point d'entrée unique pour vérifier les permissions.
 * Agrège les votes de tous les Voters enregistrés.
 * 
 * STRATÉGIE DE DÉCISION:
 * ----------------------
 * - Si un Voter dit GRANTED et aucun ne dit DENIED → Accès autorisé
 * - Si un Voter dit DENIED → Accès refusé
 * - Si tous les Voters s'abstiennent → Accès refusé par défaut
 * 
 * EXEMPLE:
 * --------
 * $checker = new AuthorizationChecker($user);
 * $checker->addVoter(new RoleVoter());
 * $checker->addVoter(new RoleHierarchyVoter($hierarchy));
 * 
 * if ($checker->isGranted('ROLE_ADMIN')) { ... }
 * if ($checker->isGranted('edit', $post)) { ... }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Authorization;

use Ogan\Security\UserInterface;
use Ogan\Config\Config;

class AuthorizationChecker
{
    /**
     * @var VoterInterface[]
     */
    private array $voters = [];

    private ?UserInterface $user = null;

    private static ?self $instance = null;

    public function __construct(?UserInterface $user = null)
    {
        $this->user = $user;
        $this->registerDefaultVoters();
    }

    /**
     * Singleton pour accès global
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Définir l'utilisateur courant
     */
    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Récupérer l'utilisateur courant
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * Ajouter un Voter
     */
    public function addVoter(VoterInterface $voter): self
    {
        $this->voters[] = $voter;
        return $this;
    }

    /**
     * Vérifier si l'utilisateur a la permission
     * 
     * @param string $attribute L'attribut à vérifier (ex: 'ROLE_ADMIN', 'edit')
     * @param mixed $subject Le sujet optionnel (ex: Post instance)
     * @return bool true si autorisé
     */
    public function isGranted(string $attribute, mixed $subject = null): bool
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        
        foreach ($this->voters as $voter) {
            $vote = $voter->vote($this->user, $attribute, $subject);
            
            // Si un Voter refuse explicitement, refuser immédiatement
            if ($vote === VoterInterface::ACCESS_DENIED) {
                return false;
            }
            
            // Si un Voter accorde, noter le résultat
            if ($vote === VoterInterface::ACCESS_GRANTED) {
                $result = VoterInterface::ACCESS_GRANTED;
            }
        }
        
        // Autorisé seulement si au moins un Voter a accordé
        return $result === VoterInterface::ACCESS_GRANTED;
    }

    /**
     * Vérifier et lancer une exception si non autorisé
     * 
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGranted(string $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->isGranted($attribute, $subject)) {
            throw new AccessDeniedException($message);
        }
    }

    /**
     * Enregistrer les Voters par défaut
     */
    private function registerDefaultVoters(): void
    {
        // RoleVoter pour les vérifications de rôles simples
        $this->addVoter(new Voter\RoleVoter());
        
        // RoleHierarchyVoter si une hiérarchie est configurée
        $hierarchy = Config::get('security.role_hierarchy', []);
        if (!empty($hierarchy)) {
            $this->addVoter(new Voter\RoleHierarchyVoter($hierarchy));
        }
    }
}
