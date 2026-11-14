<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Model\Entity\UserEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Champs obligatoires de l'utilisateur.
 *
 * @internal
 *
 * @coversNothing
 */
final class UserEntityTest extends TestCase
{
    #[DataProvider('requiredFields')]
    public function testRequiredFieldCannotBeBlank(string $setter, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new UserEntity()->{$setter}('   ');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function requiredFields(): iterable
    {
        yield 'prénom' => ['setFirstname', 'Le prénom ne peut pas être vide'];

        yield 'nom' => ['setLastname', 'Le nom ne peut pas être vide'];

        yield 'e-mail' => ['setEmail', "L'email ne peut pas être vide"];
    }
}
