<?php

namespace GP247\Front\Tests\Feature\Seo;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use GP247\Front\Tests\Feature\Seo\Support\InteractsWithFrontStore;
use GP247\Front\Tests\TestCase;

/**
 * Feature tests for GET /robots.txt.
 *
 * Verifies that robots.txt is served as plain text with the correct
 * structure (User-agent, Disallow, Sitemap pointer).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-004
 */
class RobotsTest extends TestCase
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

    public function test_robots_returns_status_200(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
    }

    public function test_robots_content_type_is_plain_text(): void
    {
        $response = $this->get('/robots.txt');

        // Laravel may return text/plain or text/plain;charset=UTF-8
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
    }

    public function test_robots_contains_user_agent_line(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSee('User-agent:', escape: false);
    }

    public function test_robots_contains_sitemap_reference(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertSee('Sitemap:', escape: false);
        $response->assertSee('sitemap.xml', escape: false);
    }

    public function test_robots_uses_adminconfig_when_set(): void
    {
        \GP247\Core\Models\AdminConfig::updateOrCreate(
            ['key' => 'seo.robots_txt', 'store_id' => config('app.storeId')],
            [
                'value'    => "User-agent: *\nDisallow: /admin/\n# custom robots",
                'group'    => 'seo',
                'code'     => 'seo.robots_txt',
                'security' => 0,
                'sort'     => 0,
                'detail'   => '',
            ]
        );

        // Clear static cache so controller picks up the new value
        $reflection = new \ReflectionProperty(\GP247\Core\Models\AdminConfig::class, 'getAllConfigOfStore');
        // WHY []: declared as an array and read via array_key_exists(); null makes later gp247_config() calls throw.
        $reflection->setValue(null, []);

        $response = $this->get('/robots.txt');

        $response->assertSee('# custom robots', escape: false);
    }
}
