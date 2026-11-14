<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\Middleware\SessionMiddleware;
use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Support\WithEnv;

/**
 * Démarrage de la vraie session PHP : chaque test tourne dans un processus séparé.
 *
 * @internal
 *
 * @coversNothing
 */
#[RunTestsInSeparateProcesses]
final class SessionMiddlewareTest extends TestCase
{
    use WithEnv;

    private ?ServerRequestInterface $handled = null;

    public function testSessionIsStartedWithHardenedCookieSettings(): void
    {
        $this->setEnv('APP_ENV', 'dev');

        $this->process(new SessionMiddleware());

        self::assertSame(PHP_SESSION_ACTIVE, session_status());
        self::assertSame('KOABANA_SESSION', session_name());
        self::assertSame('1', ini_get('session.use_strict_mode'));
        self::assertSame('1', ini_get('session.use_only_cookies'));

        $cookie = session_get_cookie_params();
        self::assertSame(7200, $cookie['lifetime']);
        self::assertSame('/', $cookie['path']);
        self::assertTrue($cookie['httponly']);
        self::assertSame('Lax', $cookie['samesite']);
        self::assertFalse($cookie['secure']);
    }

    public function testCustomNameAndLifetime(): void
    {
        $this->process(new SessionMiddleware('AUTRE_SESSION', 600));

        self::assertSame('AUTRE_SESSION', session_name());
        self::assertSame(600, session_get_cookie_params()['lifetime']);
    }

    public function testCookieIsSecureInProductionOverHttps(): void
    {
        $this->setEnv('APP_ENV', 'prod');
        $_SERVER['HTTPS'] = 'on';

        $this->process(new SessionMiddleware());

        self::assertTrue(session_get_cookie_params()['secure']);
    }

    public function testCookieIsNotSecureInProductionOverHttp(): void
    {
        $this->setEnv('APP_ENV', 'prod');
        unset($_SERVER['HTTPS']);

        $this->process(new SessionMiddleware());

        self::assertFalse(session_get_cookie_params()['secure']);
    }

    public function testAlreadyActiveSessionIsKept(): void
    {
        session_name('DEJA_ACTIVE');
        session_start();

        $this->process(new SessionMiddleware());

        self::assertSame('DEJA_ACTIVE', session_name());
    }

    public function testBagsAreAttachedAndWriteIntoTheSession(): void
    {
        $this->process(new SessionMiddleware());

        $request = $this->handled;
        self::assertNotNull($request);
        self::assertInstanceOf(FlashBag::class, $request->getAttribute('flash'));
        self::assertInstanceOf(ProfileBag::class, $request->getAttribute('profile'));

        $session = $request->getAttribute('session');
        self::assertInstanceOf(SessionBag::class, $session);
        $session->set('panier', 3);
        self::assertSame(3, $_SESSION['panier']);
    }

    public function testProfileIsIsolatedFromTheRestOfTheSession(): void
    {
        $this->process(new SessionMiddleware());
        $request = $this->handled;
        self::assertNotNull($request);
        $_SESSION['_csrf_token'] = 'jeton';
        $_SESSION['panier'] = 3;

        $profile = $request->getAttribute('profile');
        self::assertInstanceOf(ProfileBag::class, $profile);
        $profile->set(['user_id' => 7, '_csrf_token' => 'écrasé ?']);

        self::assertSame(7, $_SESSION['profile']['user_id']);
        self::assertSame('jeton', $_SESSION['_csrf_token']);

        // Vider le profil (déconnexion) ne touche pas au reste de la session
        $profile->clear();
        self::assertFalse($profile->isLogged());
        self::assertSame('jeton', $_SESSION['_csrf_token']);
        self::assertSame(3, $_SESSION['panier']);
    }

    /**
     * @internal appelé par le gestionnaire de test
     */
    public function remember(ServerRequestInterface $request): void
    {
        $this->handled = $request;
    }

    private function process(SessionMiddleware $middleware): ResponseInterface
    {
        // Gestionnaire qui mémorise la requête reçue
        $handler = new class($this) implements RequestHandlerInterface {
            public function __construct(private readonly SessionMiddlewareTest $test) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->remember($request);

                return new Response(200);
            }
        };

        return $middleware->process(new ServerRequest('GET', '/'), $handler);
    }
}
