<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Attributes\After;

/**
 * Modifie des variables d'environnement le temps d'un test, puis les restaure.
 */
trait WithEnv
{
    /** @var array<string, false|string> valeurs d'origine, avant le premier changement */
    private array $savedEnv = [];

    /**
     * @param null|string $value null pour supprimer la variable
     */
    protected function setEnv(string $name, ?string $value): void
    {
        if (!\array_key_exists($name, $this->savedEnv)) {
            $this->savedEnv[$name] = getenv($name);
        }

        putenv(null === $value ? $name : "{$name}={$value}");
    }

    #[After]
    protected function restoreEnv(): void
    {
        foreach ($this->savedEnv as $name => $value) {
            putenv(false === $value ? $name : "{$name}={$value}");
        }

        $this->savedEnv = [];
    }
}
