<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Form\TextInput;
use Koabana\Form\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // --- Résultat global de validate() ---

    public function testValidateReturnsTrueWhenAllFieldsAreValid(): void
    {
        $field = $this->field(['required' => true, 'email' => true], 'ada@example.com');

        self::assertTrue($this->validator->validate([$field]));
        self::assertSame([], $field->getErrors());
    }

    public function testValidateReturnsFalseWhenAFieldIsInvalid(): void
    {
        $field = $this->field(['email' => true], 'pas-un-email');

        self::assertFalse($this->validator->validate([$field]));
    }

    public function testValidateReturnsFalseWhenOnlyOneFieldOfSeveralIsInvalid(): void
    {
        $valid = $this->field(['required' => true], 'Ada');
        $invalid = $this->field(['required' => true], '');

        self::assertFalse($this->validator->validate([$valid, $invalid]));
    }

    public function testValidatingTwiceDoesNotDuplicateErrors(): void
    {
        $field = $this->field(['required' => true], '');

        $this->validator->validate([$field]);
        $this->validator->validate([$field]);

        self::assertCount(1, $field->getErrors());
    }

    public function testErrorsAreClearedOnceTheValueIsCorrected(): void
    {
        $field = $this->field(['required' => true], '');
        $this->validator->validate([$field]);

        $field->setValue('Ada');

        self::assertTrue($this->validator->validate([$field]));
        self::assertSame([], $field->getErrors());
    }

    #[DataProvider('emptyValues')]
    public function testRequiredFieldWithEmptyValueIsInvalid(mixed $value): void
    {
        $field = $this->field(['required' => true], $value);

        self::assertFalse($this->validator->validate([$field]));
        self::assertSame(['Ce champ est requis.'], $field->getErrors());
    }

    // --- Règle required ---

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyValues(): iterable
    {
        yield 'chaîne vide' => [''];

        yield 'null' => [null];
    }

    #[DataProvider('zeroValues')]
    public function testZeroIsAValidRequiredValue(mixed $value): void
    {
        $field = $this->field(['required' => true], $value);

        self::assertTrue($this->validator->validate([$field]));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function zeroValues(): iterable
    {
        yield 'chaîne "0"' => ['0'];

        yield 'entier 0' => [0];
    }

    public function testRequiredErrorStopsOtherChecks(): void
    {
        $field = $this->field(['required' => true, 'email' => true, 'minLength' => 5], '');

        $this->validator->validate([$field]);

        self::assertSame(['Ce champ est requis.'], $field->getErrors());
    }

    public function testOptionalEmptyFieldSkipsOtherChecks(): void
    {
        $field = $this->field(['email' => true, 'minLength' => 5], '');

        self::assertTrue($this->validator->validate([$field]));
    }

    /**
     * @param array<string, mixed> $rules
     */
    #[DataProvider('rules')]
    public function testRule(array $rules, mixed $value, bool $expectedValid, ?string $expectedError): void
    {
        $field = $this->field($rules, $value);

        self::assertSame($expectedValid, $this->validator->validate([$field]));
        self::assertSame(null === $expectedError ? [] : [$expectedError], $field->getErrors());
    }

    // --- Règles de format et de bornes ---

    /**
     * @return iterable<string, array{array<string, mixed>, mixed, bool, ?string}>
     */
    public static function rules(): iterable
    {
        yield 'email valide' => [['email' => true], 'ada@example.com', true, null];

        yield 'email invalide' => [['email' => true], 'ada@', false, "Format d'email invalide."];

        yield 'minLength atteint' => [['minLength' => 3], 'Ada', true, null];

        yield 'minLength manqué' => [['minLength' => 3], 'Al', false, 'Minimum 3 caractères requis.'];

        yield 'minLength compte les caractères accentués' => [['minLength' => 3], 'éé', false, 'Minimum 3 caractères requis.'];

        yield 'maxLength respecté' => [['maxLength' => 3], 'Ada', true, null];

        yield 'maxLength dépassé' => [['maxLength' => 3], 'Adam', false, 'Maximum 3 caractères.'];

        yield 'maxLength compte les caractères accentués' => [['maxLength' => 3], 'Éloï', false, 'Maximum 3 caractères.'];

        yield 'maxLength accepte 3 caractères accentués' => [['maxLength' => 3], 'éàü', true, null];

        yield 'min atteint' => [['min' => 18], '18', true, null];

        yield 'min manqué' => [['min' => 18], '17', false, 'La valeur doit être au minimum 18.'];

        yield 'max respecté' => [['max' => 99], '99', true, null];

        yield 'max dépassé' => [['max' => 99], '100', false, 'La valeur ne doit pas dépasser 99.'];

        yield 'regex respectée' => [['regex' => '/^[0-9]{5}$/'], '16400', true, null];

        yield 'regex non respectée' => [['regex' => '/^[0-9]{5}$/'], '1640', false, "Le format n'est pas valide."];
    }

    public function testSeveralRulesCanFailTogether(): void
    {
        $field = $this->field(['email' => true, 'minLength' => 10], 'a@b');

        $this->validator->validate([$field]);

        self::assertSame(["Format d'email invalide.", 'Minimum 10 caractères requis.'], $field->getErrors());
    }

    /**
     * @param array<string, mixed> $rules
     */
    private function field(array $rules, mixed $value): TextInput
    {
        $field = new TextInput('champ', $rules);
        $field->setValue($value);

        return $field;
    }
}
