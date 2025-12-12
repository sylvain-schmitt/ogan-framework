<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 POST - Modèle Article
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Model;

use Ogan\Database\Model;

class Post extends Model
{
    protected static ?string $primaryKey = 'id';

    // ─────────────────────────────────────────────────────────────
    // PROPRIÉTÉS
    // ─────────────────────────────────────────────────────────────

    /**
     * @var int|null ID de l'article
     */
    private ?int $id = null;

    /**
     * @var string|null Titre de l'article
     */
    private ?string $title = null;

    /**
     * @var string|null Contenu de l'article
     */
    private ?string $content = null;

    /**
     * @var int|null ID de l'utilisateur auteur
     */
    private ?int $userId = null;

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

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function getContent(): ?string { return $this->content; }
    public function getUserId(): ?int { return $this->userId; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }

    // ─────────────────────────────────────────────────────────────
    // SETTERS
    // ─────────────────────────────────────────────────────────────

    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }
    public function setContent(?string $content): self { $this->content = $content; return $this; }
    public function setUserId(?int $userId): self { $this->userId = $userId; return $this; }
    public function setCreatedAt(?\DateTime $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt(?\DateTime $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}

