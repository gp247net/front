<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Front\Admin\Livewire\SeoNotFoundLog;
use GP247\Front\Library\NotFoundLog;
use GP247\Front\Models\FrontNotFoundLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use GP247\Core\Testing\InteractsWithAdminUser;
use GP247\Front\Tests\Feature\Seo\Support\ConfiguresSeoStore;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * With the 404 log switched on, the site owner sees which storefront addresses fail
 * and can redirect them (SEO phase 2).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-not-found-log, US-SEO-006
 * @aidlc-adr ADR-seo-url-lifecycle
 */
class NotFoundLogTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAdminUser;
    use InteractsWithFrontStore;
    use ConfiguresSeoStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSeoConfigCache();
        $this->setUpFrontStore();
    }

    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        parent::tearDown();
    }

    public function test_nothing_is_logged_while_the_log_is_off(): void
    {
        $this->get('/zz-missing-off-' . uniqid() . '.html')->assertNotFound();

        $this->assertSame(0, FrontNotFoundLog::where('path', 'like', '/zz-missing-off-%')->count());
    }

    public function test_a_missing_page_is_counted_once_per_address_with_the_last_referrer(): void
    {
        $this->turnOn();
        $path = '/zz-missing-' . uniqid() . '.html';

        $this->get($path . '?utm_source=x')->assertNotFound();
        $this->get($path, ['Referer' => 'https://ref.example.test/from'])->assertNotFound();

        $row = FrontNotFoundLog::where('path', $path)->first();
        $this->assertNotNull($row);
        $this->assertSame(2, (int) $row->hits);
        $this->assertSame('https://ref.example.test/from', $row->referer);
        $this->assertSame((string) config('app.storeId'), (string) $row->store_id);
    }

    public function test_an_address_that_matches_no_route_is_logged_too(): void
    {
        $this->turnOn();
        $path = '/zz/no/route/' . uniqid();

        $this->get($path)->assertNotFound();

        $this->assertSame(1, FrontNotFoundLog::where('path', $path)->count());
    }

    public function test_admin_api_assets_posts_and_found_pages_are_not_logged(): void
    {
        $this->turnOn();
        $admin = '/' . (defined('GP247_ADMIN_PREFIX') ? GP247_ADMIN_PREFIX : 'gp247_admin') . '/zz-missing-' . uniqid();
        $paths = [$admin, '/api/zz-missing-' . uniqid(), '/GP247/zz-missing-' . uniqid() . '.png', '/zz-img-' . uniqid() . '.jpg'];
        foreach ($paths as $p) {
            $this->get($p);
        }
        $this->post('/zz-post-missing-' . uniqid());
        $this->get('/');

        foreach ($paths as $p) {
            $this->assertSame(0, FrontNotFoundLog::where('path', $p)->count(), $p);
        }
        $this->assertSame(0, FrontNotFoundLog::where('path', 'like', '/zz-post-missing-%')->count());
        $this->assertSame(0, FrontNotFoundLog::where('path', '/')->count());
    }

    public function test_scanner_probes_are_not_logged_and_the_referrer_keeps_no_query(): void
    {
        $this->turnOn();
        $this->get('/wp-login.php')->assertNotFound();
        $this->get('/zz-probe-' . uniqid() . '/.env');
        $path = '/zz-ref-' . uniqid() . '.html';
        $this->get($path, ['Referer' => 'https://ref.example.test/search?q=private&email=a@b.test'])->assertNotFound();

        $this->assertSame(0, FrontNotFoundLog::where('path', '/wp-login.php')->count());
        $this->assertSame(0, FrontNotFoundLog::where('path', 'like', '%/.env')->count());
        $this->assertSame('https://ref.example.test/search', FrontNotFoundLog::where('path', $path)->value('referer'));
    }

    public function test_the_log_keeps_at_most_the_cap_of_rows_per_store(): void
    {
        $this->turnOn();
        $store = config('app.storeId');
        FrontNotFoundLog::where('store_id', $store)->delete();
        $rows = [];
        for ($i = 0; $i < NotFoundLog::MAX_ROWS; $i++) {
            $p = '/zz-cap-' . $i;
            $rows[] = ['store_id' => $store, 'path' => $p, 'path_hash' => sha1($p), 'hits' => 1, 'referer' => null,
                'first_seen_at' => now()->subDays(1), 'last_seen_at' => now()->subDays(1)->addSeconds($i)];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            FrontNotFoundLog::insert($chunk);
        }

        NotFoundLog::record($store, '/zz-cap-new', null);

        $this->assertSame(NotFoundLog::MAX_ROWS, FrontNotFoundLog::where('store_id', $store)->count());
        $this->assertSame(1, FrontNotFoundLog::where('path', '/zz-cap-new')->count());
        $this->assertSame(0, FrontNotFoundLog::where('path', '/zz-cap-0')->count());
    }

    public function test_old_rows_are_cleaned_up(): void
    {
        $store = config('app.storeId');
        FrontNotFoundLog::insert(['store_id' => $store, 'path' => '/zz-old', 'path_hash' => sha1('/zz-old'), 'hits' => 3,
            'referer' => null, 'first_seen_at' => now()->subDays(200), 'last_seen_at' => now()->subDays(100)]);

        NotFoundLog::prune($store);

        $this->assertSame(0, FrontNotFoundLog::where('path', '/zz-old')->count());
    }

    public function test_the_screen_lists_rows_escaped_and_offers_a_prefilled_redirect(): void
    {
        $this->asUser(slugs: ['admin_seo_not_found']);
        $store = config('app.storeId');
        $path = '/zz-<b>bold</b>-' . uniqid();
        NotFoundLog::record($store, $path, null);

        Livewire::test(SeoNotFoundLog::class)
            ->assertSeeHtml('data-testid="seo-not-found-list-row"')
            ->assertSeeHtml(e($path))
            ->assertDontSeeHtml('<b>bold</b>')
            ->assertSeeHtml('data-testid="seo-not-found-create-redirect"')
            ->assertSeeHtml(e(urlencode($path)));
    }

    public function test_the_screen_turns_the_log_on_and_deletes_rows(): void
    {
        $this->asUser(slugs: ['admin_seo_not_found']);
        $store = config('app.storeId');
        NotFoundLog::record($store, '/zz-delete-me', null);
        $id = FrontNotFoundLog::where('path', '/zz-delete-me')->value('id');

        Livewire::test(SeoNotFoundLog::class)
            ->set('enabled', true)
            ->call('deleteRow', $id);

        $this->resetSeoConfigCache();
        $this->assertSame('1', (string) gp247_config('seo.log_404', $store, '0'));
        $this->assertSame(0, FrontNotFoundLog::where('id', $id)->count());
    }

    public function test_the_redirect_screen_takes_the_address_from_the_log(): void
    {
        $this->asUser(slugs: ['admin_seo_redirect']);

        Livewire::withQueryParams(['from' => '/zz-from-log'])
            ->test(\GP247\Front\Admin\Livewire\SeoRedirectManager::class)
            ->assertSet('form.from', '/zz-from-log');
    }

    private function turnOn(): void
    {
        $this->setSeoConfig('seo.log_404', '1');
    }
}
