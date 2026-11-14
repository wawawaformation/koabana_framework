<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Cohérence entre les routes à jeton et les jetons générés par les contrôleurs.
 *
 * @internal
 *
 * @coversNothing
 */
final class RoutesTest extends TestCase
{
    #[DataProvider('tokenRoutes')]
    public function testTokenRouteAcceptsGeneratedTokens(string $method, string $prefix): void
    {
        // Même génération que RegisterController et LoginController
        $token = bin2hex(random_bytes(16));

        self::assertTrue($this->matchesARoute($method, $prefix.$token), "{$method} {$prefix}{$token} ne correspond à aucune route.");
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function tokenRoutes(): iterable
    {
        yield 'activation (GET)' => ['GET', '/activate/'];

        yield 'activation (POST)' => ['POST', '/activate/'];

        yield 'réinitialisation (GET)' => ['GET', '/reinitialisation-mot-de-passe/'];

        yield 'réinitialisation (POST)' => ['POST', '/reinitialisation-mot-de-passe/'];
    }

    public function testLogoutOnlyAcceptsPost(): void
    {
        // En GET, n'importe quel site pourrait déconnecter l'utilisateur (image, lien)
        self::assertTrue($this->matchesARoute('POST', '/deconnexion'));
        self::assertFalse($this->matchesARoute('GET', '/deconnexion'));
    }

    /**
     * Convertit les motifs « {nom: regex} » de config/routes.php en expression régulière.
     */
    private function matchesARoute(string $method, string $path): bool
    {
        /** @var list<array{string, string, mixed}> $routes */
        $routes = require \dirname(__DIR__, 2).'/config/routes.php';

        foreach ($routes as [$routeMethod, $pattern]) {
            $regex = preg_replace('/\{\w+:\s*([^}]+(?:\}[^}]*)?)\}/', '($1)', $pattern);
            if ($routeMethod === $method && 1 === preg_match('~^'.$regex.'$~', $path)) {
                return true;
            }
        }

        return false;
    }
}
