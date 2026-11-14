<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * PHPMailer qui n'envoie rien : il compte les envois, ou échoue sur demande.
 * Le reste (destinataires, sujet, corps) est le vrai comportement de PHPMailer.
 */
final class SpyMailer extends PHPMailer
{
    public int $sentCount = 0;
    public bool $failOnSend = false;

    public function __construct()
    {
        parent::__construct(true);
    }

    public function send()
    {
        if ($this->failOnSend) {
            throw new Exception('Serveur SMTP indisponible.');
        }

        ++$this->sentCount;

        return true;
    }

    /**
     * @return list<string> adresses des destinataires
     */
    public function recipients(): array
    {
        return array_values(array_map(static fn (array $address): string => $address[0], $this->getToAddresses()));
    }
}
