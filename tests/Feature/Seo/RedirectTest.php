<?php

namespace GP247\Front\Tests\Feature\Seo;

use GP247\Front\Models\FrontRedirect;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for 301/302 redirect middleware (FrontRedirectMiddleware).
 *
 * Verifies that requests matching a FrontRedirect record are redirected
 * with the correct HTTP status code.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-006
 */
class RedirectTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithFrontStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontStore();
    }

    protected function tearDown(): void
    {
        $this->tearDownFrontStore();
        parent::tearDown();
    }

    public function test_301_redirect_from_old_url_to_new_url(): void
    {
        FrontRedirect::create([
            'from'     => '/old-page.html',
            'to'       => '/new-page.html',
            'code'     => 301,
            'store_id' => config('app.storeId'),
            'status'   => 1,
        ]);

        $response = $this->get('/old-page.html');

        $response->assertRedirect('/new-page.html');
        $response->assertStatus(301);
    }

    public function test_302_redirect_works(): void
    {
        FrontRedirect::create([
            'from'     => '/temp-old.html',
            'to'       => '/temp-new.html',
            'code'     => 302,
            'store_id' => config('app.storeId'),
            'status'   => 1,
        ]);

        $response = $this->get('/temp-old.html');

        $response->assertRedirect('/temp-new.html');
        $response->assertStatus(302);
    }

    public function test_inactive_redirect_is_ignored(): void
    {
        FrontRedirect::create([
            'from'     => '/disabled-redirect.html',
            'to'       => '/somewhere.html',
            'code'     => 301,
            'store_id' => config('app.storeId'),
            'status'   => 0,
        ]);

        // Should fall through to the catch-all route (404 or page not found)
        $response = $this->get('/disabled-redirect.html');

        $this->assertNotEquals(301, $response->getStatusCode());
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    public function test_unknown_url_is_not_redirected(): void
    {
        $response = $this->get('/zz-no-redirect-for-this.html');

        // Must not be a redirect
        $this->assertNotContains($response->getStatusCode(), [301, 302]);
    }
}
