<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Log\LoggerFactory;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class LoggerFactoryTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir().'/koabana-logger-'.bin2hex(random_bytes(4)).'.log';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testLoggerWritesNamedEntriesToTheFile(): void
    {
        new LoggerFactory('koabana', $this->file)->create()->info('Bonjour');

        self::assertStringContainsString('koabana.INFO: Bonjour', (string) file_get_contents($this->file));
    }

    public function testEntriesBelowTheLevelAreIgnored(): void
    {
        $logger = new LoggerFactory('koabana', $this->file, Level::Warning)->create();

        $logger->info('Ignoré');
        $logger->error('Gardé');

        $content = (string) file_get_contents($this->file);
        self::assertStringNotContainsString('Ignoré', $content);
        self::assertStringContainsString('koabana.ERROR: Gardé', $content);
    }
}
