<?php

declare(strict_types=1);

namespace Koabana\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Fabrique d'instances PHPMailer configurees via variables d'environnement.
 */
final class MailerFactory
{
    public function __construct() {}

    /**
     * @return PHPMailer
     */
    public function create(): PHPMailer
    {
        $host = getenv('SMTP_HOST');

        if (false === $host || '' === $host) {
            throw new \RuntimeException('Hote SMTP non configure.');
        }

        $port = $this->intEnv('SMTP_PORT', 25);
        $auth = $this->boolEnv('SMTP_AUTH', false);
        $secure = (string) (getenv('SMTP_SECURE') ?: '');
        $autoTls = $this->boolEnv('SMTP_AUTO_TLS', true);
        $username = (string) (getenv('SMTP_USER') ?: '');
        $password = (string) (getenv('SMTP_PASSWORD') ?: '');
        $debug = $this->intEnv('SMTP_DEBUG', 0);
        $fromEmail = (string) (getenv('SMTP_FROM_EMAIL') ?: '');
        $fromName = (string) (getenv('SMTP_FROM_NAME') ?: '');

        $mailer = new PHPMailer(true);
        $mailer->CharSet = 'UTF-8';
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = $auth;
        $mailer->SMTPAutoTLS = $autoTls;
        if ('' !== $secure) {
            $mailer->SMTPSecure = $secure;
        }

        if ($auth) {
            $mailer->Username = $username;
            $mailer->Password = $password;
        }

        $mailer->SMTPDebug = $debug;

        if ('' !== $fromEmail) {
            $mailer->setFrom($fromEmail, $fromName);
        }

        return $mailer;
    }

    private function boolEnv(string $key, bool $default): bool
    {
        $value = getenv($key);

        if (false === $value || '' === $value) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private function intEnv(string $key, int $default): int
    {
        $value = getenv($key);

        if (false === $value || '' === $value) {
            return $default;
        }

        return (int) $value;
    }
}
