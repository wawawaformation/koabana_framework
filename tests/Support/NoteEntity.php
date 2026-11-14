<?php

declare(strict_types=1);

namespace Tests\Support;

use Koabana\Model\Entity\AbstractEntity;

/**
 * Entité minimale pour tester AbstractRepository : une propriété statique ne doit pas devenir une colonne.
 */
final class NoteEntity extends AbstractEntity
{
    public static int $instances = 0;

    private string $title = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
