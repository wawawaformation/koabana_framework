<<<<<<< HEAD
<?php declare(strict_types=1);
=======
<?php

declare(strict_types=1);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

namespace Koabana\Database;

/**
 * Mini Query Builder simple et minimaliste.
<<<<<<< HEAD
 * 
=======
 *
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
 * Permet construire des requêtes SELECT de façon fluide sans ORM.
 * Reste compatible avec les prepared statements PDO.
 */
final class QueryBuilder
{
    private string $table;
    private BDDFactory $bddFactory;
<<<<<<< HEAD
    /** @var array<string> */
    private array $columns = [];
    /** @var array<array{column: string, operator: string, value: mixed}> */
    private array $wheres = [];
=======

    /** @var array<string> */
    private array $columns = [];

    /** @var array<array{column: string, operator: string, value: mixed}> */
    private array $wheres = [];

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    /** @var array<array{column: string, direction: string}> */
    private array $orders = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
<<<<<<< HEAD
=======

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
    /** @var array<mixed> */
    private array $bindings = [];

    public function __construct(string $table, BDDFactory $bddFactory)
    {
        $this->table = $table;
        $this->bddFactory = $bddFactory;
    }

    /**
     * Sélectionne des colonnes spécifiques (par défaut: *)
<<<<<<< HEAD
=======
     *
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     * @param array<string>|string $columns
     */
    public function select(array|string $columns = '*'): self
    {
        if (\is_string($columns)) {
            $this->columns = [$columns];
        } else {
            $this->columns = $columns;
        }
<<<<<<< HEAD
=======

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        return $this;
    }

    /**
     * Ajoute une condition WHERE
<<<<<<< HEAD
     * @param string $column Nom de la colonne
     * @param string $operator Opérateur (=, <, >, <=, >=, !=, LIKE, etc.)
     * @param mixed $value Valeur à comparer
=======
     *
     * @param string $column   Nom de la colonne
     * @param string $operator Opérateur (=, <, >, <=, >=, !=, LIKE, etc.)
     * @param mixed  $value    Valeur à comparer
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     */
    public function where(string $column, string $operator = '=', mixed $value = null): self
    {
        // Gestion du cas where('column', value) sans opérateur
<<<<<<< HEAD
        if ($value === null && $operator !== '=') {
=======
        if (null === $value && '=' !== $operator) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Ajoute une condition WHERE avec IN
<<<<<<< HEAD
     * @param string $column
=======
     *
     * @param string       $column
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     * @param array<mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = \array_map(fn () => '?', $values);
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IN',
<<<<<<< HEAD
            'value' => '(' . \implode(', ', $placeholders) . ')',
=======
            'value' => '('.\implode(', ', $placeholders).')',
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Ajoute une condition WHERE avec BETWEEN
<<<<<<< HEAD
     * @param string $column
     * @param mixed $min
     * @param mixed $max
=======
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => ['min' => $min, 'max' => $max],
        ];

        return $this;
    }

