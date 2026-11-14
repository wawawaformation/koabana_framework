<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Database\BDDFactory;
use PHPUnit\Framework\TestCase;
use Tests\Support\WithEnv;

/**
 * Connexion PDO depuis DB_DSN, DB_USER et DB_PASSWORD (et réglages de MyPDO).
 *
 * @internal
 *
 * @coversNothing
 */
final class BDDFactoryTest extends TestCase
{
    use WithEnv;

    public function testMissingDsnIsRejected(): void
    {
        $this->setEnv('DB_DSN', null);

        $this->expectException(\RuntimeException::class);

        new BDDFactory()->getConnection();
    }

    public function testConnectionIsCreatedOnceAndConfigured(): void
    {
        $this->setEnv('DB_DSN', 'sqlite::memory:');
        $factory = new BDDFactory();

        $pdo = $factory->getConnection();

        self::assertSame($pdo, $factory->getConnection());
        self::assertSame(\PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(\PDO::ATTR_ERRMODE));
        self::assertSame(\PDO::FETCH_ASSOC, $pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    public function testConnectionFailureIsReportedClearly(): void
    {
        $this->setEnv('DB_DSN', 'sqlite:/dossier/inexistant/base.sqlite');

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('Échec de la connexion à la base de données');

        new BDDFactory()->getConnection();
    }
}
