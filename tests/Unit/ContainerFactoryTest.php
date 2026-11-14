<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Bootstrap\ContainerFactory;
use Koabana\View\PhpTemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContainerFactoryTest extends TestCase
{
    private const string DEFINITIONS = __DIR__.'/../../config/containers.php';

    /** @var list<string> fichiers temporaires à supprimer */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->tempFiles);
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContainerFactory::create('/inexistant/containers.php');
    }

    public function testFileMustReturnAnArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('doit retourner un tableau');

        ContainerFactory::create($this->tempFile('<?php return "pas un tableau";'));
    }

    public function testProjectDefinitionsBuildServices(): void
    {
        $container = ContainerFactory::create(self::DEFINITIONS);

        self::assertInstanceOf(PhpTemplateRenderer::class, $container->get(PhpTemplateRenderer::class));
        self::assertSame(\dirname(__DIR__, 2).'/templates', $container->get('paths.templates'));
    }

    public function testOverridesReplaceDefinitions(): void
    {
        $logger = new NullLogger();

        $container = ContainerFactory::create(self::DEFINITIONS, [LoggerInterface::class => $logger]);

        self::assertSame($logger, $container->get(LoggerInterface::class));
    }

    private function tempFile(string $content): string
    {
        $file = sys_get_temp_dir().'/koabana-container-'.bin2hex(random_bytes(4)).'.php';
        file_put_contents($file, $content);
        $this->tempFiles[] = $file;

        return $file;
    }
}
