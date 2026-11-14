<?php

declare(strict_types=1);

namespace Tests\Integration;

use Koabana\Controller\DashboardController;

/**
 * Espace connecté : accès réservé aux utilisateurs connectés.
 *
 * @internal
 *
 * @coversNothing
 */
final class DashboardControllerTest extends ControllerTestCase
{
    public function testAnonymousVisitorIsRedirectedToLogin(): void
    {
        $request = $this->request('GET', '/mon-espace');

        $response = new DashboardController($this->view)->index($request);

        self::assertSame('/connexion', $response->getHeaderLine('Location'));
        self::assertArrayHasKey('danger', $this->flashes($request));
    }

    public function testLoggedUserSeesTheDashboard(): void
    {
        $request = $this->request('GET', '/mon-espace');
        $this->profile($request)->set(['user_id' => 1, 'user_firstname' => 'Ada', 'user_lastname' => 'Lovelace', 'user_email' => 'ada@example.com']);

        $response = new DashboardController($this->view)->index($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Bienvenue Ada.', (string) $response->getBody());
    }
}
