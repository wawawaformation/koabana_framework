<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Form\Checkbox;
use Koabana\Form\EmailInput;
use Koabana\Form\PasswordInput;
use Koabana\Form\Select;
use Koabana\Form\Textarea;
use Koabana\Form\TextInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Rendu HTML et accesseurs des champs de formulaire.
 *
 * @internal
 *
 * @coversNothing
 */
final class FormFieldsTest extends TestCase
{
    // --- Accesseurs de Field ---

    public function testRulesAreExtractedFromAttributes(): void
    {
        $field = new TextInput('nom', ['required' => true, 'minLength' => 2, 'class' => 'x']);

        self::assertSame(['required' => true, 'minLength' => 2], $field->getRules());
        self::assertTrue($field->hasRule('minLength'));
        self::assertFalse($field->hasRule('email'));
        self::assertSame(2, $field->getRule('minLength'));
        self::assertNull($field->getRule('email'));
    }

    public function testAttributesAreKeptAsGiven(): void
    {
        $field = new TextInput('nom', ['class' => 'x', 'required' => true]);

        self::assertSame(['class' => 'x', 'required' => true], $field->getAttributes());
        self::assertSame('x', $field->getAttribute('class'));
        self::assertNull($field->getAttribute('absent'));
    }

    public function testErrorsCanBeAddedAndCleared(): void
    {
        $field = new TextInput('nom');
        $field->addError('Erreur');

        self::assertTrue($field->hasErrors());
        self::assertSame(['Erreur'], $field->getErrors());

        $field->clearErrors();

        self::assertFalse($field->hasErrors());
    }

    // --- Attributs HTML communs (testés via TextInput) ---

    public function testValidationRulesBecomeHtmlAttributes(): void
    {
        $field = new TextInput('age', [
            'required' => true,
            'minLength' => 1,
            'maxLength' => 3,
            'min' => 18,
            'max' => 99,
            'regex' => '/^[0-9]+$/',
        ]);

        self::assertSame(
            '<input type="text" name="age" value="" required minlength="1" maxlength="3" min="18" max="99" pattern="^[0-9]+$">',
            $field->render(),
        );
    }

    public function testOtherAttributesAreRenderedAndEscaped(): void
    {
        $field = new TextInput('nom', [
            'class' => 'form-control',
            'placeholder' => '"Ada" <Lovelace>',
            'autofocus' => true,
            'disabled' => false,
        ]);

        self::assertSame(
            '<input type="text" name="nom" value="" class="form-control" placeholder="&quot;Ada&quot; &lt;Lovelace&gt;" autofocus>',
            $field->render(),
        );
    }

    public function testNonHtmlKeysAreNotRendered(): void
    {
        $field = new TextInput('nom', ['label' => 'Nom', 'infos' => 'Aide', 'match' => 'autre']);

        self::assertSame('<input type="text" name="nom" value="">', $field->render());
    }

    // --- Rendu propre à chaque type ---

    public function testTextInputEscapesItsValue(): void
    {
        $field = new TextInput('nom');
        $field->setValue('<script>"x"</script>');

        self::assertSame(
            '<input type="text" name="nom" value="&lt;script&gt;&quot;x&quot;&lt;/script&gt;">',
            $field->render(),
        );
    }

    public function testTextInputWithEmailRuleIsAnEmailInput(): void
    {
        $field = new TextInput('courriel', ['email' => true]);

        self::assertSame('<input type="email" name="courriel" value="">', $field->render());
    }

    public function testEmailInputRendersAnEscapedEmailInput(): void
    {
        $field = new EmailInput('email', ['required' => true]);
        $field->setValue('a"b@example.com');

        self::assertSame(
            '<input type="email" name="email" value="a&quot;b@example.com" required>',
            $field->render(),
        );
    }

    public function testPasswordInputNeverRendersItsValue(): void
    {
        $field = new PasswordInput('password', ['required' => true]);
        $field->setValue('secret');

        self::assertSame('<input type="password" name="password" required>', $field->render());
    }

    public function testTextareaEscapesItsContent(): void
    {
        $field = new Textarea('message', ['rows' => 5]);
        $field->setValue('</textarea><b>');

        self::assertSame(
            '<textarea name="message" rows="5">&lt;/textarea&gt;&lt;b&gt;</textarea>',
            $field->render(),
        );
    }

    #[DataProvider('checkboxValues')]
    public function testCheckboxIsCheckedOnlyForTruthyValues(mixed $value, bool $checked): void
    {
        $field = new Checkbox('cgu');
        $field->setValue($value);

        $expected = '<input type="checkbox" name="cgu" value="1"'.($checked ? ' checked' : '').'>';
        self::assertSame($expected, $field->render());
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function checkboxValues(): iterable
    {
        yield 'valeur postée "1"' => ['1', true];

        yield 'true' => [true, true];

        yield 'non cochée (null)' => [null, false];

        yield 'chaîne "0"' => ['0', false];
    }

    public function testSelectRendersEscapedOptions(): void
    {
        $field = new Select('pays', ['fr' => 'France', 'x"y' => '<Autre>'], ['required' => true]);

        self::assertSame(['fr' => 'France', 'x"y' => '<Autre>'], $field->getOptions());
        self::assertSame(
            '<select name="pays" required><option value="fr">France</option><option value="x&quot;y">&lt;Autre&gt;</option></select>',
            $field->render(),
        );
    }

    public function testSelectMarksTheCurrentValueAsSelected(): void
    {
        $field = new Select('pays', ['fr' => 'France', 'de' => 'Allemagne']);
        $field->setValue('de');

        self::assertStringContainsString('<option value="de" selected>', $field->render());
        self::assertStringNotContainsString('<option value="fr" selected>', $field->render());
    }

    public function testSelectWithNumericKeysSelectsThePostedStringValue(): void
    {
        // Les clés numériques d'un tableau PHP deviennent des entiers,
        // alors qu'une valeur postée est toujours une chaîne
        $field = new Select('note', [1 => 'Un', 2 => 'Deux']);
        $field->setValue('2');

        self::assertStringContainsString('<option value="2" selected>', $field->render());
    }

    public function testRegexWithFlagsIsNotRenderedAsHtmlPattern(): void
    {
        // Exemple tiré du README du module Form : le drapeau i n'existe pas en HTML,
        // le navigateur refuserait donc les majuscules que le serveur accepte
        $field = new TextInput('code', ['regex' => '/^[a-z]+$/i']);

        self::assertSame('<input type="text" name="code" value="">', $field->render());
    }

    public function testSelectWithoutValueSelectsNothing(): void
    {
        $field = new Select('note', [0 => 'Zéro', '' => 'Aucune']);

        self::assertStringNotContainsString('selected', $field->render());
    }
}
