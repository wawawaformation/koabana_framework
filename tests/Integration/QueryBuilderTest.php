<?php

declare(strict_types=1);

namespace Tests\Integration;

use Koabana\Database\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Construction et exécution de requêtes SELECT, sur une vraie base SQLite.
 *
 * @internal
 *
 * @coversNothing
 */
final class QueryBuilderTest extends TestCase
{
    private UserDatabase $db;

    protected function setUp(): void
    {
        $this->db = new UserDatabase();
        // ids 1 à 4, insérés dans cet ordre
        $this->db->addUser('ada@example.com', isActive: true);
        $this->db->addUser('bob@example.com', isActive: false);
        $this->db->addUser('cy@example.com', isActive: true);
        $this->db->addUser('dee@example.com', isActive: false);
    }

    // --- Génération SQL ---

    public function testRawSqlCombinesAllClauses(): void
    {
        $sql = $this->query()
            ->select(['id', 'email'])
            ->where('is_active', '=', 1)
            ->whereIn('id', [1, 2])
            ->whereBetween('id', 1, 3)
            ->orderBy('email', 'desc')
            ->limit(10)
            ->offset(5)
            ->toRawSql()
        ;

        self::assertSame(
            'SELECT id, email FROM user WHERE is_active = ? AND id IN (?, ?) AND id BETWEEN ? AND ? ORDER BY email DESC LIMIT 10 OFFSET 5',
            $sql,
        );
    }

    public function testInvalidOrderDirectionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->query()->orderBy('email', 'sideways');
    }

    // --- Exécution ---

    public function testGetReturnsAllRowsByDefault(): void
    {
        self::assertCount(4, $this->query()->get());
    }

    public function testSelectRestrictsColumns(): void
    {
        self::assertSame([['email' => 'ada@example.com']], $this->query()->select('email')->where('id', '=', 1)->get());
    }

    public function testWhereWithOperator(): void
    {
        self::assertSame(['cy@example.com', 'dee@example.com'], $this->emails($this->query()->where('id', '>', 2)));
    }

    public function testWhereRequiresAnExplicitOperator(): void
    {
        // Plus de raccourci where(colonne, valeur) : where('x', 'LIKE') était ambigu
        $this->expectException(\ArgumentCountError::class);

        $this->query()->where('email', 'bob@example.com'); // @phpstan-ignore arguments.count
    }

    public function testOperatorsAreCaseInsensitive(): void
    {
        self::assertSame(['bob@example.com'], $this->emails($this->query()->where('email', 'like', 'b%')));
        self::assertSame(['ada@example.com', 'cy@example.com', 'dee@example.com'], $this->emails($this->query()->where('email', 'not like', 'b%')));
    }

    // --- NULL ---

    public function testWhereNullAndWhereNotNull(): void
    {
        $this->db->pdo->exec("UPDATE user SET token = 'abc' WHERE id = 2");

        self::assertSame(['bob@example.com'], $this->emails($this->query()->whereNotNull('token')));
        self::assertSame(['ada@example.com', 'cy@example.com', 'dee@example.com'], $this->emails($this->query()->whereNull('token')));
        self::assertSame('SELECT * FROM user WHERE token IS NULL AND id IS NOT NULL', $this->query()->whereNull('token')->whereNotNull('id')->toRawSql());
    }

    public function testWhereWithNullValueIsRejected(): void
    {
        // « colonne = NULL » ne correspond jamais à rien en SQL
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('whereNull');

        $this->query()->where('token', '=', null);
    }

    // --- Protection contre l'injection SQL ---

    public function testUnknownOperatorIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->query()->where('id', '= 1 OR 1 =', 1);
    }

    /**
     * @param callable(QueryBuilder): mixed $call
     */
    #[DataProvider('injectedColumns')]
    public function testInvalidColumnNameIsRejected(callable $call): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $call($this->query());
    }

    /**
     * @return iterable<string, array{callable(QueryBuilder): mixed}>
     */
    public static function injectedColumns(): iterable
    {
        $evil = 'id; DROP TABLE user --';

        yield 'select' => [static fn (QueryBuilder $q) => $q->select(['id', $evil])];

        yield 'where' => [static fn (QueryBuilder $q) => $q->where($evil, '=', 1)];

        yield 'whereIn' => [static fn (QueryBuilder $q) => $q->whereIn($evil, [1])];

        yield 'whereBetween' => [static fn (QueryBuilder $q) => $q->whereBetween($evil, 1, 2)];

        yield 'whereNull' => [static fn (QueryBuilder $q) => $q->whereNull($evil)];

        yield 'orderBy' => [static fn (QueryBuilder $q) => $q->orderBy('(CASE WHEN 1 THEN id END)')];
    }

    public function testQualifiedColumnsAndStarAreAccepted(): void
    {
        $sql = $this->query()->select(['user.id', 'user.email'])->where('user.id', '=', 1)->toRawSql();

        self::assertSame('SELECT user.id, user.email FROM user WHERE user.id = ?', $sql);
        self::assertSame('SELECT * FROM user', $this->query()->select('*')->toRawSql());
    }

    public function testSeveralWheresAreCombinedWithAnd(): void
    {
        $query = $this->query()->where('is_active', '=', 1)->where('id', '>', 1);

        self::assertSame(['cy@example.com'], $this->emails($query));
    }

    public function testWhereIn(): void
    {
        self::assertSame(['ada@example.com', 'dee@example.com'], $this->emails($this->query()->whereIn('id', [1, 4])));
    }

    public function testEmptyWhereInIsIgnored(): void
    {
        self::assertCount(4, $this->query()->whereIn('id', [])->get());
    }

    public function testWhereBetween(): void
    {
        self::assertSame(['bob@example.com', 'cy@example.com'], $this->emails($this->query()->whereBetween('id', 2, 3)));
    }

    public function testValuesAreBoundInTheOrderOfTheConditions(): void
    {
        // whereIn avant where : chaque valeur doit aller à son « ? »
        $query = $this->query()->whereIn('id', [1, 2, 3])->where('is_active', '=', 0);

        self::assertSame(['bob@example.com'], $this->emails($query));
    }

    public function testOrderByLimitAndOffset(): void
    {
        $query = $this->query()->orderBy('id', 'desc')->limit(2)->offset(1);

        self::assertSame(['cy@example.com', 'bob@example.com'], $this->emails($query));
    }

    public function testCountAppliesConditions(): void
    {
        self::assertSame(4, $this->query()->count());
        self::assertSame(2, $this->query()->where('is_active', '=', 1)->count());
        self::assertSame(2, $this->query()->whereIn('id', [1, 2])->count());
        self::assertSame(3, $this->query()->whereBetween('id', 2, 4)->count());
    }

    public function testFirstReturnsTheFirstRowOrNull(): void
    {
        self::assertSame('ada@example.com', $this->query()->orderBy('id')->first()['email'] ?? null);
        self::assertNull($this->query()->where('id', '>', 99)->first());
    }

    private function query(): QueryBuilder
    {
        // Passe par le dépôt, comme le code applicatif
        return $this->db->repository->query();
    }

    /**
     * @return list<string>
     */
    private function emails(QueryBuilder $query): array
    {
        return array_column($query->select('email')->get(), 'email');
    }
}
