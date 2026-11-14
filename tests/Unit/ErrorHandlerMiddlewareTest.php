<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\Middleware\ErrorHandlerMiddleware;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class ErrorHandlerMiddlewareTest extends TestCase
{
    public function testNotFoundExceptionReturns404(): void
    {
        $middleware = new ErrorHandlerMiddleware($this->createStub(LoggerInterface::class), false);

        $response = $middleware->process($this->request(), $this->handlerThrowing(new NotFoundException()));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testMethodNotAllowedExceptionReturns405(): void
    {
        $middleware = new ErrorHandlerMiddleware($this->createStub(LoggerInterface::class), false);

        $response = $middleware->process($this->request(), $this->handlerThrowing(new MethodNotAllowedException(['GET'])));

        self::assertSame(405, $response->getStatusCode());
    }

    public function testHttpExceptionIsLoggedAsWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');
        $logger->expects(self::never())->method('error');

        $middleware = new ErrorHandlerMiddleware($logger, false);
        $middleware->process($this->request(), $this->handlerThrowing(new NotFoundException()));
    }

    public function testOtherExceptionReturns500AndIsLoggedAsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $middleware = new ErrorHandlerMiddleware($logger, false);
        $response = $middleware->process($this->request(), $this->handlerThrowing(new \RuntimeException('boom')));

        self::assertSame(500, $response->getStatusCode());
    }

    private function request(): ServerRequestInterface
    {
        return new ServerRequest('GET', 'http://localhost/inconnue');
    }

    // Handler qui lève l'exception donnée, pour simuler le routeur
    private function handlerThrowing(\Throwable $exception): RequestHandlerInterface
    {
        return new class($exception) implements RequestHandlerInterface {
            public function __construct(private readonly \Throwable $exception) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }
}
