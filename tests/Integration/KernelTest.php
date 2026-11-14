<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Bootstrap\ContainerFactory;
use Koabana\Bootstrap\RouterFactory;
use Koabana\Http\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use Tests\Support\WithEnv;

/**
 * L'application complète (conteneur, routes, middlewares) traite une requête de bout en bout.
 * Processus séparés : SessionMiddleware démarre une vraie session PHP.
 *
 * @internal
 *
 * @coversNothing
 */
#[RunTestsInSeparateProcesses]
final class KernelTest extends TestCase
{
    use WithEnv;

    private const string ROOT = __DIR__.'/../..';

    /** @var list<array{level: mixed, message: string}> */
    public array $logs = [];

    protected function setUp(): void
    {
        $this->setEnv('APP_ENV', 'dev');
    }

    public function testHomePageIsServedWithSecurityHeaders(): void
    {
        $response = $this->handle('GET', '/');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<h1>Bienvenue sur Koabana Framework</h1>', (string) $response->getBody());
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
    }

    public function testUnknownRouteIsA404(): void
    {
        self::assertSame(404, $this->handle('GET', '/inexistant')->getStatusCode());
    }

    public function testTrailingSlashIsRedirected(): void
    {
        $response = $this->handle('GET', '/connexion/');

        self::assertSame(308, $response->getStatusCode());
        self::assertStringEndsWith('/connexion', $response->getHeaderLine('Location'));
    }

    public function testPostWithoutCsrfTokenIsRefused(): void
    {
        self::assertSame(403, $this->handle('POST', '/connexion')->getStatusCode());
    }

    /**
     * Toutes les réponses, y compris les erreurs et redirections, doivent porter les en-têtes de sécurité.
     */
    #[DataProvider('nonSuccessResponses')]
    public function testErrorAndRedirectResponsesHaveSecurityHeaders(string $method, string $path): void
    {
        $response = $this->handle($method, $path);

        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertNotSame('', $response->getHeaderLine('Content-Security-Policy'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nonSuccessResponses(): iterable
    {
        yield '404' => ['GET', '/inexistant'];

        yield '308 (barre finale)' => ['GET', '/connexion/'];

        yield '403 (CSRF)' => ['POST', '/connexion'];
    }

    public function testCrashShowsDetailsOutsideProduction(): void
    {
        $response = $this->handleWithCrashingRoute();

        // Page Whoops en HTML (son contenu reste vide en ligne de commande, Whoops ne s'affiche que sous un serveur web)
        self::assertSame(500, $response->getStatusCode());
        self::assertStringStartsWith('text/html', $response->getHeaderLine('Content-Type'));
        self::assertSame('Unhandled exception', $this->logs[0]['message'] ?? null);
    }

    public function testCrashHidesDetailsInProduction(): void
    {
        $this->setEnv('APP_ENV', 'prod');

        $response = $this->handleWithCrashingRoute();

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Erreur interne du serveur', (string) $response->getBody());
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
    }

    private function handle(string $method, string $path, string $routesFile = self::ROOT.'/config/routes.php'): ResponseInterface
    {
        $container = ContainerFactory::create(self::ROOT.'/config/containers.php');
        $kernel = new Kernel(RouterFactory::create($container, $routesFile), $this->logger());

        return $kernel->handle(new ServerRequest($method, 'http://localhost'.$path));
    }

    private function handleWithCrashingRoute(): ResponseInterface
    {
        $routesFile = sys_get_temp_dir().'/koabana-crash-'.bin2hex(random_bytes(4)).'.php';
        file_put_contents($routesFile, "<?php return [['GET', '/crash', fn () => throw new RuntimeException('Boum')]];");

        try {
            return $this->handle('GET', '/crash', $routesFile);
        } finally {
            unlink($routesFile);
        }
    }

    /**
     * Logger qui garde les entrées en mémoire (le vrai écrirait dans var/log).
     */
    private function logger(): AbstractLogger
    {
        return new class($this) extends AbstractLogger {
            public function __construct(private readonly KernelTest $test) {}

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->test->logs[] = ['level' => $level, 'message' => (string) $message];
            }
        };
    }
}
