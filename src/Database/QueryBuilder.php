<?php

declare(strict_types=1);

namespace Koabana\Database;

/**
 * Mini Query Builder simple et minimaliste.
 *
 * Permet construire des requêtes SELECT de façon fluide sans ORM.
 * Reste compatible avec les prepared statements PDO.
 *
 * Sécurité : les valeurs sont toujours liées (« ? »). Les noms de colonnes et les
 * opérateurs, eux, sont écrits dans le SQL : ils sont donc validés (format d'identifiant,
 * liste blanche d'opérateurs) et une valeur invalide lève une InvalidArgumentException.
 */
final class QueryBuilder
{
    /** Opérateurs autorisés dans where() */
    private const array OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];

    /** Nom de colonne, éventuellement préfixé par la table (ex. user.email) */
    private const string IDENTIFIER = '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/';

    private string $table;
    private BDDFactory $bddFactory;

    /** @var array<string> */
    private array $columns = [];

    /** @var array<string> fragments SQL des conditions, combinés par AND */
    private array $wheres = [];

    /** @var array<array{column: string, direction: string}> */
    private array $orders = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    /** @var array<mixed> valeurs liées, dans l'ordre exact des « ? » des conditions */
    private array $bindings = [];

    public function __construct(string $table, BDDFactory $bddFactory)
    {
        $this->table = $table;
        $this->bddFactory = $bddFactory;
    }

    /**
     * Sélectionne des colonnes spécifiques (par défaut: *)
     *
     * @param array<string>|string $columns
     */
    public function select(array|string $columns = '*'): self
    {
        $columns = \is_string($columns) ? [$columns] : $columns;

        foreach ($columns as $column) {
            if ('*' !== $column) {
                $this->assertIdentifier($column);
            }
        }

        $this->columns = $columns;

        return $this;
    }

    /**
     * Ajoute une condition WHERE
     *
     * @param string $column   Nom de la colonne
     * @param string $operator Opérateur (=, !=, <>, <, <=, >, >=, LIKE, NOT LIKE)
     * @param mixed  $value    Valeur à comparer (pour NULL : whereNull() / whereNotNull())
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->assertIdentifier($column);

        $operator = \strtoupper($operator);
        if (!\in_array($operator, self::OPERATORS, true)) {
            throw new \InvalidArgumentException('Invalid operator: '.$operator);
        }

        // « colonne = NULL » ne correspond jamais à rien en SQL
        if (null === $value) {
            throw new \InvalidArgumentException('NULL value in where(): use whereNull() or whereNotNull() instead.');
        }

        $this->wheres[] = $column.' '.$operator.' ?';
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Ajoute une condition WHERE avec IN
     *
     * @param string       $column
     * @param array<mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        $this->assertIdentifier($column);

        if (empty($values)) {
            return $this;
        }

        $placeholders = \array_map(fn () => '?', $values);
        $this->wheres[] = $column.' IN ('.\implode(', ', $placeholders).')';

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Ajoute une condition WHERE avec BETWEEN
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->assertIdentifier($column);

        $this->wheres[] = $column.' BETWEEN ? AND ?';
        $this->bindings[] = $min;
        $this->bindings[] = $max;

        return $this;
    }

    /**
     * Ajoute une condition WHERE colonne IS NULL
     */
    public function whereNull(string $column): self
    {
        $this->assertIdentifier($column);
        $this->wheres[] = $column.' IS NULL';

        return $this;
    }

    /**
     * Ajoute une condition WHERE colonne IS NOT NULL
     */
    public function whereNotNull(string $column): self
    {
        $this->assertIdentifier($column);
        $this->wheres[] = $column.' IS NOT NULL';

        return $this;
    }

    /**
     * Tri des résultats
     *
     * @param string $column    Colonne
     * @param string $direction ASC ou DESC
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->assertIdentifier($column);

        $direction = \strtoupper($direction);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            throw new \InvalidArgumentException('Invalid order direction: '.$direction);
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

        return $this;
    }

    /**
     * Offset (pagination)
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;

        return $this;
    }

    /**
     * Construit et exécute la requête SELECT, retourne les résultats
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        return $this->execute($this->toSql())->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compte les résultats
     */
    public function count(): int
    {
        $result = $this->execute($this->buildCountSql())->fetch(\PDO::FETCH_ASSOC);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Retourne la première ligne ou null
     *
     * @return null|array<string, mixed>
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * Retourne la requête SQL générée (utile pour debugging)
     */
    public function toRawSql(): string
    {
        return $this->toSql();
    }

    /**
     * Prépare la requête et lie les valeurs dans l'ordre des « ? »
     */
    private function execute(string $sql): \PDOStatement
    {
        $stmt = $this->bddFactory->getConnection()->prepare($sql);

        foreach ($this->bindings as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Refuse tout nom de colonne qui n'est pas un identifiant simple (protection contre l'injection SQL)
     */
    private function assertIdentifier(string $column): void
    {
        if (1 !== \preg_match(self::IDENTIFIER, $column)) {
            throw new \InvalidArgumentException('Invalid column name: '.$column);
        }
    }

    /**
     * Construit la requête SQL
     */
    private function toSql(): string
    {
        $columns = empty($this->columns) ? '*' : \implode(', ', $this->columns);
        $sql = 'SELECT '.$columns.' FROM '.$this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE '.\implode(' AND ', $this->wheres);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY '.$this->buildOrderClauses();
        }

        if (null !== $this->limitValue) {
            $sql .= ' LIMIT '.$this->limitValue;
        }

        if (null !== $this->offsetValue) {
            $sql .= ' OFFSET '.$this->offsetValue;
        }

        return $sql;
    }

    /**
     * Construit les clauses ORDER BY
     */
    private function buildOrderClauses(): string
    {
        return \implode(', ', \array_map(
            fn ($order) => $order['column'].' '.$order['direction'],
            $this->orders,
        ));
    }

    /**
     * Construit une requête COUNT
     */
    private function buildCountSql(): string
    {
        $sql = 'SELECT COUNT(*) as count FROM '.$this->table;

        if (!empty($this->wheres)) {
            $sql .= ' WHERE '.\implode(' AND ', $this->wheres);
        }

        return $sql;
    }
}
