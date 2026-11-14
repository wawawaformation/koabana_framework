<?php

declare(strict_types=1);

namespace Tests\Integration;

use Koabana\Controller\RegisterController;
use Psr\Http\Message\ResponseInterface;

/**
 * Inscription : formulaire, e-mail d'activation, puis création du mot de passe.
 *
 * @internal
 *
 * @coversNothing
 */
final class RegisterControllerTest extends ControllerTestCase
{
    private const array VALID_FORM = [
        'firstname' => 'Éloïse',
        'lastname' => 'Lé',
        'email' => 'eloise@example.com',
    ];

    private RegisterController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RegisterController($this->view, $this->db->repository, $this->mailer);
    }

    // --- Formulaire d'inscription ---

    public function testRegisterDisplaysTheForm(): void
    {
        $response = $this->controller->register($this->request('GET', '/inscription'), []);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="email"', (string) $response->getBody());
    }

    public function testValidRegistrationCreatesAnInactiveUserWithA32CharToken(): void
    {
        $response = $this->register(self::VALID_FORM);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/inscription/success', $response->getHeaderLine('Location'));

        $user = $this->db->user('eloise@example.com');
        self::assertNotNull($user);
        self::assertSame('Éloïse', $user->getFirstname());
        self::assertFalse($user->isActive());
        self::assertNull($user->getPasswordHash());
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $user->getToken());
        self::assertGreaterThan(new \DateTimeImmutable('+59 minutes'), $user->getTokenExpiresAt());
    }

    public function testValidRegistrationSendsTheActivationLink(): void
    {
        $this->register(self::VALID_FORM);

        $token = (string) $this->db->user('eloise@example.com')?->getToken();
        self::assertSame(1, $this->mailer->sentCount);
        self::assertSame(['eloise@example.com'], $this->mailer->recipients());
        self::assertStringContainsString(self::APP_URL.'/activate/'.$token, $this->mailer->Body);
        self::assertStringContainsString(self::APP_URL.'/activate/'.$token, $this->mailer->AltBody);
    }

    public function testActivationLinkIgnoresAForgedHostHeader(): void
    {
        // Le lien doit venir de APP_URL, jamais de l'hôte envoyé par le client
        $this->register(self::VALID_FORM, 'http://site-pirate.example/inscription');

        self::assertStringNotContainsString('site-pirate.example', $this->mailer->Body);
        self::assertStringContainsString(self::APP_URL.'/activate/', $this->mailer->Body);
    }

    public function testRegistrationFailsLoudlyWithoutAppUrl(): void
    {
        putenv('APP_URL');

        try {
            $this->register(self::VALID_FORM);
            self::fail('Une exception était attendue sans APP_URL.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('APP_URL', $e->getMessage());
        }

        self::assertSame(0, $this->db->countUsers());
        self::assertSame(0, $this->mailer->sentCount);
    }

    public function testInvalidRegistrationCreatesNothing(): void
    {
        $response = $this->register(['email' => 'eloise@'] + self::VALID_FORM);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(htmlspecialchars("Format d'email invalide.", ENT_QUOTES), (string) $response->getBody());
        self::assertSame(0, $this->db->countUsers());
        self::assertSame(0, $this->mailer->sentCount);
    }

    public function testEmailOfAnActiveAccountIsRefused(): void
    {
        $this->db->addUser('eloise@example.com', password: 'motdepasse1', isActive: true);

        $response = $this->register(self::VALID_FORM);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Cette adresse e-mail est déjà utilisée.', (string) $response->getBody());
        self::assertSame(1, $this->db->countUsers());
        self::assertSame(0, $this->mailer->sentCount);
    }

    public function testInactiveAccountReceivesANewActivationLink(): void
    {
        // Compte jamais activé : e-mail perdu, lien expiré ou envoi en échec
        $oldToken = str_repeat('e', 32);
        $this->db->addUser('eloise@example.com', token: $oldToken, tokenExpiresAt: new \DateTimeImmutable('-1 day'));

        $response = $this->register(self::VALID_FORM);

        self::assertSame('/inscription/success', $response->getHeaderLine('Location'));
        self::assertSame(1, $this->db->countUsers());

        $user = $this->db->user('eloise@example.com');
        self::assertNotNull($user);
        self::assertSame('Éloïse', $user->getFirstname());
        self::assertFalse($user->isActive());
        self::assertNotSame($oldToken, $user->getToken());
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $user->getToken());
        self::assertGreaterThan(new \DateTimeImmutable(), $user->getTokenExpiresAt());
        self::assertStringContainsString(self::APP_URL.'/activate/'.$user->getToken(), $this->mailer->Body);
    }

    public function testMailFailureRedirectsWithAWarning(): void
    {
        $this->mailer->failOnSend = true;
        $request = $this->request('POST', '/inscription', self::VALID_FORM);

        // Le contrôleur journalise l'échec avec error_log() : on l'écarte de la sortie du test
        $previousErrorLog = ini_set('error_log', '/dev/null');
        $response = $this->controller->register($request, []);
        ini_set('error_log', (string) $previousErrorLog);

        self::assertSame('/inscription', $response->getHeaderLine('Location'));
        self::assertArrayHasKey('warning', $this->flashes($request));
    }

    public function testSuccessPageIsDisplayed(): void
    {
        self::assertSame(200, $this->controller->success($this->request('GET', '/inscription/success'))->getStatusCode());
    }

    // --- Activation et création du mot de passe ---

    public function testUnknownTokenRedirectsToRegistration(): void
    {
        $request = $this->request('GET', '/activate/inconnu');

        $response = $this->controller->activate($request, ['token' => str_repeat('0', 32)]);

        self::assertSame('/inscription', $response->getHeaderLine('Location'));
        self::assertArrayHasKey('error', $this->flashes($request));
    }

    public function testExpiredTokenIsClearedAndRedirects(): void
    {
        $token = str_repeat('a', 32);
        $this->db->addUser(token: $token, tokenExpiresAt: new \DateTimeImmutable('-1 minute'));

        $response = $this->controller->activate($this->request('GET', '/activate/'.$token), ['token' => $token]);

        self::assertSame('/inscription', $response->getHeaderLine('Location'));
        self::assertNull($this->db->user('ada@example.com')?->getToken());
    }

    public function testActivationLinkDisplaysThePasswordFormWithoutActivatingYet(): void
    {
        $token = $this->pendingUserToken();

        $response = $this->controller->activate($this->request('GET', '/activate/'.$token), ['token' => $token]);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="password"', (string) $response->getBody());
        // Le compte n'est activé qu'une fois le mot de passe créé
        self::assertFalse($this->db->user('ada@example.com')?->isActive());
    }

    public function testMismatchedConfirmationKeepsTheAccountInactive(): void
    {
        $token = $this->pendingUserToken();
        $request = $this->request('POST', '/activate/'.$token, ['password' => 'motdepasse1', 'confirmation' => 'motdepasse2']);

        $response = $this->controller->activate($request, ['token' => $token]);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('La confirmation ne correspond pas au mot de passe', (string) $response->getBody());
        self::assertFalse($this->db->user('ada@example.com')?->isActive());
    }

    public function testSettingThePasswordActivatesTheAccount(): void
    {
        $token = $this->pendingUserToken();
        $request = $this->request('POST', '/activate/'.$token, ['password' => 'motdepasse1', 'confirmation' => 'motdepasse1']);

        $response = $this->controller->activate($request, ['token' => $token]);

        self::assertSame('/connexion', $response->getHeaderLine('Location'));
        $user = $this->db->user('ada@example.com');
        self::assertNotNull($user);
        self::assertTrue($user->isActive());
        self::assertTrue(password_verify('motdepasse1', (string) $user->getPasswordHash()));
        self::assertNull($user->getToken());
        self::assertNull($user->getTokenExpiresAt());
    }

    /**
     * @param array<string, string> $form
     */
    private function register(array $form, string $uri = '/inscription'): ResponseInterface
    {
        return $this->controller->register($this->request('POST', $uri, $form), []);
    }

    private function pendingUserToken(): string
    {
        $token = str_repeat('b', 32);
        $this->db->addUser(token: $token, tokenExpiresAt: new \DateTimeImmutable('+1 hour'));

        return $token;
    }
}
