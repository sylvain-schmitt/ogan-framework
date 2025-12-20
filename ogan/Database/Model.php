<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📦 MODEL - Classe de Base pour les Modèles (Active Record Pattern)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Classe abstraite de base pour tous les modèles.
 * Implémente le pattern Active Record : chaque instance représente une ligne.
 * 
 * ACTIVE RECORD PATTERN :
 * -----------------------
 * 
 * L'Active Record est un pattern où :
 * - Chaque instance de Model = une ligne de la table
 * - Les méthodes CRUD sont sur l'instance ou la classe
 * - Pas besoin de Repository séparé (contrairement au Data Mapper)
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * 
 * // Créer un modèle
 * class User extends Model {
 *     protected static string $table = 'users';
 * }
 * 
 * // Créer
 * $user = new User();
 * $user->name = 'Ogan';
 * $user->email = 'ogan@example.com';
 * $user->save();
 * 
 * // Lire
 * $user = User::find(1);
 * $users = User::where('age', '>', 18)->get();
 * 
 * // Mettre à jour
 * $user->name = 'Ogan Updated';
 * $user->save();
 * 
 * // Supprimer
 * $user->delete();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database;

abstract class Model
{
    /**
     * @var string|null Nom de la table (auto-détecté depuis le nom de la classe si null)
     */
    protected static ?string $table = null;

    /**
     * @var array Attributs de l'entité (données de la ligne)
     */
    protected array $attributes = [];

    /**
     * @var bool Indique si l'entité existe déjà en DB (nouvelle vs. existante)
     */
    protected bool $exists = false;

    /**
     * @var string|null Nom de la colonne clé primaire (par défaut 'id')
     */
    protected static ?string $primaryKey = 'id';

    /**
     * @var array Attributs à cacher lors de la serialization (ex: password)
     */
    protected array $hidden = [];

