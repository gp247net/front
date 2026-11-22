<?php

namespace GP247\Front\Tests\Feature\FrontendTemplateDev;

use GP247\Core\Models\Languages;
use GP247\Front\Models\FrontLayoutBlock;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use GP247\Front\Tests\TestCase;

/**
 * The LayoutBlock position registry offers only positions a template renders.
 *
 * The head-code position was registered as 'top_site' while the sole call that
 * reaches the document <head> is gp247_render_block('header') in GP247Front's
 * layout.blade.php. The dropdown is a closed single-select fed by the registry,
 * so 'top_site' was the only head-code option an admin could pick — and nothing
 * rendered it. A block saved there validated, listed, and silently produced
 * nothing. 'header' in turn could not be picked at all.
 *
 * The guard that matters is not "the registry contains 'header'" but the two-way
 * agreement below: every position a template renders must be offerable, and every
 * position offered must be rendered somewhere.
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-004
 */
class LayoutBlockHeaderPositionTest extends TestCase
{
    use DatabaseTransactions;

    private const TEMPLATE = 'GP247Front';

    /** Position keys the admin dropdown offers. */
    private function registered(): array
    {
        return array_keys(config('gp247-config.front.layout_position', []));
    }

    /** Position literals the template's blades actually render, across every source root. */
    private function rendered(): array
    {
        $found = [];

        foreach (gp247_template_source_roots() as $root) {
            $dir = $root . '/' . self::TEMPLATE;
            if (!is_dir($dir)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }
                preg_match_all(
                    '/gp247_render_block\(\s*\'([a-z_]+)\'/',
                    file_get_contents($file->getPathname()),
                    $m
                );
                $found = array_merge($found, $m[1]);
            }
        }

