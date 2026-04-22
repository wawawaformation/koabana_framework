# Mailer

Ce dossier fournit une fabrique `MailerFactory` qui configure PHPMailer a partir des variables d'environnement et renvoie une instance prete a l'emploi.

## Configuration (.env)

Variables prises en charge :
- `SMTP_HOST` : hote SMTP (ex: mailpit, smtp.gmail.com)
- `SMTP_PORT` : port SMTP (defaut 25)
- `SMTP_AUTH` : activer l'authentification (1/0)
- `SMTP_USER` : identifiant SMTP (si auth active)
- `SMTP_PASSWORD` : mot de passe SMTP (si auth active)
- `SMTP_SECURE` : chiffrement (tls, ssl) ou vide
- `SMTP_AUTO_TLS` : negotiation TLS automatique (1/0)
- `SMTP_DEBUG` : debug SMTP (1/0)
- `SMTP_FROM_EMAIL` : email expediteur par defaut
- `SMTP_FROM_NAME` : nom expediteur par defaut

## Injection avec le container

PHPMailer est enregistre dans le container via `MailerFactory`.
Vous pouvez l'injecter directement dans un controleur ou un service.

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ContactController extends AbstractController
{
    public function send(ServerRequestInterface $request, PHPMailer $mailer): ResponseInterface
    {
        $mailer->addAddress('contact@example.test', 'Support');
        $mailer->Subject = 'Nouveau message';
        $mailer->Body = 'Bonjour, ceci est un message.';
        $mailer->send();

        $this->addFlash($request, 'success', 'Email envoye.');

        return $this->redirect('/');
    }
}
```

## Exemples concrets

### Email texte + HTML + reply-to

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class NewsletterController extends AbstractController
{
    public function send(ServerRequestInterface $request, PHPMailer $mailer): ResponseInterface
    {
        $mailer->addAddress('client@example.test', 'Client');
        $mailer->addReplyTo('support@example.test', 'Support');

        $mailer->Subject = 'Votre facture est disponible';
        $mailer->isHTML(true);
        $mailer->Body = '<h1>Merci</h1><p>Votre facture est prete.</p>';
        $mailer->AltBody = 'Merci. Votre facture est prete.';
        $mailer->send();

        $this->addFlash($request, 'success', 'Email envoye.');

        return $this->redirect('/');
    }
}
```

### Email avec piece jointe

```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class InvoiceController extends AbstractController
{
    public function send(ServerRequestInterface $request, PHPMailer $mailer): ResponseInterface
    {
        $mailer->addAddress('client@example.test', 'Client');
        $mailer->Subject = 'Votre facture';
        $mailer->Body = 'Veuillez trouver votre facture en piece jointe.';

        $filePath = dirname(__DIR__, 2) . '/var/invoices/invoice_2026_0001.pdf';
        $mailer->addAttachment($filePath, 'facture_2026_0001.pdf');

        $mailer->send();

        $this->addFlash($request, 'success', 'Email envoye.');

        return $this->redirect('/');
    }
}
```

## Remarques

- L'instance est configuree en SMTP et reutilisable a chaque injection.
- Si `SMTP_FROM_EMAIL` est defini, il est applique via `setFrom()`.
