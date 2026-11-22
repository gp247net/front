<?php

namespace GP247\Front\Tests\Feature\FrontendTemplateDev;

use GP247\Core\Models\AdminStore;
use GP247\Front\Support\ResolvesTemplateView;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use GP247\Front\Tests\TestCase;

/**
 * `Default` was removed entirely from the project (ADR-014 Amend #1) — `GP247Front` is now the sole/default
 * template. Two independent fallback points had to be changed together:
 * `GP247_TEMPLATE_FRONT_DEFAULT` (vendor/gp247/front/src/Config/config.php)
 * and the separate hardcoded fallback in `ResolvesTemplateView::activeTemplateName()`.
 *
 * @aidlc-unit frontend-template-dev
 * @aidlc-story US-TPL-009
 * @aidlc-adr ADR-014
 */
class DefaultTemplateRemovedTest extends TestCase
{
    use DatabaseTransactions;

    public function test_gp247_template_front_default_constant_is_gp247front(): void
    {
        $this->assertTrue(defined('GP247_TEMPLATE_FRONT_DEFAULT'));
        $this->assertSame('GP247Front', GP247_TEMPLATE_FRONT_DEFAULT);
    }

    public function test_store_info_template_falls_back_to_gp247front_when_column_is_null(): void
    {
        $store = AdminStore::create([
            'id' => (string) Str::uuid(),
            'code' => 'zzdefrm_'.Str::random(8),
            'template' => null,
            'language' => 'en',
            'currency' => 'USD',
            'status' => 1,
            'active' => 1,
        ]);
        $this->resetAdminStoreCache();

        $this->assertSame('GP247Front', gp247_store_info('template', null, $store->id));
    }

    public function test_resolves_template_view_fallback_is_gp247front_not_default(): void
    {
        $store = AdminStore::create([
            'id' => (string) Str::uuid(),
            'code' => 'zzdefrm2_'.Str::random(8),
            'template' => null,
            'language' => 'en',
            'currency' => 'USD',
            'status' => 1,
            'active' => 1,
        ]);
        $this->resetAdminStoreCache();

        // WHY: ResolvesTemplateView::activeTemplateName() calls
        // gp247_store_info('template', 'Default') with an EXPLICIT default
        // that historically bypassed the GP247_TEMPLATE_FRONT_DEFAULT
        // constant entirely — had to be changed independently, otherwise
        // this trait still resolved the removed 'Default' template.
        config(['app.storeId' => $store->id]);
        $consumer = new class {
            use ResolvesTemplateView;

            public function activeTemplate(): string
            {
                return $this->activeTemplateName();
            }
        };

        $this->assertSame('GP247Front', $consumer->activeTemplate());
    }

    /**
     * @return void
     */
    private function resetAdminStoreCache(): void
    {
        $prop = new \ReflectionProperty(AdminStore::class, 'getAll');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }
}
