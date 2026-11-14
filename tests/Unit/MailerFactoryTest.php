<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Mail\MailerFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\WithEnv;

/**
 * Configuration de PHPMailer depuis les variables SMTP_*.
 *
 * @internal
 *
 * @coversNothing
 */
final class MailerFactoryTest extends TestCase
{
    use WithEnv;

    private const array VARIABLES = [
        'SMTP_HOST', 'SMTP_PORT', 'SMTP_AUTH', 'SMTP_USER', 'SMTP_PASSWORD', 'SMTP_SECURE',
        'SMTP_AUTO_TLS', 'SMTP_DEBUG', 'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME',
    ];

    protected function setUp(): void
    {
        // Part d'un environnement SMTP vide, quel que soit celui de la machine
        foreach (self::VARIABLES as $name) {
            $this->setEnv($name, null);
        }
    }

    #[DataProvider('missingHosts')]
    public function testMissingHostIsRejected(?string $host): void
    {
        $this->setEnv('SMTP_HOST', $host);

        $this->expectException(\RuntimeException::class);

        new MailerFactory()->create();
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function missingHosts(): iterable
    {
        yield 'absente' => [null];

        yield 'vide' => [''];
    }

    public function testDefaultsWithOnlyTheHost(): void
    {
        $this->setEnv('SMTP_HOST', 'mailpit');

        $mailer = new MailerFactory()->create();

        self::assertSame('smtp', $mailer->Mailer);
        self::assertSame('mailpit', $mailer->Host);
        self::assertSame(25, $mailer->Port);
        self::assertFalse($mailer->SMTPAuth);
        self::assertTrue($mailer->SMTPAutoTLS);
        self::assertSame('', $mailer->SMTPSecure);
        self::assertSame('', $mailer->Username);
        self::assertSame(0, $mailer->SMTPDebug);
        self::assertSame('UTF-8', $mailer->CharSet);
    }

    public function testFullConfiguration(): void
    {
        $this->setEnv('SMTP_HOST', 'smtp.example.com');
        $this->setEnv('SMTP_PORT', '587');
        $this->setEnv('SMTP_AUTH', 'true');
        $this->setEnv('SMTP_USER', 'ada');
        $this->setEnv('SMTP_PASSWORD', 'secret');
        $this->setEnv('SMTP_SECURE', 'tls');
        $this->setEnv('SMTP_AUTO_TLS', '0');
        $this->setEnv('SMTP_DEBUG', '2');
        $this->setEnv('SMTP_FROM_EMAIL', 'noreply@example.com');
        $this->setEnv('SMTP_FROM_NAME', 'Koabana');

        $mailer = new MailerFactory()->create();

        self::assertSame(587, $mailer->Port);
        self::assertTrue($mailer->SMTPAuth);
        self::assertSame('ada', $mailer->Username);
        self::assertSame('secret', $mailer->Password);
        self::assertSame('tls', $mailer->SMTPSecure);
        self::assertFalse($mailer->SMTPAutoTLS);
        self::assertSame(2, $mailer->SMTPDebug);
        self::assertSame('noreply@example.com', $mailer->From);
        self::assertSame('Koabana', $mailer->FromName);
    }

    public function testCredentialsAreIgnoredWithoutAuth(): void
    {
        $this->setEnv('SMTP_HOST', 'mailpit');
        $this->setEnv('SMTP_AUTH', '0');
        $this->setEnv('SMTP_USER', 'ada');

        self::assertSame('', new MailerFactory()->create()->Username);
    }

    #[DataProvider('booleans')]
    public function testBooleanValues(string $value, bool $expected): void
    {
        $this->setEnv('SMTP_HOST', 'mailpit');
        $this->setEnv('SMTP_AUTH', $value);

        self::assertSame($expected, new MailerFactory()->create()->SMTPAuth);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function booleans(): iterable
    {
        foreach (['1', 'true', 'YES', 'on'] as $value) {
            yield $value => [$value, true];
        }

        foreach (['0', 'false', 'no', 'off'] as $value) {
            yield $value => [$value, false];
        }
    }
}
