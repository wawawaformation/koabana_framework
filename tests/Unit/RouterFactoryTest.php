<?php

declare(strict_types=1);

namespace Tests\Unit;

use DI\Container;
use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Bootstrap\RouterFactory;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Chargement des routes depuis un fichier [méthode, chemin, gestionnaire].
 *
 * @internal
 *
 * @coversNothing
 */
final class RouterFactoryTest extends TestCase
{
    private string $routesFile;

    protected function setUp(): void
    {
        // Gestionnaires sous forme de closures : pas besoin de contrôleur ni de conteneur rempli
        $this->routesFile = sys_get_temp_dir().'/koabana-routes-'.bin2hex(random_bytes(4)).'.php';
        file_put_contents($this->routesFile, <<<'PHP'
            <?php
            return [
                ['GET', '/', fn () => new GuzzleHttp\Psr7\Response(200, [], 'accueil')],
                ['GET', '/article/{id:\d+}', fn ($request, array $args) => new GuzzleHttp\Psr7\Response(200, [], 'article '.$args['id'])],
            ];
            PHP);
    }

    protected function tearDown(): void
    {
        unlink($this->routesFile);
    }

    public function testRoutesAreDispatchedToTheirHandler(): void
    {
        $router = RouterFactory::create(new Container(), $this->routesFile);

        self::assertSame('accueil', (string) $router->dispatch(new ServerRequest('GET', '/'))->getBody());
    }

    public function testRouteParametersAreForwarded(): void
    {
        $router = RouterFactory::create(new Container(), $this->routesFile);

        self::assertSame('article 42', (string) $router->dispatch(new ServerRequest('GET', '/article/42'))->getBody());
    }

    public function testUnknownPathIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        RouterFactory::create(new Container(), $this->routesFile)->dispatch(new ServerRequest('GET', '/inconnu'));
    }

    public function testWrongMethodIsNotAllowed(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        RouterFactory::create(new Container(), $this->routesFile)->dispatch(new ServerRequest('POST', '/'));
    }
}
