<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Form\Form;
use Koabana\Form\TextInput;
use PHPUnit\Framework\TestCase;
use Tests\Support\ContactEntity;

/**
 * Orchestration du formulaire : champs, données, validation, entité et rendu.
 *
 * @internal
 *
 * @coversNothing
 */
final class FormTest extends TestCase
{
    // --- Gestion des champs ---

    public function testFieldsCanBeAddedAndRetrievedByName(): void
    {
        $form = new Form('contact');
        $nom = new TextInput('nom');
        $form->add($nom);

        self::assertSame($nom, $form->field('nom'));
        self::assertNull($form->field('absent'));
        self::assertSame(['nom' => $nom], $form->getFields());
    }

    public function testFillSetsValuesAndNullsMissingOnes(): void
    {
        $form = $this->form();

        $form->fill(['nom' => 'Ada', 'inconnu' => 'ignoré']);

        self::assertSame(['nom' => 'Ada', 'email' => null], $form->getData());
    }

    // --- Validation et erreurs ---

    public function testValidFormHasNoErrors(): void
    {
        $form = $this->form();
        $form->fill(['nom' => 'Ada', 'email' => 'ada@example.com']);

        self::assertTrue($form->validate());
        self::assertSame([], $form->errors());
    }

    public function testInvalidFormReportsErrorsByField(): void
    {
        $form = $this->form();
        $form->fill(['nom' => '', 'email' => 'ada@example.com']);

        self::assertFalse($form->validate());
        self::assertSame(['nom' => ['Ce champ est requis.']], $form->errors());
        self::assertSame(['Ce champ est requis.'], $form->errors('nom'));
        self::assertSame([], $form->errors('email'));
        self::assertSame([], $form->errors('absent'));
    }

    public function testAddErrorTargetsAnExistingFieldOnly(): void
    {
        $form = $this->form();

        $form->addError('email', 'Adresse déjà utilisée.');
        $form->addError('absent', 'Ignorée.');

        self::assertSame(['email' => ['Adresse déjà utilisée.']], $form->errors());
    }

    // --- Liaison avec une entité ---

    public function testGetEntityReturnsNullWithoutBinding(): void
    {
        self::assertNull($this->form()->getEntity());
    }

    public function testBindHydratesFieldsFromGetters(): void
    {
        $form = $this->form();

        $form->bind($this->entity('Ada', 'ada@example.com'));

        self::assertSame(['nom' => 'Ada', 'email' => 'ada@example.com'], $form->getData());
    }

    public function testGetEntityWritesFieldValuesThroughSetters(): void
    {
        $entity = $this->entity('Ada', 'ada@example.com');
        $form = $this->form();
        $form->bind($entity);
        $form->fill(['nom' => 'Grace', 'email' => 'grace@example.com']);

        self::assertSame($entity, $form->getEntity());
        self::assertSame('Grace', $entity->getNom());
        self::assertSame('grace@example.com', $entity->getEmail());
    }

    public function testFromEntityBuildsAndHydratesTheForm(): void
    {
        $entity = $this->entity('Ada', 'ada@example.com');

        $form = Form::fromEntity('contact', $entity, function (Form $form): void {
            $form->add(new TextInput('nom'));
        });

        self::assertSame(['nom' => 'Ada'], $form->getData());
        self::assertSame($entity, $form->getEntity());
    }

    public function testCreateFromRequestKeepsPostedDataForFieldsAddedAfterwards(): void
    {
        // Usage documenté dans le README : création depuis la requête, puis ajout des champs
        $request = new ServerRequest('POST', '/contact')->withParsedBody(['nom' => 'Ada']);

        $form = Form::createFromRequest('contact', $request);
        $form->add(new TextInput('nom'));
        $form->add(new TextInput('email'));

        self::assertSame(['nom' => 'Ada', 'email' => null], $form->getData());
    }

    // --- Rendu ---

    public function testCsrfIsEmptyWithoutToken(): void
    {
        self::assertSame('', $this->form()->csrf());
    }

    public function testCsrfRendersAnEscapedHiddenInput(): void
    {
        $form = $this->form();
        $form->setCsrfToken('abc"123');

        self::assertSame('<input type="hidden" name="_csrf_token" value="abc&quot;123">', $form->csrf());
    }

    public function testOpenRendersMethodActionAndAttributes(): void
    {
        $html = $this->form()->open('/contact', 'post', [
            'class' => 'a"b',
            'novalidate' => true,
            'hidden' => false,
        ]);

        self::assertSame('<form name="contact" method="POST" action="/contact" class="a&quot;b" novalidate>', $html);
    }

    public function testOpenWithoutActionOmitsTheAttribute(): void
    {
        self::assertSame('<form name="contact" method="POST">', $this->form()->open());
    }

    public function testCloseRendersTheClosingTag(): void
    {
        self::assertSame('</form>', $this->form()->close());
    }

    private function form(): Form
    {
        $form = new Form('contact');
        $form->add(new TextInput('nom', ['required' => true]));
        $form->add(new TextInput('email', ['email' => true]));

        return $form;
    }

    /**
     * Petite entité de test avec getters et setters.
     */
    private function entity(string $nom, string $email): ContactEntity
    {
        return new ContactEntity($nom, $email);
    }
}
