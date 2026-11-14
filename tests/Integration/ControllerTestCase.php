<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use Koabana\View\PhpTemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base des tests de contrôleurs : vraie base SQLite, vrais templates, mailer espion.
 */
abstract class ControllerTestCase extends TestCase
{
    protected const string APP_URL = 'https://koabana.test';

    protected UserDatabase $db;
    protected SpyMailer $mailer;
    protected PhpTemplateRenderer $view;

    /** @var array<string, mixed> données de session partagées par les bags */
    private array $session = [];

    private false|string $previousAppUrl;

    protected function setUp(): void
    {
        $this->db = new UserDatabase();
        $this->mailer = new SpyMailer();
        $this->view = new PhpTemplateRenderer(\dirname(__DIR__, 2).'/templates');

        $this->previousAppUrl = getenv('APP_URL');
        putenv('APP_URL='.self::APP_URL);
    }

    protected function tearDown(): void
    {
        putenv(false === $this->previousAppUrl ? 'APP_URL' : 'APP_URL='.$this->previousAppUrl);
    }

    /**
     * Requête avec les bags que SessionMiddleware ajoute en conditions réelles.
     *
     * @param array<string, string> $body
     */
    protected function request(string $method, string $uri, array $body = []): ServerRequestInterface
    {
        $session = new SessionBag($this->session);
        $request = new ServerRequest($method, $uri)
            ->withAttribute('session', $session)
            ->withAttribute('flash', new FlashBag($session))
            ->withAttribute('profile', new ProfileBag($session->bag('profile')))
            ->withAttribute('csrf_token', 'jeton-csrf')
        ;

        return [] === $body ? $request : $request->withParsedBody($body);
    }

    /**
     * Messages flash en attente (non encore affichés).
     *
     * @return array<string, list<string>>
     */
    protected function flashes(ServerRequestInterface $request): array
    {
        $flash = $request->getAttribute('flash');
        self::assertInstanceOf(FlashBag::class, $flash);

        return $flash->all();
    }

    protected function profile(ServerRequestInterface $request): ProfileBag
    {
        $profile = $request->getAttribute('profile');
        self::assertInstanceOf(ProfileBag::class, $profile);

        return $profile;
    }
}