    /**
     * Tri des résultats
<<<<<<< HEAD
     * @param string $column Colonne
=======
     *
     * @param string $column    Colonne
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     * @param string $direction ASC ou DESC
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = \strtoupper($direction);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
<<<<<<< HEAD
            throw new \InvalidArgumentException('Invalid order direction: ' . $direction);
=======
            throw new \InvalidArgumentException('Invalid order direction: '.$direction);
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Limite le nombre de résultats
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
<<<<<<< HEAD
=======

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        return $this;
    }

    /**
     * Offset (pagination)
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
<<<<<<< HEAD
=======

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        return $this;
    }

    /**
     * Construit et exécute la requête SELECT, retourne les résultats
<<<<<<< HEAD
=======
     *
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $sql = $this->toSql();
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->prepare($sql);

        // Binding des paramètres
        $index = 1;
        foreach ($this->wheres as $where) {
<<<<<<< HEAD
            if ($where['operator'] === 'IN' || $where['operator'] === 'BETWEEN') {
=======
            if ('IN' === $where['operator'] || 'BETWEEN' === $where['operator']) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                continue;
            }
            $stmt->bindValue($index++, $where['value']);
        }

        // Binding des valeurs from whereIn/whereBetween
        foreach ($this->bindings as $binding) {
            $stmt->bindValue($index++, $binding);
        }

        $stmt->execute();
<<<<<<< HEAD
=======

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compte les résultats
     */
    public function count(): int
    {
        $sql = $this->buildCountSql();
        $pdo = $this->bddFactory->getConnection();
        $stmt = $pdo->prepare($sql);

        $index = 1;
        foreach ($this->wheres as $where) {
<<<<<<< HEAD
            if ($where['operator'] === 'IN' || $where['operator'] === 'BETWEEN') {
=======
            if ('IN' === $where['operator'] || 'BETWEEN' === $where['operator']) {
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
                continue;
            }
            $stmt->bindValue($index++, $where['value']);
        }

        foreach ($this->bindings as $binding) {
            $stmt->bindValue($index++, $binding);
        }

        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Retourne la première ligne ou null
<<<<<<< HEAD
     * @return array<string, mixed>|null
=======
     *
     * @return null|array<string, mixed>
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        $results = $this->get();
<<<<<<< HEAD
=======

>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        return $results[0] ?? null;
    }

    /**
<<<<<<< HEAD
=======
     * Retourne la requête SQL générée (utile pour debugging)
     */
    public function toRawSql(): string
    {
        return $this->toSql();
    }

    /**
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
     * Construit la requête SQL
     */
    private function toSql(): string
    {
        $columns = empty($this->columns) ? '*' : \implode(', ', $this->columns);
<<<<<<< HEAD
        $sql = 'SELECT ' . $columns . ' FROM ' . $this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClauses();
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . $this->buildOrderClauses();
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
=======
        $sql = 'SELECT '.$columns.' FROM '.$this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE '.$this->buildWhereClauses();
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY '.$this->buildOrderClauses();
        }

        if (null !== $this->limitValue) {
            $sql .= ' LIMIT '.$this->limitValue;
        }

        if (null !== $this->offsetValue) {
            $sql .= ' OFFSET '.$this->offsetValue;
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        }

        return $sql;
    }

    /**
     * Construit les clauses WHERE
     */
    private function buildWhereClauses(): string
    {
        $clauses = [];

        foreach ($this->wheres as $where) {
            $column = $where['column'];
            $operator = $where['operator'];

<<<<<<< HEAD
            if ($operator === 'IN') {
                $clauses[] = $column . ' ' . $where['value'];
            } elseif ($operator === 'BETWEEN') {
                $clauses[] = $column . ' BETWEEN ? AND ?';
                // Les valeurs seront bindées séparément
            } else {
                $clauses[] = $column . ' ' . $operator . ' ?';
=======
            if ('IN' === $operator) {
                $clauses[] = $column.' '.$where['value'];
            } elseif ('BETWEEN' === $operator) {
                $clauses[] = $column.' BETWEEN ? AND ?';
            // Les valeurs seront bindées séparément
            } else {
                $clauses[] = $column.' '.$operator.' ?';
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
            }
        }

        return \implode(' AND ', $clauses);
    }

    /**
     * Construit les clauses ORDER BY
     */
    private function buildOrderClauses(): string
    {
        return \implode(', ', \array_map(
<<<<<<< HEAD
            fn ($order) => $order['column'] . ' ' . $order['direction'],
            $this->orders
=======
            fn ($order) => $order['column'].' '.$order['direction'],
            $this->orders,
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        ));
    }

    /**
     * Construit une requête COUNT
     */
    private function buildCountSql(): string
    {
<<<<<<< HEAD
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClauses();
=======
        $sql = 'SELECT COUNT(*) as count FROM '.$this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE '.$this->buildWhereClauses();
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        }

        return $sql;
    }
<<<<<<< HEAD

    /**
     * Retourne la requête SQL générée (utile pour debugging)
     */
    public function toRawSql(): string
    {
        return $this->toSql();
    }
=======
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
}
