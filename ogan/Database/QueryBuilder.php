<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔨 QUERYBUILDER - Constructeur de Requêtes SQL
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Construit des requêtes SQL de manière orientée objet et sécurisée.
 * Utilise des requêtes préparées pour éviter les injections SQL.
 * 
 * POURQUOI UN QUERY BUILDER ?
 * ----------------------------
 * 
 * 1. SÉCURITÉ :
 *    Utilise des requêtes préparées automatiquement.
 *    Protection contre les injections SQL.
 * 
 * 2. LISIBILITÉ :
 *    Code plus lisible que du SQL brut.
 *    Méthodes chaînables (fluent interface).
 * 
 * 3. FLEXIBILITÉ :
 *    Construit des requêtes complexes facilement.
 *    Supporte WHERE, JOIN, ORDER BY, GROUP BY, etc.
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * 
 * // SELECT
 * $users = QueryBuilder::table('users')
 *     ->select(['id', 'name', 'email'])
 *     ->where('age', '>', 18)
 *     ->orderBy('name', 'ASC')
 *     ->limit(10)
 *     ->get();
 * 
 * // INSERT
 * QueryBuilder::table('users')
 *     ->insert(['name' => 'Ogan', 'email' => 'ogan@example.com']);
 * 
 * // UPDATE
 * QueryBuilder::table('users')
 *     ->where('id', '=', 1)
 *     ->update(['name' => 'Ogan Updated']);
 * 
 * // DELETE
 * QueryBuilder::table('users')
 *     ->where('id', '=', 1)
 *     ->delete();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database;

use PDO;

class QueryBuilder
{
    /**
     * @var PDO Connexion PDO
     */
    private PDO $pdo;

    /**
     * @var string Table principale
     */
    private string $table;

    /**
     * @var array Colonnes à sélectionner
     */
    private array $select = ['*'];

    /**
     * @var array Conditions WHERE
     */
    private array $wheres = [];

    /**
     * @var array Jointures
     */
    private array $joins = [];

    /**
     * @var array ORDER BY
     */
    private array $orderBy = [];

    /**
     * @var array GROUP BY
     */
    private array $groupBy = [];

    /**
     * @var int|null LIMIT
     */
    private ?int $limit = null;

    /**
     * @var int|null OFFSET
     */
    private ?int $offset = null;

    /**
     * @var array Paramètres pour les requêtes préparées
     */
    private array $params = [];

