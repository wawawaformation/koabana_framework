<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Controller\AbstractController;
use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use Koabana\View\PhpTemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Méthodes utilitaires communes aux contrôleurs.
 *
 * @internal
 *
 * @coversNothing
 */
final class AbstractControllerTest extends TestCase
{
    private object $controller;

    protected function setUp(): void
    {
        // Sous-classe de test qui rend publiques les méthodes protégées
        $this->controller = new class(new PhpTemplateRenderer(\dirname(__DIR__, 2).'/templates')) extends AbstractController {
            /** @param array<string, string> $headers */
            public function callHtml(string $html, int $status = 200, array $headers = []): ResponseInterface
            {
                return $this->html($html, $status, $headers);
            }

            public function callText(string $text): ResponseInterface
            {
                return $this->text($text);
            }

            /** @param array<string, mixed> $data */
            public function callJson(array $data): ResponseInterface
            {
                return $this->json($data);
            }

            public function callNotFound(): ResponseInterface
            {
                return $this->notFound();
            }

            public function callRedirect(string $location, int $status): ResponseInterface
            {
                return $this->redirect($location, $status);
            }

            public function callIsLogged(ServerRequestInterface $request): bool
            {
                return $this->isLogged($request);
            }

            public function callSession(ServerRequestInterface $request): SessionBag
            {
                return $this->session($request);
            }

            public function callAddFlash(ServerRequestInterface $request): void
            {
                $this->addFlash($request, 'info', 'Message');
            }
        };
    }

    // --- URL de base ---

    #[DataProvider('baseUrls')]
    public function testBaseUrl(string $uri, string $expected): void
    {
        self::assertSame($expected, $this->controller->getBaseUrl(new ServerRequest('GET', $uri)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function baseUrls(): iterable
    {
        yield 'port non standard' => ['http://localhost:8080/page', 'http://localhost:8080'];

        yield 'https sans port' => ['https://exemple.fr/page', 'https://exemple.fr'];

        yield 'http sur le port 80' => ['http://exemple.fr:80/page', 'http://exemple.fr'];

        yield 'https sur le port 8443' => ['https://exemple.fr:8443/page', 'https://exemple.fr:8443'];
    }

    // --- Réponses ---

    public function testHtmlResponse(): void
    {
        $response = $this->controller->callHtml('<p>Bonjour</p>', 201);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('<p>Bonjour</p>', (string) $response->getBody());
    }

    public function testCustomContentTypeIsKeptWhateverItsCase(): void
    {
        $response = $this->controller->callHtml('<svg/>', 200, ['content-type' => 'image/svg+xml']);

        self::assertSame('image/svg+xml', $response->getHeaderLine('Content-Type'));
    }

    public function testTextAndNotFoundResponses(): void
    {
        self::assertSame('text/plain; charset=utf-8', $this->controller->callText('Bonjour')->getHeaderLine('Content-Type'));

        $notFound = $this->controller->callNotFound();
        self::assertSame(404, $notFound->getStatusCode());
        self::assertSame('Not Found', (string) $notFound->getBody());
    }

    public function testJsonResponseKeepsUnicodeAndSlashes(): void
    {
        $response = $this->controller->callJson(['ville' => 'Angoulême', 'url' => '/a/b']);

        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"ville":"Angoulême","url":"/a/b"}', (string) $response->getBody());
    }

    public function testJsonEncodingErrorReturns500(): void
    {
        // Octets invalides en UTF-8 : json_encode échoue
        $response = $this->controller->callJson(['texte' => "\xB1\x31"]);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('JSON encoding error', (string) $response->getBody());
    }

    public function testRedirectWithCustomStatus(): void
    {
        $response = $this->controller->callRedirect('/ailleurs', 301);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/ailleurs', $response->getHeaderLine('Location'));
    }

    // --- Accès aux bags de session ---

    public function testBagsAreReadFromRequestAttributes(): void
    {
        $data = [];
        $session = new SessionBag($data);
        $request = new ServerRequest('GET', '/')
            ->withAttribute('session', $session)
            ->withAttribute('profile', new ProfileBag($session))
        ;

        self::assertSame($session, $this->controller->callSession($request));
        self::assertFalse($this->controller->callIsLogged($request));
    }

    public function testMissingFlashBagIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FlashBag introuvable');

        $this->controller->callAddFlash(new ServerRequest('GET', '/'));
    }

    public function testMissingProfileBagIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ProfileBag introuvable');

        $this->controller->callIsLogged(new ServerRequest('GET', '/'));
    }

    public function testMissingSessionBagIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SessionBag introuvable');

        $this->controller->callSession(new ServerRequest('GET', '/')->withAttribute('flash', 'pas un FlashBag'));
    }

    public function testFlashIsAddedToTheBag(): void
    {
        $data = [];
        $flash = new FlashBag(new SessionBag($data));

        $this->controller->callAddFlash(new ServerRequest('GET', '/')->withAttribute('flash', $flash));

        self::assertSame(['info' => ['Message']], $flash->all());
    }
}
