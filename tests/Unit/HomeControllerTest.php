<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Controller\HomeController;
use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use Koabana\View\PhpTemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class HomeControllerTest extends TestCase
{
    private HomeController $controller;

    protected function setUp(): void
    {
        // Vrai moteur de rendu sur les vrais templates : le test échoue si un template manque
        $this->controller = new HomeController(new PhpTemplateRenderer(\dirname(__DIR__, 2).'/templates'));
    }

    public function testIndexReturnsResponseWithStatus200(): void
    {
        $request = $this->request();
        $response = $this->controller->index($request, []);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testIndexReturnsHtmlContentType(): void
    {
        $request = $this->request();
        $response = $this->controller->index($request, []);

        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testIndexReturnsResponseInterface(): void
    {
        $request = $this->request();
        $response = $this->controller->index($request, []);

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testIndexRendersPageInsideLayout(): void
    {
        $body = (string) $this->controller->index($this->request(), [])->getBody();

        // Le titre vient du layout, le h1 du template de la page
        self::assertStringContainsString('<title>Accueil | Koabana</title>', $body);
        self::assertStringContainsString('<h1>Bienvenue sur Koabana Framework</h1>', $body);
    }

    // Requête avec les bags que SessionMiddleware ajoute en conditions réelles
    private function request(): ServerRequestInterface
    {
        $data = [];
        $session = new SessionBag($data);

        return new ServerRequest('GET', 'http://localhost/')
            ->withAttribute('flash', new FlashBag($session))
            ->withAttribute('profile', new ProfileBag($session))
        ;
    }
}
