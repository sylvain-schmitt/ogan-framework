<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 👤 USER - Modèle Utilisateur
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Exemple d'utilisation de l'ORM avec le pattern Active Record
 * Style Symfony : propriétés explicites avec getters/setters
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Model;

use Ogan\Database\Model;

class User extends Model
{
    /**
     * Le nom de la table est automatiquement déduit : User → user (singulier)
     * Si vous voulez un nom différent, définissez :
     * protected static ?string $table = 'my_custom_table';
     */

    /**
     * @var string|null Clé primaire (optionnel, 'id' par défaut)
     */
    protected static ?string $primaryKey = 'id';

    // ─────────────────────────────────────────────────────────────
    // PROPRIÉTÉS (comme Symfony/Doctrine)
    // ─────────────────────────────────────────────────────────────

    /**
     * @var int|null ID de l'utilisateur
     */
    private ?int $id = null;

    /**
     * @var string|null Nom de l'utilisateur
     */
    private ?string $name = null;

    /**
     * @var string|null Email de l'utilisateur
     */
    private ?string $email = null;

    /**
     * @var string|null Mot de passe hashé
     */
    private ?string $password = null;

    /**
     * @var \DateTime|null Date de création
     */
    private ?\DateTime $createdAt = null;

    /**
     * @var \DateTime|null Date de mise à jour
     */
    private ?\DateTime $updatedAt = null;

    // ─────────────────────────────────────────────────────────────
    // GETTERS
    // ─────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    // ─────────────────────────────────────────────────────────────
    // SETTERS
    // ─────────────────────────────────────────────────────────────

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTHODES DE REQUÊTE
    // ─────────────────────────────────────────────────────────────

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER UN UTILISATEUR PAR EMAIL
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function findByEmail(string $email): ?self
    {
        // where() retourne un QueryBuilder, on doit utiliser la méthode statique first() de Model
        // qui hydrate automatiquement le résultat
        $result = self::where('email', '=', $email)->first();

        if ($result === null) {
            return null;
        }

        // Hydrater le résultat en instance de User
        $user = new static($result);
        $user->exists = true;
        $user->hydrateFromAttributes();
        return $user;
    }
}
