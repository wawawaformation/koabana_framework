<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Objet simple lié à un formulaire (bind / getEntity) dans FormTest.
 */
final class ContactEntity
{
    public function __construct(private string $nom, private string $email) {}

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
