<?php

declare(strict_types=1);

namespace Koabana\Controller;

<<<<<<< HEAD
use GuzzleHttp\Psr7\Response;
use Koabana\Model\Repository\TestRepository;
=======
use Koabana\View\PhpTemplateRenderer;
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contrôleur de la page d'accueil.
 */
<<<<<<< HEAD
final class HomeController
{
    /**
     * @param TestRepository $testRepository
     */
    public function __construct(private TestRepository $testRepository) {}
=======
final class HomeController extends AbstractController
{
    public function __construct(PhpTemplateRenderer $view)
    {
        parent::__construct($view);
    }
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)

    /**
     * @param array<string, string> $args
     *
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, array $args): ResponseInterface
    {
<<<<<<< HEAD
        $response = $this->testRepository->findAll();

        $html = '<h1>Welcome to Koabana Framework</h1><p>This is the home page.</p>';

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
=======
        $html = '<h1>Welcome to Koabana Framework</h1><p>This is the home page.</p>';

        return $this->render(
            $request,
            'home/index',
            [
                'name' => 'Page de test',
            ],
>>>>>>> c69f81c (UPDATE Mise à jour depuis site reeel qui a permis de valider la pratique)
        );
    }
}