    /**
     * @var int|null TTL du cache en secondes (null = pas de cache)
     */
    private ?int $cacheTtl = null;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param PDO $pdo Connexion PDO
     * @param string $table Table principale
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FACTORY : Créer un nouveau QueryBuilder
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $table Nom de la table
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function table(string $table): self
    {
        return new self(Database::getConnection(), $table);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SÉLECTIONNER DES COLONNES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array|string $columns Colonnes à sélectionner
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function select(array|string $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->select = $columns;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE CONDITION WHERE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $column Colonne
     * @param string $operator Opérateur (=, >, <, LIKE, etc.)
     * @param mixed $value Valeur
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE CONDITION OR WHERE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $column Colonne
     * @param string $operator Opérateur
     * @param mixed $value Valeur
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE JOINTURE INNER JOIN
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $table Table à joindre
     * @param string $first Colonne de la table principale
     * @param string $operator Opérateur
     * @param string $second Colonne de la table jointe
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE JOINTURE LEFT JOIN
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $table Table à joindre
     * @param string $first Colonne de la table principale
     * @param string $operator Opérateur
     * @param string $second Colonne de la table jointe
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TRIER LES RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $column Colonne
     * @param string $direction ASC ou DESC
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GROUPER LES RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $column Colonne
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function groupBy(string $column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * LIMITER LE NOMBRE DE RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param int $limit Nombre maximum de résultats
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉCALER LES RÉSULTATS (PAGINATION)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param int $offset Nombre de résultats à sauter
     * @return self
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ACTIVER LE CACHE POUR CETTE REQUÊTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param int $ttl Durée du cache en secondes
     * @return self
     * 
     * @example
     * // Cache la requête pendant 5 minutes
     * $users = QueryBuilder::table('users')
     *     ->cache(300)
     *     ->where('active', '=', true)
     *     ->get();
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function cache(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXÉCUTER UNE REQUÊTE SELECT ET RÉCUPÉRER TOUS LES RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Tableau de résultats
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function get(): array
    {
        $sql = $this->buildSelect();
        
        // Si le cache est activé, utiliser le pattern cache-aside
        if ($this->cacheTtl !== null && function_exists('cache')) {
            $cacheKey = $this->generateCacheKey($sql);
            
            return cache()->remember($cacheKey, $this->cacheTtl, function() use ($sql) {
                return $this->executeSelect($sql);
            });
        }
        
        return $this->executeSelect($sql);
    }

    /**
     * Exécute la requête SELECT (utilisé par get() et le cache)
     */
    private function executeSelect(string $sql): array
    {
        $stmt = $this->pdo->prepare($sql);
        
        $start = microtime(true);
        $stmt->execute($this->params);
        $time = microtime(true) - $start;
        
        // Logger la requête pour la Debug Bar
        if (class_exists(\Ogan\Debug\DebugBar::class)) {
            \Ogan\Debug\DebugBar::addQuery($sql, $time, $this->params);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Génère une clé de cache unique pour la requête
     */
    private function generateCacheKey(string $sql): string
    {
        return 'query.' . md5($sql . serialize($this->params));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXÉCUTER UNE REQUÊTE SELECT ET RÉCUPÉRER LE PREMIER RÉSULTAT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array|null Premier résultat ou null
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE PREMIER RÉSULTAT ET L'HYDRATER EN INSTANCE DE MODEL
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utile pour les requêtes depuis Model::where() qui doivent retourner
     * une instance de Model plutôt qu'un tableau.
     * 
     * @param string $modelClass Classe du modèle à instancier
     * @return object|null Instance du modèle ou null
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function firstModel(string $modelClass): ?object
    {
        $result = $this->first();

        if ($result === null) {
            return null;
        }

        $model = new $modelClass($result);
        if (method_exists($model, 'setExists')) {
            $model->setExists(true);
        } elseif (property_exists($model, 'exists')) {
            $model->exists = true;
        }

        return $model;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COMPTER LE NOMBRE DE RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return int Nombre de résultats
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function count(): int
    {
        $sql = $this->buildSelect('COUNT(*) as count');
        $stmt = $this->pdo->prepare($sql);
        
        $start = microtime(true);
        $stmt->execute($this->params);
        $time = microtime(true) - $start;
        
        if (class_exists(\Ogan\Debug\DebugBar::class)) {
            \Ogan\Debug\DebugBar::addQuery($sql, $time, $this->params);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * INSÉRER DES DONNÉES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array $data Données à insérer
     * @return int ID de la ligne insérée
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        
        $start = microtime(true);
        $stmt->execute($data);
        $time = microtime(true) - $start;
        
        if (class_exists(\Ogan\Debug\DebugBar::class)) {
            \Ogan\Debug\DebugBar::addQuery($sql, $time, $data);
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * METTRE À JOUR DES DONNÉES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array $data Données à mettre à jour
     * @return int Nombre de lignes affectées
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function update(array $data): int
    {
        $set = [];
        $params = [];

        foreach ($data as $column => $value) {
            $set[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        $sql .= $this->buildWhere();

        // Fusionner les paramètres WHERE avec les paramètres UPDATE
        $params = array_merge($params, $this->params);

        $stmt = $this->pdo->prepare($sql);
        
        $start = microtime(true);
        $stmt->execute($params);
        $time = microtime(true) - $start;
        
        if (class_exists(\Ogan\Debug\DebugBar::class)) {
            \Ogan\Debug\DebugBar::addQuery($sql, $time, $params);
        }

        return $stmt->rowCount();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUPPRIMER DES DONNÉES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return int Nombre de lignes affectées
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->buildWhere();

        $stmt = $this->pdo->prepare($sql);
        
        $start = microtime(true);
        $stmt->execute($this->params);
        $time = microtime(true) - $start;
        
        if (class_exists(\Ogan\Debug\DebugBar::class)) {
            \Ogan\Debug\DebugBar::addQuery($sql, $time, $this->params);
        }

        return $stmt->rowCount();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUIRE LA CLAUSE SELECT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string|null $selectOverride Override pour les colonnes (pour COUNT)
     * @return string SQL généré
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function buildSelect(?string $selectOverride = null): string
    {
        $select = $selectOverride ?? implode(', ', $this->select);
        $sql = "SELECT {$select} FROM {$this->table}";

        // JOINs
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // WHERE
        $sql .= $this->buildWhere();

        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $orderParts = array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->orderBy);
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUIRE LA CLAUSE WHERE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return string Clause WHERE générée
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function buildWhere(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $this->params = [];
        $conditions = [];

        foreach ($this->wheres as $index => $where) {
            $paramName = "param_{$index}";
            $conditions[] = ($index > 0 ? " {$where['type']} " : '') . "{$where['column']} {$where['operator']} :{$paramName}";
            $this->params[$paramName] = $where['value'];
        }

        return " WHERE " . implode('', $conditions);
    }


    /**
     * ═══════════════════════════════════════════════════════════════════
     * PAGINATION DES RÉSULTATS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne un objet Paginator contenant les résultats de la page
     * courante et les métadonnées de pagination.
     * 
     * @param int $perPage Nombre d'éléments par page
     * @param int|null $page Numéro de page (auto-détecté depuis $_GET si null)
     * @return \Ogan\Database\Pagination\Paginator
     * 
     * @example
     * $users = QueryBuilder::table('users')
     *     ->where('active', '=', true)
     *     ->paginate(15);
     * 
     * foreach ($users as $user) { ... }
     * echo $users->links();
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function paginate(int $perPage = 15, ?int $page = null): \Ogan\Database\Pagination\Paginator
    {
        // Auto-détection du numéro de page depuis $_GET
        if ($page === null) {
            $page = (int) ($_GET['page'] ?? 1);
        }
        $page = max(1, $page);

        // Compte le total AVANT d'appliquer limit/offset
        $total = $this->count();

        // Calcule l'offset
        $offset = ($page - 1) * $perPage;

        // Applique la pagination et récupère les résultats
        $this->limit($perPage);
        $this->offset($offset);
        $items = $this->get();

        return new \Ogan\Database\Pagination\Paginator($items, $total, $perPage, $page);
    }
}
