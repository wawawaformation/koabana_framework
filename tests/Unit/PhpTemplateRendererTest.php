<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\View\PhpTemplateRenderer;
use Koabana\View\ViewContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Rendu de templates PHP, avec ou sans layout, dans un dossier temporaire.
 *
 * @internal
 *
 * @coversNothing
 */
final class PhpTemplateRendererTest extends TestCase
{
    private string $dir;
    private PhpTemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/koabana-tpl-'.bin2hex(random_bytes(4));
        mkdir($this->dir.'/layout', 0o777, true);
        mkdir($this->dir.'/pages');

        $this->write('layout/main', '<main><?= $view->section("title") ?>|<?= $content ?></main>');
        $this->write('pages/hello', '<?php $view->start("title") ?>Titre<?php $view->end("title") ?>Bonjour <?= $view->e($name) ?>');
        $this->write('pages/vars', '<?= json_encode([$data, $template, $file, $view instanceof Koabana\View\ViewContext]) ?>');
        $this->write('pages/crash', '<p>début</p><?php throw new RuntimeException("boum");');
        $this->write('pages/crash-in-section', '<?php $view->start("title") ?>Titre<?php throw new RuntimeException("boum");');

        $this->renderer = new PhpTemplateRenderer($this->dir.'/');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*/*.php') ?: []);
        rmdir($this->dir.'/layout');
        rmdir($this->dir.'/pages');
        rmdir($this->dir);
    }

    public function testTemplateIsRenderedInsideTheLayout(): void
    {
        self::assertSame('<main>Titre|Bonjour Ada &amp; Bob</main>', $this->renderer->render('pages/hello', ['name' => 'Ada & Bob']));
    }

    public function testTemplateCanBeRenderedWithoutLayout(): void
    {
        self::assertSame('Bonjour Ada', $this->renderer->render('/pages/hello', ['name' => 'Ada'], null));
    }

    public function testViewContextIsShared(): void
    {
        $view = new ViewContext();

        self::assertSame($view, new PhpTemplateRenderer($this->dir, $view)->view());
    }

    public function testMissingTemplateIsReported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        $this->renderer->render('pages/absent');
    }

    public function testDataKeysAreNotShadowedByRendererVariables(): void
    {
        // data, template et file ne doivent pas être remplacés par des variables internes
        $html = $this->renderer->render('pages/vars', ['data' => 'D', 'template' => 'T', 'file' => 'F', 'view' => 'ignoré'], null);

        self::assertSame('["D","T","F",true]', $html);
    }

    #[DataProvider('crashingTemplates')]
    public function testCrashingTemplateLeavesNoOpenOutputBuffer(string $template): void
    {
        $level = ob_get_level();

        try {
            $this->renderer->render($template);
            self::fail('Une exception était attendue.');
        } catch (\RuntimeException $e) {
            self::assertSame('boum', $e->getMessage());
        }

        // Un tampon resté ouvert ferait fuiter du HTML partiel dans la réponse
        self::assertSame($level, ob_get_level());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function crashingTemplates(): iterable
    {
        yield 'exception dans le template' => ['pages/crash'];

        yield 'exception dans une section ouverte' => ['pages/crash-in-section'];
    }

    #[DataProvider('traversals')]
    public function testParentDirectorySegmentsAreRejected(string $template): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->renderer->render($template);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function traversals(): iterable
    {
        yield 'en tête' => ['../public/index'];

        yield 'au milieu' => ['pages/../../public/index'];

        yield 'séparateur Windows' => ['pages\..\..\public\index'];
    }

    private function write(string $name, string $content): void
    {
        file_put_contents($this->dir.'/'.$name.'.php', $content);
    }
}
