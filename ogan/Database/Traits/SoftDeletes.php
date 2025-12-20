<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗑️ SOFT DELETES TRAIT
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Permet la suppression logique des modèles (soft delete).
 * Au lieu de supprimer réellement l'enregistrement, il est marqué avec
 * une date de suppression (deleted_at).
 * 
 * USAGE :
 * -------
 * class Article extends Model {
 *     use SoftDeletes;
 * }
 * 
 * $article->delete();           // Soft delete (met deleted_at)
 * $article->forceDelete();      // Suppression réelle
 * $article->restore();          // Restaure (deleted_at = null)
 * $article->trashed();          // Vérifie si soft-deleted
 * 
 * Article::withTrashed()->get();   // Inclut les supprimés
 * Article::onlyTrashed()->get();   // Seulement les supprimés
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Traits;

use Ogan\Database\QueryBuilder;

trait SoftDeletes
{
    /**
     * Indique si une suppression forcée est en cours
     */
    protected bool $forceDeleting = false;

    /**
     * Nom de la colonne deleted_at
     */
    protected static string $deletedAtColumn = 'deleted_at';

    // ═══════════════════════════════════════════════════════════════════
    // GETTERS / SETTERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Récupère la date de suppression
     */
    public function getDeletedAt(): ?string
    {
        return $this->attributes[static::$deletedAtColumn] ?? null;
    }

    /**
     * Définit la date de suppression
     */
    public function setDeletedAt(?string $deletedAt): self
    {
        $this->attributes[static::$deletedAtColumn] = $deletedAt;
        
        // Synchroniser avec la propriété si elle existe
        // Conversion snake_case -> camelCase (ex: deleted_at -> deletedAt)
        $property = lcfirst(str_replace('_', '', ucwords(static::$deletedAtColumn, '_')));
        if (property_exists($this, $property)) {
            $this->$property = $deletedAt;
        }
        
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES DE SUPPRESSION
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Suppression logique (soft delete)
     * 
     * Override de Model::delete() lorsque le trait est utilisé
     */
    public function delete(): bool
    {
        // Si force delete, utiliser la suppression réelle
        if ($this->forceDeleting) {
            return $this->performForceDelete();
        }

        return $this->runSoftDelete();
    }

    /**
     * Exécute la suppression logique
     */
    protected function runSoftDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = static::$primaryKey;
        $id = $this->attributes[$primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $affected = QueryBuilder::table(static::getTableName())
            ->where($primaryKey, '=', $id)
            ->update([static::$deletedAtColumn => $now]);

        if ($affected > 0) {
            $this->setDeletedAt($now);
            return true;
        }

        return false;
    }

    /**
     * Suppression réelle (force delete)
     */
    public function forceDelete(): bool
    {
        $this->forceDeleting = true;
        $result = $this->performForceDelete();
        $this->forceDeleting = false;
        
        return $result;
    }

    /**
     * Exécute la suppression réelle
     */
    protected function performForceDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $primaryKey = static::$primaryKey;
        $id = $this->attributes[$primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        $affected = QueryBuilder::table(static::getTableName())
            ->where($primaryKey, '=', $id)
            ->delete();

        if ($affected > 0) {
            $this->exists = false;
            return true;
        }

        return false;
    }

    /**
     * Restaure une entité soft-deleted
     */
    public function restore(): bool
    {
        if (!$this->trashed()) {
            return false;
        }

        $primaryKey = static::$primaryKey;
        $id = $this->attributes[$primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        $affected = QueryBuilder::table(static::getTableName())
            ->where($primaryKey, '=', $id)
            ->update([static::$deletedAtColumn => null]);

        if ($affected > 0) {
            $this->setDeletedAt(null);
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'entité est soft-deleted
     */
    public function trashed(): bool
    {
        return $this->getDeletedAt() !== null;
    }

    // ═══════════════════════════════════════════════════════════════════
    // SCOPES STATIQUES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Inclut les enregistrements soft-deleted dans les requêtes
     * 
     * @return \Ogan\Database\QueryBuilder
     */
    public static function withTrashed(): QueryBuilder
    {
        // Utiliser QueryBuilder::table directement pour éviter le filtre automatique
        return QueryBuilder::table(static::getTableName());
    }

    /**
     * Retourne seulement les enregistrements soft-deleted
     * 
     * @return \Ogan\Database\QueryBuilder
     */
    public static function onlyTrashed(): QueryBuilder
    {
        // Utiliser QueryBuilder::table directement pour éviter le filtre automatique
        return QueryBuilder::table(static::getTableName())
            ->whereNotNull(static::$deletedAtColumn);
    }

    /**
     * Exclut les enregistrements soft-deleted (comportement par défaut)
     * 
     * @return \Ogan\Database\QueryBuilder
     */
    public static function withoutTrashed(): QueryBuilder
    {
        return QueryBuilder::table(static::getTableName())
            ->whereNull(static::$deletedAtColumn);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Vérifie si ce modèle utilise le trait SoftDeletes
     */
    public static function usesSoftDeletes(): bool
    {
        return true;
    }

    /**
     * Retourne le nom de la colonne deleted_at
     */
    public static function getDeletedAtColumn(): string
    {
        return static::$deletedAtColumn;
    }

    // ═══════════════════════════════════════════════════════════════════
    // OVERRIDE DE QUERY POUR FILTRE AUTO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Override de Model::query() pour appliquer le filtre soft delete
     * automatiquement sur toutes les requêtes
     * 
     * @return \Ogan\Database\QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return QueryBuilder::table(static::getTableName())
            ->whereNull(static::$deletedAtColumn);
    }
}