        return array_values(array_unique($found));
    }

    public function test_every_position_the_template_renders_can_be_picked_in_admin(): void
    {
        $rendered = $this->rendered();
        $this->assertNotEmpty($rendered, 'no gp247_render_block() call found — the scan itself is broken');

        $missing = array_values(array_diff($rendered, $this->registered()));

        $this->assertSame(
            [],
            $missing,
            'the template renders these positions but the dropdown never offers them: ' . implode(', ', $missing)
        );
    }

    public function test_every_position_offered_in_admin_is_rendered_somewhere(): void
    {
        $dead = array_values(array_diff($this->registered(), $this->rendered()));

        $this->assertSame(
            [],
            $dead,
            'the dropdown offers these positions but no template renders them, so a block filed there disappears: ' . implode(', ', $dead)
        );
    }

    public function test_the_head_code_position_is_header(): void
    {
        $registered = $this->registered();

        $this->assertContains('header', $registered);
        $this->assertNotContains('top_site', $registered);
    }

    public function test_a_fresh_install_seeds_the_header_label(): void
    {
        $seeder = file_get_contents(base_path(
            'vendor/gp247/front/src/Admin/Database/Seeders/DataFrontLanguageSeeder.php'
        ));

        $this->assertStringContainsString("'code' => 'admin.layout_block_position.header'", $seeder);
        $this->assertStringNotContainsString('admin.layout_block_position.top_site', $seeder);
    }

    public function test_a_block_at_the_header_position_renders_inside_the_document_head(): void
    {
        $marker = '<!--ZZ_HEADER_POSITION_PROBE-->';
        $this->seedBlock(['position' => 'header', 'type' => 'html', 'text' => $marker, 'page' => '*']);

        $html = $this->storefrontHome();

        $this->assertStringContainsString($marker, $html);
        $this->assertLessThan(
            strpos($html, '</head>'),
            strpos($html, $marker),
            'head code must land inside <head>, which is the whole point of this position'
        );
    }

    public function test_upgrade_moves_installed_blocks_to_the_header_position(): void
    {
        $block = $this->seedBlock(['position' => 'top_site']);

        $this->migration()->up();

        $this->assertSame('header', $block->fresh()->position);
    }

    public function test_upgrade_leaves_blocks_at_other_positions_alone(): void
    {
        $other = $this->seedBlock(['position' => 'bottom']);

        $this->migration()->up();

        $this->assertSame('bottom', $other->fresh()->position);
    }

    public function test_upgrade_is_idempotent(): void
    {
        $block = $this->seedBlock(['position' => 'top_site']);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame('header', $block->fresh()->position);
        $this->assertSame(0, Languages::where('code', 'admin.layout_block_position.top_site')->count());
    }

    public function test_upgrade_seeds_the_header_label_on_a_site_that_lacks_it(): void
    {
        Languages::whereIn('code', [
            'admin.layout_block_position.top_site',
            'admin.layout_block_position.header',
        ])->delete();

        $this->migration()->up();

        $seeded = Languages::where('code', 'admin.layout_block_position.header')
            ->pluck('text', 'location')
            ->all();

        $this->assertSame('Vị trí Head code :meta, css, javascript,...', $seeded['vi'] ?? null);
        $this->assertSame('Position Head code: meta, css, javascript, ...', $seeded['en'] ?? null);
    }

    public function test_upgrade_keeps_a_label_the_site_owner_already_edited(): void
    {
        Languages::whereIn('code', [
            'admin.layout_block_position.top_site',
            'admin.layout_block_position.header',
        ])->delete();
        Languages::insert([
            ['code' => 'admin.layout_block_position.top_site', 'text' => 'Mã nhúng đầu trang', 'position' => 'admin.layout_page_block', 'location' => 'vi'],
        ]);

        $this->migration()->up();

        $row = Languages::where('code', 'admin.layout_block_position.header')
            ->where('location', 'vi')
            ->first();

        $this->assertSame('Mã nhúng đầu trang', $row->text);
        // The vi row was the lone one filed outside the group its siblings use.
        $this->assertSame('admin.layout_block', $row->position);
    }

    public function test_upgrade_drops_the_old_key_even_when_the_new_one_is_taken(): void
    {
        Languages::whereIn('code', [
            'admin.layout_block_position.top_site',
            'admin.layout_block_position.header',
        ])->delete();
        Languages::insert([
            ['code' => 'admin.layout_block_position.header', 'text' => 'Head', 'position' => 'admin.layout_block', 'location' => 'vi'],
            ['code' => 'admin.layout_block_position.top_site', 'text' => 'Cũ', 'position' => 'admin.layout_block', 'location' => 'vi'],
        ]);

        $this->migration()->up();

        $this->assertSame(0, Languages::where('code', 'admin.layout_block_position.top_site')->count());
        $this->assertSame(
            'Head',
            Languages::where('code', 'admin.layout_block_position.header')->where('location', 'vi')->value('text')
        );
    }

    /** Render the storefront home page with the layout cache of this process cleared. */
    private function storefrontHome(): string
    {
        // FrontLayoutBlock memoises the whole layout in a static property for the
        // life of the PHP process, which outlives a single test.
        $memo = new \ReflectionProperty(FrontLayoutBlock::class, 'getLayout');
        $memo->setAccessible(true);
        $memo->setValue(null, null);

        $response = $this->get('/');
        $response->assertOk();

        $memo->setValue(null, null);

        return $response->getContent();
    }

    private function seedBlock(array $overrides = []): FrontLayoutBlock
    {
        return FrontLayoutBlock::create(array_merge([
            'id' => (string) Str::uuid(),
            'name' => 'ZZ Header Position Probe',
            'position' => 'header',
            'page' => '*',
            'type' => 'html',
            'text' => '<!--ZZ-->',
            'sort' => 999,
            'status' => 1,
            'template' => self::TEMPLATE,
            'store_id' => config('app.storeId') ?: '1',
        ], $overrides));
    }

    private function migration(): object
    {
        return require base_path(
            'vendor/gp247/front/src/Admin/Database/Migrations/upgrade/2026_09_22_090000_rename_top_site_position_to_header.php'
        );
    }
}
