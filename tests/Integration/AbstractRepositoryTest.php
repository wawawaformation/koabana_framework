<?php

declare(strict_types=1);

namespace Tests\Integration;

use Koabana\Model\Entity\UserEntity;
use PHPUnit\Framework\TestCase;

/**
 * Opérations CRUD génériques, testées à travers UserRepository sur SQLite.
 *
 * @internal
 *
 * @coversNothing
 */
final class AbstractRepositoryTest extends TestCase
{
    private UserDatabase $db;

    protected function setUp(): void
    {
        $this->db = new UserDatabase();
    }

    public function testFindAllReturnsHydratedEntitiesOrderedById(): void
    {
        $this->db->addUser('ada@example.com');
        $this->db->addUser('bob@example.com');

        $users = $this->db->repository->findAll();

        self::assertCount(2, $users);
        self::assertContainsOnlyInstancesOf(UserEntity::class, $users);
        self::assertSame(['ada@example.com', 'bob@example.com'], array_map(static fn (UserEntity $u) => $u->getEmail(), $users));
    }

    public function testFindByIdHydratesTypes(): void
    {
        $id = $this->db->addUser(isActive: true, token: str_repeat('a', 32), tokenExpiresAt: new \DateTimeImmutable('2030-01-02 03:04:05'))->getId();

        $user = $this->db->repository->findById((int) $id);

        self::assertInstanceOf(UserEntity::class, $user);
        self::assertSame($id, $user->getId());
        self::assertTrue($user->isActive());
        self::assertEquals(new \DateTimeImmutable('2030-01-02 03:04:05'), $user->getTokenExpiresAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        self::assertNull($this->db->repository->findById(99));
    }

    public function testCreateAssignsTheIdToTheEntity(): void
    {
        $user = new UserEntity()->setFirstname('Ada')->setLastname('Lovelace')->setEmail('ada@example.com');

        $id = $this->db->repository->create($user);

        self::assertGreaterThan(0, $id);
        self::assertSame($id, $user->getId());
    }

    public function testCreatedEntityCanBeUpdatedDirectly(): void
    {
        // Créer puis modifier le même objet, sans le relire en base
        $user = new UserEntity()->setFirstname('Ada')->setLastname('Lovelace')->setEmail('ada@example.com');
        $this->db->repository->create($user);

        $user->setFirstname('Augusta');

        self::assertTrue($this->db->repository->update($user));
        self::assertSame('Augusta', $this->db->user('ada@example.com')?->getFirstname());
    }

    public function testUpdateSetsUpdatedAtAndKeepsCreatedAt(): void
    {
        $this->db->pdo->exec("INSERT INTO user (firstname, lastname, email, created_at) VALUES ('Ada', 'Lovelace', 'ada@example.com', '2020-01-01 10:00:00')");
        $user = $this->db->user('ada@example.com');
        self::assertNotNull($user);

        $before = new \DateTimeImmutable('-1 second');
        $this->db->repository->update($user->setFirstname('Augusta'));

        $reloaded = $this->db->user('ada@example.com');
        self::assertEquals(new \DateTimeImmutable('2020-01-01 10:00:00'), $reloaded?->getCreatedAt());
        self::assertGreaterThanOrEqual($before, $reloaded?->getUpdatedAt());
        self::assertSame($user->getUpdatedAt()?->format('Y-m-d H:i:s'), $reloaded?->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    public function testUpdateWithoutIdIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->db->repository->update(new UserEntity()->setFirstname('Ada')->setLastname('Lovelace')->setEmail('ada@example.com'));
    }

    public function testDeleteAndDeleteEntity(): void
    {
        $ada = $this->db->addUser('ada@example.com');
        $bob = $this->db->addUser('bob@example.com');

        self::assertTrue($this->db->repository->delete((int) $ada->getId()));
        self::assertFalse($this->db->repository->delete((int) $ada->getId()));
        self::assertTrue($this->db->repository->deleteEntity($bob));
        self::assertSame(0, $this->db->countUsers());
    }
}