    /**
     * @var array Attributs à inclure exclusivement lors de la serialization
     * Si non vide, seuls ces attributs seront inclus
     */
    protected array $visible = [];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array $attributes Attributs initiaux
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        // Hydrater les propriétés depuis les attributs si elles existent
        $this->hydrateFromAttributes();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HYDRATER LES PROPRIÉTÉS DEPUIS LES ATTRIBUTS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Si le modèle a des propriétés privées avec getters/setters,
     * on les hydrate automatiquement depuis $attributes.
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function hydrateFromAttributes(): void
    {
        foreach ($this->attributes as $key => $value) {
            // Convertir snake_case en camelCase pour les setters
            // created_at → setCreatedAt, user_id → setUserId
            $camelKey = str_replace('_', '', ucwords($key, '_'));
            $setter = 'set' . $camelKey;

            if (method_exists($this, $setter)) {
                // Gérer les dates
                if (in_array($key, ['created_at', 'updated_at']) && is_string($value)) {
                    try {
                        $value = new \DateTime($value);
                    } catch (\Exception $e) {
                        // Si la conversion échoue, garder la valeur originale
                    }
                }

                // Gérer les tableaux JSON : vérifier si la propriété attend un array
                $propertyName = lcfirst($camelKey);
                if (is_string($value) && property_exists($this, $propertyName)) {
                    $reflection = new \ReflectionProperty($this, $propertyName);
                    $type = $reflection->getType();
                    if ($type instanceof \ReflectionNamedType && $type->getName() === 'array') {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $value = $decoded;
                        }
                    }
                }

                $this->$setter($value);
            }
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SYNCHRONISER LES PROPRIÉTÉS VERS LES ATTRIBUTS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Avant de sauvegarder, synchroniser les propriétés vers $attributes.
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function syncAttributesFromProperties(): void
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            $name = $property->getName();
            $getter = 'get' . ucfirst($name);

            if (method_exists($this, $getter)) {
                $value = $this->$getter();

                // Convertir les DateTime en string pour la base de données
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                // Convertir les tableaux en JSON pour la base de données
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                // Convertir camelCase en snake_case pour la base de données
                $dbKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
                $this->attributes[$dbKey] = $value;
            }
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER UNE ENTITÉ PAR ID
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param int $id ID de l'entité
     * @return static|null Instance du modèle ou null si non trouvé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function find(int $id): ?static
    {
        // Utiliser static::query() pour permettre l'override par des traits (ex: SoftDeletes)
        $result = static::query()
            ->where(static::$primaryKey, '=', $id)
            ->first();

        if ($result === null) {
            return null;
        }

        $model = new static($result);
        $model->exists = true;
        // Réhydrater après avoir défini exists pour que les setters soient appelés
        $model->hydrateFromAttributes();
        return $model;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER TOUTES LES ENTITÉS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Tableau d'instances du modèle
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function all(): array
    {
        // Utiliser static::query() pour permettre l'override par des traits (ex: SoftDeletes)
        $results = static::query()->get();
        return static::hydrate($results);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COMPTER LE NOMBRE D'ENREGISTREMENTS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return int Nombre d'enregistrements
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function count(): int
    {
        $result = QueryBuilder::table(static::getTableName())
            ->select(['COUNT(*) as count'])
            ->first();

        return (int) ($result['count'] ?? 0);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE PREMIER RÉSULTAT D'UNE REQUÊTE (hydraté)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utilise le QueryBuilder pour trouver un résultat et l'hydrate
     * automatiquement en instance de Model.
     * 
     * @return static|null Instance du modèle ou null si non trouvé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function first(): ?static
    {
        $result = static::query()->first();

        if ($result === null) {
            return null;
        }

        $model = new static($result);
        $model->exists = true;
        // Réhydrater après avoir défini exists pour que les setters soient appelés
        $model->hydrateFromAttributes();
        return $model;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UN QUERY BUILDER POUR CE MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de chaîner des méthodes WHERE, ORDER BY, etc.
     * 
     * @return QueryBuilder
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function query(): QueryBuilder
    {
        return QueryBuilder::table(static::getTableName());
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE CONDITION WHERE (méthode statique)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $column Colonne
     * @param string $operator Opérateur
     * @param mixed $value Valeur
     * @return QueryBuilder
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function where(string $column, string $operator, mixed $value): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * PAGINATION DES RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne un Paginator avec les résultats hydrartés en instances du modèle.
     * 
     * @param int $perPage Nombre d'éléments par page
     * @param int|null $page Numéro de page (auto-détecté depuis $_GET si null)
     * @return \Ogan\Database\Pagination\Paginator
     * 
     * @example
     * $users = User::paginate(15);
     * foreach ($users as $user) { ... }
     * echo $users->links();
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function paginate(int $perPage = 15, ?int $page = null): \Ogan\Database\Pagination\Paginator
    {
        // Auto-détection du numéro de page depuis $_GET
        if ($page === null) {
            $page = (int) ($_GET['page'] ?? 1);
        }
        $page = max(1, $page);

        // Compte le total
        $query = static::query();
        $total = $query->count();

        // Calcule l'offset
        $offset = ($page - 1) * $perPage;

        // Récupère les résultats bruts (pas hydratés)
        $rawResults = static::query()
            ->limit($perPage)
            ->offset($offset)
            ->get();

        // Hydrate les résultats en instances du modèle
        $items = static::hydrate($rawResults);

        return new \Ogan\Database\Pagination\Paginator($items, $total, $perPage, $page);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HYDRATER DES RÉSULTATS EN INSTANCES DU MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Transforme un tableau de résultats SQL en instances du modèle.
     * 
     * @param array $results Résultats SQL
     * @return array Tableau d'instances du modèle
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected static function hydrate(array $results): array
    {
        $models = [];
        foreach ($results as $result) {
            $model = new static($result);
            $model->exists = true;
            // Réhydrater après avoir défini exists pour que les setters soient appelés
            $model->hydrateFromAttributes();
            $models[] = $model;
        }
        return $models;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SAUVEGARDER L'ENTITÉ (INSERT ou UPDATE)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Si l'entité existe déjà (exists = true), fait un UPDATE.
     * Sinon, fait un INSERT.
     * 
     * @return bool TRUE si succès, FALSE sinon
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function save(): bool
    {
        // Synchroniser les propriétés vers les attributs avant sauvegarde
        $this->syncAttributesFromProperties();

        // Gérer automatiquement created_at et updated_at
        $now = new \DateTime();

        if ($this->exists) {
            // Mise à jour : seulement updated_at
            if (method_exists($this, 'setUpdatedAt')) {
                $this->setUpdatedAt($now);
            }
            return $this->update();
        } else {
            // Insertion : created_at et updated_at
            if (method_exists($this, 'setCreatedAt')) {
                $this->setCreatedAt($now);
            }
            if (method_exists($this, 'setUpdatedAt')) {
                $this->setUpdatedAt($now);
            }
            // Re-synchroniser après avoir défini les dates
            $this->syncAttributesFromProperties();
            return $this->insert();
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * INSÉRER L'ENTITÉ EN BASE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool TRUE si succès
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function insert(): bool
    {
        // Remove 'exists' from attributes as it's not a DB column
        $data = $this->attributes;
        unset($data['exists']);
        
        $id = QueryBuilder::table(static::getTableName())->insert($data);

        if ($id > 0) {
            $this->attributes[static::$primaryKey] = $id;
            // Mettre à jour la propriété id si elle existe
            $setter = 'set' . ucfirst(static::$primaryKey);
            if (method_exists($this, $setter)) {
                $this->$setter($id);
            }
            $this->exists = true;
            return true;
        }

        return false;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * METTRE À JOUR L'ENTITÉ EN BASE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool TRUE si succès
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function update(): bool
    {
        $primaryKey = static::$primaryKey;
        $id = $this->attributes[$primaryKey] ?? null;

        if ($id === null) {
            return false;
        }

        // Mettre à jour updated_at si le setter existe
        if (method_exists($this, 'setUpdatedAt')) {
            $this->setUpdatedAt(new \DateTime());
            // Re-synchroniser après avoir défini updated_at
            $this->syncAttributesFromProperties();
        }

        // Exclure la clé primaire et 'exists' des données à mettre à jour
        $data = $this->attributes;
        unset($data[$primaryKey]);
        unset($data['exists']);

        $affected = QueryBuilder::table(static::getTableName())
            ->where($primaryKey, '=', $id)
            ->update($data);

        return $affected > 0;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUPPRIMER L'ENTITÉ DE LA BASE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool TRUE si succès
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function delete(): bool
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
     * ═══════════════════════════════════════════════════════════════════
     * MAGIC GETTER : Récupérer un attribut
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet d'accéder aux attributs comme des propriétés :
     * $user->name au lieu de $user->attributes['name']
     * 
     * @param string $name Nom de l'attribut
     * @return mixed Valeur de l'attribut ou null
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __get(string $name): mixed
    {
        // 1. Essayer le getter standard (getProperty)
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        // 2. Essayer le getter booléen (isProperty)
        $isMethod = 'is' . ucfirst($name);
        if (method_exists($this, $isMethod)) {
            return $this->$isMethod();
        }

        // 3. Essayer l'attribut exact
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        // 4. Essayer l'attribut en snake_case (createdAt -> created_at)
        $snakeName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        if (array_key_exists($snakeName, $this->attributes)) {
            return $this->attributes[$snakeName];
        }

        return null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * MAGIC SETTER : Définir un attribut
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de définir les attributs comme des propriétés :
     * $user->name = 'Ogan' au lieu de $user->attributes['name'] = 'Ogan'
     * 
     * @param string $name Nom de l'attribut
     * @param mixed $value Valeur à définir
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __set(string $name, mixed $value): void
    {
        // 'exists' is a Model property, not a DB attribute
        if ($name === 'exists') {
            $this->exists = (bool) $value;
            return;
        }
        $this->attributes[$name] = $value;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * MAGIC ISSUET : Vérifier si un attribut existe
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom de l'attribut
     * @return bool TRUE si l'attribut existe
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE NOM DE LA TABLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Si $table n'est pas défini, déduit automatiquement depuis le nom de la classe.
     * Exemple : User → users, PostCategory → post_categories
     * 
     * @return string Nom de la table
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function getTableName(): string
    {
        // Si la table est explicitement définie, l'utiliser
        if (static::$table !== null) {
            return static::$table;
        }

        // Sinon, déduire depuis le nom de la classe
        $className = static::class;
        $shortName = substr($className, strrpos($className, '\\') + 1);

        // Convertir PascalCase en snake_case (SINGULIER comme Symfony/Doctrine)
        // User → user, PostCategory → post_category
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        // Pas de pluriel : on garde le singulier comme Symfony/Doctrine
        return $tableName;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE NOM DE LA CLÉ PRIMAIRE
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function getPrimaryKeyName(): string
    {
        return static::$primaryKey ?? 'id';
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONS ORM (Style Symfony)
    // ─────────────────────────────────────────────────────────────

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UNE RELATION ONETOMANY
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Un modèle parent peut avoir plusieurs modèles enfants.
     * 
     * @param string $related Classe du modèle cible
     * @param string $foreignKey Clé étrangère dans la table cible
     * @param string $localKey Clé locale dans la table parent (défaut: 'id')
     * @return \Ogan\Database\Relations\OneToMany
     * 
     * Exemple :
     * // Dans User.php
     * public function getPosts(): \Ogan\Database\Relations\OneToMany
     * {
     *     return $this->oneToMany(Post::class, 'user_id');
     * }
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function oneToMany(string $related, string $foreignKey, string $localKey = 'id'): \Ogan\Database\Relations\OneToMany
    {
        return new \Ogan\Database\Relations\OneToMany($this, $related, $foreignKey, $localKey);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UNE RELATION MANYTOONE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Plusieurs modèles enfants appartiennent à un modèle parent.
     * 
     * @param string $related Classe du modèle cible
     * @param string $foreignKey Clé étrangère dans la table actuelle
     * @param string $localKey Clé locale dans la table cible (défaut: 'id')
     * @return \Ogan\Database\Relations\ManyToOne
     * 
     * Exemple :
     * // Dans Post.php
     * public function getUser(): \Ogan\Database\Relations\ManyToOne
     * {
     *     return $this->manyToOne(User::class, 'user_id');
     * }
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function manyToOne(string $related, string $foreignKey, string $localKey = 'id'): \Ogan\Database\Relations\ManyToOne
    {
        return new \Ogan\Database\Relations\ManyToOne($this, $related, $foreignKey, $localKey);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UNE RELATION ONETOONE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Un modèle parent a exactement un modèle enfant.
     * 
     * @param string $related Classe du modèle cible
     * @param string $foreignKey Clé étrangère dans la table cible
     * @param string $localKey Clé locale dans la table parent (défaut: 'id')
     * @return \Ogan\Database\Relations\OneToOne
     * 
     * Exemple :
     * // Dans User.php
     * public function getProfile(): \Ogan\Database\Relations\OneToOne
     * {
     *     return $this->oneToOne(Profile::class, 'user_id');
     * }
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function oneToOne(string $related, string $foreignKey, string $localKey = 'id'): \Ogan\Database\Relations\OneToOne
    {
        return new \Ogan\Database\Relations\OneToOne($this, $related, $foreignKey, $localKey);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UNE RELATION MANYTOMANY
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Plusieurs modèles sont liés à plusieurs autres modèles via une table pivot.
     * 
     * @param string $related Classe du modèle cible
     * @param string $pivotTable Nom de la table pivot
     * @param string $pivotForeignKey Clé étrangère vers le modèle parent dans la table pivot
     * @param string $pivotRelatedKey Clé étrangère vers le modèle cible dans la table pivot
     * @param string $localKey Clé locale dans la table parent (défaut: 'id')
     * @return \Ogan\Database\Relations\ManyToMany
     * 
     * Exemple :
     * // Dans User.php
     * public function getRoles(): \Ogan\Database\Relations\ManyToMany
     * {
     *     return $this->manyToMany(Role::class, 'user_role', 'user_id', 'role_id');
     * }
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function manyToMany(string $related, string $pivotTable, string $pivotForeignKey, string $pivotRelatedKey, string $localKey = 'id'): \Ogan\Database\Relations\ManyToMany
    {
        return new \Ogan\Database\Relations\ManyToMany($this, $related, $pivotTable, $pivotForeignKey, $pivotRelatedKey, $localKey);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SERIALIZATION (API JSON)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Convertit le modèle en tableau pour l'API
     * 
     * Respecte $hidden et $visible :
     * - Si $visible est défini, seuls ces attributs sont inclus
     * - Si $hidden est défini, ces attributs sont exclus
     * 
     * @param bool $withRelations Inclure les relations chargées
     * @return array
     */
    public function toArray(bool $withRelations = true): array
    {
        // Synchroniser les propriétés vers attributs
        $this->syncAttributesFromProperties();
        
        $result = $this->filterAttributes($this->attributes);
        
        // Ajouter les relations chargées si demandé
        if ($withRelations) {
            $result = $this->addLoadedRelations($result);
        }
        
        return $result;
    }

    /**
     * Convertit le modèle en JSON
     * 
     * @param int $options Options json_encode (JSON_PRETTY_PRINT, etc.)
     * @param bool $withRelations Inclure les relations chargées
     * @return string
     */
    public function toJson(int $options = 0, bool $withRelations = true): string
    {
        return json_encode($this->toArray($withRelations), $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Filtre les attributs selon $hidden et $visible
     */
    protected function filterAttributes(array $attributes): array
    {
        // Si $visible est défini, ne garder que ces attributs
        if (!empty($this->visible)) {
            $attributes = array_intersect_key($attributes, array_flip($this->visible));
        }
        
        // Supprimer les attributs cachés
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }
        
        return $attributes;
    }

    /**
     * Ajoute les relations chargées au tableau
     */
    protected function addLoadedRelations(array $result): array
    {
        // Parcourir les propriétés pour trouver les relations chargées
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            $value = $property->getValue($this);
            
            // Si c'est une relation (collection ou modèle), la serialiser
            if ($value instanceof Model) {
                $result[$name] = $value->toArray(false); // Éviter récursion infinie
            } elseif (is_array($value) && isset($value[0]) && $value[0] instanceof Model) {
                $result[$name] = array_map(fn($m) => $m->toArray(false), $value);
            }
        }
        
        return $result;
    }

    /**
     * Retourne tous les attributs bruts
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Cache temporairement des attributs pour cette instance
     */
    public function makeHidden(array|string $attributes): self
    {
        $this->hidden = array_merge($this->hidden, (array) $attributes);
        return $this;
    }

    /**
     * Rend des attributs visibles pour cette instance
     */
    public function makeVisible(array|string $attributes): self
    {
        $this->hidden = array_diff($this->hidden, (array) $attributes);
        $this->visible = array_merge($this->visible, (array) $attributes);
        return $this;
    }

    /**
     * Définit les attributs cachés
     */
    public function setHidden(array $hidden): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Définit les attributs visibles
     */
    public function setVisible(array $visible): self
    {
        $this->visible = $visible;
        return $this;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * ACTIVE RECORD vs DATA MAPPER
 * -----------------------------
 * 
 * ACTIVE RECORD (ce que nous implémentons) :
 * - Chaque instance = une ligne de la table
 * - Les méthodes CRUD sont sur l'instance
 * - Plus simple à comprendre
 * - Utilisé par Laravel (Eloquent), Ruby on Rails
 * 
 * DATA MAPPER :
 * - Séparation entre entité et persistance
 * - Repository séparé pour la persistance
 * - Plus flexible mais plus complexe
 * - Utilisé par Doctrine (Symfony), Hibernate (Java)
 * 
 * EXEMPLE ACTIVE RECORD :
 * 
 * $user = new User();
 * $user->name = 'Ogan';
 * $user->save(); // INSERT
 * 
 * $user->name = 'Ogan Updated';
 * $user->save(); // UPDATE
 * 
 * EXEMPLE DATA MAPPER :
 * 
 * $user = new User();
 * $user->name = 'Ogan';
 * $repository->save($user); // Repository gère INSERT/UPDATE
 * 
 * MAGIC METHODS
 * -------------
 * 
 * Les méthodes __get(), __set(), __isset() permettent d'utiliser
 * les attributs comme des propriétés :
 * 
 * $user->name = 'Ogan';        // Appelle __set()
 * echo $user->name;           // Appelle __get()
 * isset($user->name);          // Appelle __isset()
 * 
 * C'est plus élégant que :
 * $user->attributes['name'] = 'Ogan';
 * echo $user->attributes['name'];
 * 
 * HYDRATION
 * ---------
 * 
 * L'hydratation transforme les résultats SQL (tableaux) en objets :
 * 
 * // Résultat SQL
 * ['id' => 1, 'name' => 'Ogan', 'email' => 'ogan@example.com']
 * 
 * // Devient
 * User {
 *     attributes: [
 *         'id' => 1,
 *         'name' => 'Ogan',
 *         'email' => 'ogan@example.com'
 *     ],
 *     exists: true
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
