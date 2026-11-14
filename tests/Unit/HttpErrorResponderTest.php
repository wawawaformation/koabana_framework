<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\HttpErrorResponder;
use Koabana\View\PhpTemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Transformation d'un code d'erreur HTTP en page HTML ou en JSON.
 *
 * @internal
 *
 * @coversNothing
 */
final class HttpErrorResponderTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        // Templates réels du framework, copiés pour pouvoir ajouter des surcharges
        $this->dir = sys_get_temp_dir().'/koabana-errors-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/layout', 0o777, true);
        mkdir($this->dir.'/errors');
        copy(\dirname(__DIR__, 2).'/templates/layout/main.php', $this->dir.'/layout/main.php');
        copy(\dirname(__DIR__, 2).'/templates/errors/error.php', $this->dir.'/errors/error.php');
    }

    protected function tearDown(): void
    {
        foreach (['layout/main', 'errors/error', 'errors/404'] as $file) {
            @unlink($this->dir.'/'.$file.'.php');
        }
        @rmdir($this->dir.'/layout');
        @rmdir($this->dir.'/errors');
        @rmdir($this->dir);
    }

    #[DataProvider('pages')]
    public function testHtmlPageInTheLayout(int $status, string $title, string $message): void
    {
        $response = $this->responder()->respond(new ServerRequest('GET', '/'), $status);

        $body = (string) $response->getBody();
        self::assertSame($status, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<title>'.htmlspecialchars($title).' | Koabana</title>', $body);
        self::assertStringContainsString('<h1>'.htmlspecialchars($title).'</h1>', $body);
        self::assertStringContainsString(htmlspecialchars($message, ENT_QUOTES), $body);
        self::assertStringContainsString('href="/"', $body);
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function pages(): iterable
    {
        yield '400' => [400, 'Requête invalide', "La requête envoyée n'a pas pu être traitée."];

        yield '403' => [403, 'Accès refusé', "Vous n'avez pas accès à cette page, ou votre session a expiré. Rechargez la page puis réessayez."];

        yield '404' => [404, 'Page introuvable', "La page demandée n'existe pas ou a été déplacée."];

        yield '405' => [405, 'Méthode non autorisée', "Cette action n'est pas possible sur cette page."];

        yield 'code sans message dédié' => [429, 'Erreur 429', 'Une erreur est survenue lors du traitement de la requête.'];
    }

    public function testTemplateForTheCodeReplacesTheCommonOne(): void
    {
        file_put_contents($this->dir.'/errors/404.php', '<p>Perdu ? <?= $status ?></p>');

        $body = (string) $this->responder()->respond(new ServerRequest('GET', '/'), 404)->getBody();

        self::assertStringContainsString('<p>Perdu ? 404</p>', $body);
        self::assertStringNotContainsString('<h1>', $body);
    }

    public function testHeadersAreKept(): void
    {
        $response = $this->responder()->respond(new ServerRequest('GET', '/'), 405, ['Allow' => 'GET, POST']);

        self::assertSame('GET, POST', $response->getHeaderLine('Allow'));
    }

    #[DataProvider('jsonAccepts')]
    public function testJsonWhenAcceptAsksForIt(string $accept): void
    {
        $request = new ServerRequest('GET', '/', ['Accept' => $accept]);

        $response = $this->responder()->respond($request, 404);

        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"status":404,"error":"Page introuvable"}', (string) $response->getBody());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function jsonAccepts(): iterable
    {
        yield 'application/json' => ['application/json'];

        yield 'problem+json' => ['application/problem+json'];
    }

    public function testAnyOtherAcceptGetsHtml(): void
    {
        $request = new ServerRequest('GET', '/', ['Accept' => '*/*']);

        self::assertStringStartsWith('text/html', $this->responder()->respond($request, 404)->getHeaderLine('Content-Type'));
    }

    public function testBrokenErrorTemplateFallsBackToPlainText(): void
    {
        file_put_contents($this->dir.'/errors/404.php', '<?php throw new RuntimeException("boum");');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $response = $this->responder($logger)->respond(new ServerRequest('GET', '/'), 404);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('Page introuvable', (string) $response->getBody());
    }

    private function responder(?LoggerInterface $logger = null): HttpErrorResponder
    {
        return new HttpErrorResponder(new PhpTemplateRenderer($this->dir), $logger ?? new NullLogger());
    }
}
