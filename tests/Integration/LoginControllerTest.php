<?php

declare(strict_types=1);

namespace Tests\Integration;

use Koabana\Controller\LoginController;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Connexion, déconnexion, mot de passe oublié et réinitialisation.
 *
 * @internal
 *
 * @coversNothing
 */
final class LoginControllerTest extends ControllerTestCase
{
    private LoginController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new LoginController($this->view, $this->db->repository, $this->mailer);
    }

    // --- Connexion ---

    public function testLoginDisplaysTheForm(): void
    {
        $response = $this->controller->login($this->request('GET', '/connexion'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="password"', (string) $response->getBody());
    }

    public function testValidCredentialsLogTheUserIn(): void
    {
        $user = $this->db->addUser(password: 'motdepasse1', isActive: true);
        $request = $this->request('POST', '/connexion', ['email' => 'ada@example.com', 'password' => 'motdepasse1']);

        $response = $this->controller->login($request);

        self::assertSame('/mon-espace', $response->getHeaderLine('Location'));
        self::assertTrue($this->profile($request)->isLogged());
        self::assertSame($user->getId(), $this->profile($request)->getId());
        self::assertSame('ada@example.com', $this->profile($request)->getEmail());
    }

    public function testWrongPasswordIsRefused(): void
    {
        $this->db->addUser(password: 'motdepasse1', isActive: true);
        $request = $this->request('POST', '/connexion', ['email' => 'ada@example.com', 'password' => 'mauvais']);

        $response = $this->controller->login($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Email ou mot de passe incorrect.', (string) $response->getBody());
        self::assertFalse($this->profile($request)->isLogged());
    }

    public function testInactiveAccountCannotLogIn(): void
    {
        $this->db->addUser(password: 'motdepasse1', isActive: false);
        $request = $this->request('POST', '/connexion', ['email' => 'ada@example.com', 'password' => 'motdepasse1']);

        $response = $this->controller->login($request);

        // Même message que pour un mauvais mot de passe : on ne révèle pas l'état du compte
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Email ou mot de passe incorrect.', (string) $response->getBody());
        self::assertFalse($this->profile($request)->isLogged());
    }

    public function testUnknownEmailIsRefusedWithTheSameMessage(): void
    {
        $request = $this->request('POST', '/connexion', ['email' => 'inconnu@example.com', 'password' => 'motdepasse1']);

        $response = $this->controller->login($request);

        self::assertStringContainsString('Email ou mot de passe incorrect.', (string) $response->getBody());
        self::assertFalse($this->profile($request)->isLogged());
    }

    /**
     * Processus séparé : la session PHP native ne doit pas fuir vers les autres tests.
     */
    #[RunInSeparateProcess]
    public function testSuccessfulLoginRegeneratesTheSessionId(): void
    {
        // Session sans cookie : démarrable en CLI malgré la sortie de PHPUnit
        ini_set('session.use_cookies', '0');
        ini_set('session.save_path', sys_get_temp_dir());
        session_start();
        $before = session_id();

        $this->db->addUser(password: 'motdepasse1', isActive: true);
        $this->controller->login(
            $this->request('POST', '/connexion', ['email' => 'ada@example.com', 'password' => 'motdepasse1']),
        );

        self::assertNotSame($before, session_id());
        session_destroy();
    }

    public function testLogoutClearsTheProfile(): void
    {
        $request = $this->request('GET', '/deconnexion');
        $this->profile($request)->set(['user_id' => 1, 'user_firstname' => 'Ada', 'user_lastname' => 'Lovelace', 'user_email' => 'ada@example.com']);

        $response = $this->controller->logout($request);

        self::assertSame('/connexion', $response->getHeaderLine('Location'));
        self::assertFalse($this->profile($request)->isLogged());
    }

    // --- Mot de passe oublié ---

    public function testForgotPasswordDisplaysTheForm(): void
    {
        $response = $this->controller->forgotPassword($this->request('GET', '/utilisateur/mot-de-passe-oublie'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testUnknownEmailGetsTheSameAnswerWithoutMail(): void
    {
        $request = $this->request('POST', '/utilisateur/mot-de-passe-oublie', ['email' => 'inconnu@example.com']);

        $response = $this->controller->forgotPassword($request);

        self::assertSame('/connexion', $response->getHeaderLine('Location'));
        self::assertSame(
            ['success' => ['Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.']],
            $this->flashes($request),
        );
        self::assertSame(0, $this->mailer->sentCount);
    }

    public function testKnownEmailReceivesAResetLink(): void
    {
        $this->db->addUser(password: 'motdepasse1', isActive: true);

        $this->forgotPassword('ada@example.com');

        $token = (string) $this->db->user('ada@example.com')?->getToken();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
        self::assertSame(['ada@example.com'], $this->mailer->recipients());
        self::assertStringContainsString(self::APP_URL.'/reinitialisation-mot-de-passe/'.$token, $this->mailer->Body);
    }

    public function testResetLinkIgnoresAForgedHostHeader(): void
    {
        $this->db->addUser(password: 'motdepasse1', isActive: true);

        $this->forgotPassword('ada@example.com', 'http://site-pirate.example/utilisateur/mot-de-passe-oublie');

        self::assertStringNotContainsString('site-pirate.example', $this->mailer->Body);
        self::assertStringNotContainsString('site-pirate.example', $this->mailer->AltBody);
    }

    public function testResetMailFailureIsReported(): void
    {
        $this->db->addUser(password: 'motdepasse1', isActive: true);
        $this->mailer->failOnSend = true;

        $request = $this->request('POST', '/utilisateur/mot-de-passe-oublie', ['email' => 'ada@example.com']);
        $response = $this->controller->forgotPassword($request);

        self::assertSame('/connexion', $response->getHeaderLine('Location'));
        self::assertArrayHasKey('error', $this->flashes($request));
    }

    public function testResetMailKeepsTheConfiguredSender(): void
    {
        // MailerFactory règle l'expéditeur depuis SMTP_FROM_EMAIL : le contrôleur ne doit pas l'écraser
        $this->mailer->setFrom('noreply@koabana.test', 'Koabana');
        $this->db->addUser(password: 'motdepasse1', isActive: true);

        $this->forgotPassword('ada@example.com');

        self::assertSame('noreply@koabana.test', $this->mailer->From);
    }

    // --- Réinitialisation ---

    public function testValidResetLinkDisplaysThePasswordForm(): void
    {
        $token = $this->resetToken();

        $response = $this->controller->resetPassword($this->request('GET', '/reinitialisation-mot-de-passe/'.$token), ['token' => $token]);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('name="confirmation"', (string) $response->getBody());
    }

    public function testExpiredResetLinkIsClearedAndRefused(): void
    {
        $token = str_repeat('c', 32);
        $this->db->addUser(password: 'ancien-mdp', isActive: true, token: $token, tokenExpiresAt: new \DateTimeImmutable('-1 minute'));
        $request = $this->request('GET', '/reinitialisation-mot-de-passe/'.$token);

        $response = $this->controller->resetPassword($request, ['token' => $token]);

        self::assertSame('/utilisateur/mot-de-passe-oublie', $response->getHeaderLine('Location'));
        self::assertArrayHasKey('error', $this->flashes($request));
        self::assertNull($this->db->user('ada@example.com')?->getToken());
    }

    public function testMismatchedConfirmationKeepsTheOldPassword(): void
    {
        $token = $this->resetToken();
        $request = $this->request('POST', '/reinitialisation-mot-de-passe/'.$token, ['password' => 'nouveau-mdp1', 'confirmation' => 'nouveau-mdp2']);

        $response = $this->controller->resetPassword($request, ['token' => $token]);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Le mot de passe de confirmation ne correspond pas.', (string) $response->getBody());
        self::assertTrue(password_verify('ancien-mdp', (string) $this->db->user('ada@example.com')?->getPasswordHash()));
    }

    public function testResetChangesThePasswordAndConsumesTheToken(): void
    {
        $token = $this->resetToken();
        $request = $this->request('POST', '/reinitialisation-mot-de-passe/'.$token, ['password' => 'nouveau-mdp1', 'confirmation' => 'nouveau-mdp1']);

        $response = $this->controller->resetPassword($request, ['token' => $token]);

        self::assertSame('/connexion', $response->getHeaderLine('Location'));
        $user = $this->db->user('ada@example.com');
        self::assertTrue(password_verify('nouveau-mdp1', (string) $user?->getPasswordHash()));
        self::assertNull($user?->getToken());
    }

    public function testResetActivatesAnInactiveAccount(): void
    {
        // La réinitialisation prouve la possession de l'adresse, comme le lien d'activation
        $token = str_repeat('f', 32);
        $this->db->addUser(isActive: false, token: $token, tokenExpiresAt: new \DateTimeImmutable('+1 hour'));
        $request = $this->request('POST', '/reinitialisation-mot-de-passe/'.$token, ['password' => 'nouveau-mdp1', 'confirmation' => 'nouveau-mdp1']);

        $this->controller->resetPassword($request, ['token' => $token]);

        self::assertTrue($this->db->user('ada@example.com')?->isActive());
    }

    private function forgotPassword(string $email, string $uri = '/utilisateur/mot-de-passe-oublie'): void
    {
        $this->controller->forgotPassword($this->request('POST', $uri, ['email' => $email]));
    }

    private function resetToken(): string
    {
        $token = str_repeat('d', 32);
        $this->db->addUser(password: 'ancien-mdp', isActive: true, token: $token, tokenExpiresAt: new \DateTimeImmutable('+1 hour'));

        return $token;
    }
}
