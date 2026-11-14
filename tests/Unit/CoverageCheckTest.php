<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Teste bin/coverage-check.php en l'exécutant comme en CI (code de sortie + message).
 *
 * @internal
 *
 * @coversNothing
 */
final class CoverageCheckTest extends TestCase
{
    private string $clover;

    protected function setUp(): void
    {
        $this->clover = (string) tempnam(sys_get_temp_dir(), 'clover');
    }

    protected function tearDown(): void
    {
        @unlink($this->clover);
    }

    public function testPassesWhenCoverageReachesThreshold(): void
    {
        $this->writeClover(statements: 200, covered: 100); // 50 %

        [$exitCode, $output] = $this->runScript('50');

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('50.00 %', $output);
    }

    public function testFailsWhenCoverageIsBelowThreshold(): void
    {
        $this->writeClover(statements: 200, covered: 99); // 49,5 %

        [$exitCode, $output] = $this->runScript('50');

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('49.50 %', $output);
    }

    public function testFailsWhenReportIsMissing(): void
    {
        @unlink($this->clover);

        [$exitCode] = $this->runScript('50');

        self::assertSame(1, $exitCode);
    }

    public function testFailsWithoutThreshold(): void
    {
        $this->writeClover(statements: 10, covered: 10);

        [$exitCode] = $this->runScript('');

        self::assertSame(1, $exitCode);
    }

    private function writeClover(int $statements, int $covered): void
    {
        file_put_contents($this->clover, <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <coverage><project>
              <metrics statements="{$statements}" coveredstatements="{$covered}"/>
            </project></coverage>
            XML);
    }

    /**
     * Lance le script et renvoie [code de sortie, sortie standard + erreurs].
     *
     * @return array{int, string}
     */
    private function runScript(string $threshold): array
    {
        $script = \dirname(__DIR__, 2).'/bin/coverage-check.php';
        $command = \sprintf('php %s %s %s 2>&1', escapeshellarg($script), escapeshellarg($this->clover), $threshold);

        exec($command, $lines, $exitCode);

        return [$exitCode, implode("\n", $lines)];
    }
}
