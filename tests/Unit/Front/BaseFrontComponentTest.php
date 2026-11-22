<?php

namespace GP247\Front\Tests\Unit\Front;

use GP247\Front\Livewire\BaseFrontComponent;
use Illuminate\Support\Facades\View;
use GP247\Front\Tests\TestCase;
use GP247\Front\Tests\Unit\Front\Support\StubFrontComponent;

/**
 * Unit tests for BaseFrontComponent — the headless base class that resolves
 * a Livewire component's view through the active template first, falling
 * back to the package default (ADR-011, US-TPL-004/005).
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-004, US-TPL-005
 * @aidlc-adr ADR-011
 */
class BaseFrontComponentTest extends TestCase
{
    private string $tempTemplatesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempTemplatesDir = sys_get_temp_dir() . '/gp247_base_front_component_test_' . uniqid();
        mkdir($this->tempTemplatesDir . '/TplWithOverride/livewire', 0777, true);
        file_put_contents(
            $this->tempTemplatesDir . '/TplWithOverride/livewire/stub-component.blade.php',
            '<div>template-override</div>'
        );

        // Additive: appends a search path to the existing 'GP247TemplatePath'
        // namespace (registered by FrontServiceProvider) without touching the
        // real app/GP247/Templates directory.
        View::addNamespace('GP247TemplatePath', $this->tempTemplatesDir);

        // Fallback view namespace, standing in for a package's own view (e.g. gp247-shop-front).
        View::addNamespace('gp247-stub-front', __DIR__ . '/Support/views');
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempTemplatesDir);

        parent::tearDown();
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    // -------------------------------------------------------------------------
    // Class contract
    // -------------------------------------------------------------------------

    public function test_is_abstract_and_extends_livewire_component(): void
    {
        $ref = new \ReflectionClass(BaseFrontComponent::class);

        $this->assertTrue($ref->isAbstract());
        $this->assertTrue($ref->isSubclassOf(\Livewire\Component::class));
    }

    // -------------------------------------------------------------------------
    // Fallback (no template override)
    // -------------------------------------------------------------------------

    public function test_falls_back_to_default_view_when_template_has_no_override(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = 'TemplateWithoutOverride';

        $this->assertSame(
            'gp247-stub-front::livewire.stub-component',
            $component->resolvedViewForTest()
        );
    }

    public function test_falls_back_to_default_view_when_active_template_is_empty_string(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = '';

        $this->assertSame(
            'gp247-stub-front::livewire.stub-component',
            $component->resolvedViewForTest()
        );
    }

    // -------------------------------------------------------------------------
    // Template override
    // -------------------------------------------------------------------------

    public function test_resolves_template_override_view_when_it_exists(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = 'TplWithOverride';

        $this->assertSame(
            'GP247TemplatePath::TplWithOverride.livewire.stub-component',
            $component->resolvedViewForTest()
        );
    }

    // -------------------------------------------------------------------------
    // render()
    // -------------------------------------------------------------------------

    public function test_render_returns_a_view_instance_using_resolved_view(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = 'TemplateWithoutOverride';

        $view = $component->render();

        $this->assertInstanceOf(\Illuminate\View\View::class, $view);
        $this->assertSame('gp247-stub-front::livewire.stub-component', $view->name());
    }

    public function test_render_uses_template_override_view_when_active(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = 'TplWithOverride';

        $view = $component->render();

        $this->assertSame('GP247TemplatePath::TplWithOverride.livewire.stub-component', $view->name());
    }

    public function test_render_passes_view_data_through(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = 'TemplateWithoutOverride';
        $component->forcedViewData = ['count' => 42, 'label' => 'cart'];

        $view = $component->render();

        $this->assertSame(42, $view->getData()['count']);
        $this->assertSame('cart', $view->getData()['label']);
    }

    public function test_render_html_reflects_template_override_content(): void
    {
        $component = new StubFrontComponent();
        $component->forcedTemplate = 'TplWithOverride';

        $html = $component->render()->render();

        $this->assertStringContainsString('template-override', $html);
        $this->assertStringNotContainsString('default-view', $html);
    }
}
