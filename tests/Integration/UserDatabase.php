<?php

declare(strict_types=1);

namespace Tests\Integration;

use Koabana\Database\BDDFactory;
use Koabana\Database\MyPDO;
use Koabana\Model\Entity\UserEntity;
use Koabana\Model\Repository\UserRepository;

/**
 * Base SQLite en mémoire avec la table `user`, pour tester le vrai UserRepository.
 *
 * Le schéma reprend database/schema.sql (MySQL), traduit en SQLite.
 */
final class UserDatabase
{
    public readonly MyPDO $pdo;
    public readonly UserRepository $repository;

    public function __construct()
    {
        $this->pdo = new MyPDO('sqlite::memory:');
        $this->pdo->exec(
            'CREATE TABLE user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                firstname TEXT NOT NULL,
                lastname TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NULL,
                is_active INTEGER NOT NULL DEFAULT 0,
                token TEXT NULL UNIQUE,
                token_expires_at TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NULL
            )',
        );

        // Fabrique qui renvoie toujours la connexion SQLite, sans lire DB_DSN
        $factory = new class($this->pdo) extends BDDFactory {
            public function __construct(MyPDO $pdo)
            {
                $this->connection = $pdo;
            }

            public function getConnection(): MyPDO
            {
                return $this->connection ?? throw new \LogicException('Connexion absente.');
            }
        };

        $this->repository = new UserRepository($factory);
    }

    /**
     * Crée un utilisateur et le renvoie relu depuis la base.
     */
    public function addUser(
        string $email = 'ada@example.com',
        ?string $password = null,
        bool $isActive = false,
        ?string $token = null,
        ?\DateTimeImmutable $tokenExpiresAt = null,
    ): UserEntity {
        $user = new UserEntity()
            ->setFirstname('Ada')
            ->setLastname('Lovelace')
            ->setEmail($email)
            ->setPasswordHash(null === $password ? null : password_hash($password, PASSWORD_DEFAULT))
            ->setIsActive($isActive)
            ->setToken($token)
            ->setTokenExpiresAt($tokenExpiresAt)
        ;
        $this->repository->create($user);

        return $this->user($email) ?? throw new \LogicException('Utilisateur non créé.');
    }

    public function user(string $email): ?UserEntity
    {
        return $this->repository->findByEmail($email);
    }

    public function countUsers(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();
    }
}
