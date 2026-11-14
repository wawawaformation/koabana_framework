<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\Middleware\CsrfMiddleware;
use Koabana\Http\Session\SessionBag;
use League\Route\Http\Exception\ForbiddenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private RequestHandlerInterface $handler;
    private SessionBag $session;

    /** @var array<string, mixed> */
    private array $sessionData = [];

    protected function setUp(): void
    {
        $this->middleware = new CsrfMiddleware(['/api/*']);
        $this->session = new SessionBag($this->sessionData);

        /** @var RequestHandlerInterface&Stub */
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200, [], 'OK'));
        $this->handler = $handler;
    }

    public function testGetRequestBypassesCsrfCheck(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);

        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testTokenIsGeneratedForSession(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);

        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($this->session->has('_csrf_token'));
    }

    public function testTokenIsHexadecimal(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);

        $this->middleware->process($request, $this->handler);

        $token = $this->session->get('_csrf_token');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testPostRequestWithValidTokenPasses(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $token = $this->session->get('_csrf_token');

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/form',
            ['X-CSRF-Token' => $token],
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);

        $response = $this->middleware->process($postRequest, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPostRequestWithInvalidTokenIsForbidden(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/form',
            ['X-CSRF-Token' => 'invalid_token'],
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);

        $this->expectException(ForbiddenException::class);

        $this->middleware->process($postRequest, $this->handler);
    }

    public function testExemptedPathsSkipCsrfValidation(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/api/endpoint',
            ['X-CSRF-Token' => 'invalid_token'],
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);

        $response = $this->middleware->process($postRequest, $this->handler);

        // Avec exemption /api/*, le token invalide ne cause pas de 403
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testTokenFromFormBodyWorks(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $token = $this->session->get('_csrf_token');

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/form',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            '_csrf_token='.urlencode($token).'&name=test',
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);
        $postRequest = $postRequest->withParsedBody(['_csrf_token' => $token, 'name' => 'test']);

        $response = $this->middleware->process($postRequest, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testMissingSessionIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SessionMiddleware');

        $this->middleware->process(new ServerRequest('GET', 'http://localhost/'), $this->handler);
    }

    public function testValidTokenInHeaderPasses(): void
    {
        $request = $this->post('PUT', '/profil')->withHeader('X-CSRF-Token', $this->token());

        self::assertSame(200, $this->middleware->process($request, $this->handler)->getStatusCode());
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('invalidBodies')]
    public function testInvalidBodyTokenIsRefused(string $method, array $body): void
    {
        $this->token();

        $this->expectException(ForbiddenException::class);

        $this->middleware->process($this->post($method, '/form')->withParsedBody($body), $this->handler);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function invalidBodies(): iterable
    {
        yield 'jeton absent (DELETE)' => ['DELETE', []];

        yield 'mauvais jeton (PATCH)' => ['PATCH', ['_csrf_token' => 'faux']];

        // _csrf_token[]=x : un tableau ne doit pas provoquer d'avertissement PHP, mais un refus
        yield 'jeton envoyé en tableau' => ['POST', ['_csrf_token' => ['x']]];
    }

    public function testExemptionNeedsTheWholePrefix(): void
    {
        $this->token();

        // « /api/* » exempte /api/… mais pas /apiculture
        self::assertSame(200, $this->middleware->process($this->post('POST', '/api/v1/items'), $this->handler)->getStatusCode());

        $this->expectException(ForbiddenException::class);
        $this->middleware->process($this->post('POST', '/apiculture'), $this->handler);
    }

    private function token(): string
    {
        $request = new ServerRequest('GET', 'http://localhost/')->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        return (string) $this->session->get('_csrf_token');
    }

    private function post(string $method, string $path): ServerRequest
    {
        return new ServerRequest($method, 'http://localhost'.$path)->withAttribute('session', $this->session);
    }
}
