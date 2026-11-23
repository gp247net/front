<?php

namespace GP247\Front\Library;

use GP247\Front\Controllers\SeoController;
use GP247\Front\Models\FrontRedirect;
use GP247\Front\Support\SeoMetaInstaller;
use GP247\Front\Support\SeoUrlLifecycleInstaller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * SEO check of one store (SEO phase 3): a fixed catalogue of problem codes, each found
 * with one aggregate query (count + up to MAX_EXAMPLES examples with an edit link). Run
 * on demand within a time budget and kept per store; the admin screen, the "System
 * alerts" check, gp247:doctor and the MCP tools all read the same result
 * (ADR-seo-audit, NFR-MAINT-health-check-single-source).
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-audit, US-SEO-health-alert
 * @aidlc-adr ADR-seo-audit
 */
class SeoAudit
{
    public const CACHE_PREFIX = 'gp247_seo_audit:';

    public const LEVEL_FIX = 'fix';

    public const LEVEL_REVIEW = 'review';

    public const MAX_EXAMPLES = 20;

    /** Seconds after which the run stops and is marked incomplete (shared host). */
    public const TIME_BUDGET = 20;

    private const KEEP_DAYS = 7;

    /** Redirect hops followed when looking for loops. */
    private const MAX_HOPS = 10;

    /** Orphan ids deleted per statement. */
    private const DELETE_CHUNK = 1000;

    /** Problem codes (a stable contract for tests, MCP and doctor) => level. */
    public const CODES = [
        'robots_blocks_site' => self::LEVEL_FIX,
        'content_noindex' => self::LEVEL_FIX,
        'redirect_loop' => self::LEVEL_FIX,
        'redirect_to_missing' => self::LEVEL_FIX,
        'canonical_offsite' => self::LEVEL_FIX,
        'missing_description' => self::LEVEL_REVIEW,
        'duplicate_title' => self::LEVEL_REVIEW,
        'redirect_chain' => self::LEVEL_REVIEW,
        'sitemap_all_off' => self::LEVEL_REVIEW,
        'store_missing_description' => self::LEVEL_REVIEW,
        'store_missing_og_image' => self::LEVEL_REVIEW,
        'no_search_verification' => self::LEVEL_REVIEW,
        'orphan_seo_rows' => self::LEVEL_REVIEW,
    ];

    /**
     * A redirect target counts as missing only when it returned 404 within this many days.
     * WHY: the 404 log keeps a row for 90 days, so a target that failed once and works now
     * would otherwise stay "to fix"; a target still missing gets fresh 404s from the very
     * visitors the redirect sends there.
     */
    public const RECENT_404_DAYS = 7;

    /** Language key of a finding's title when it differs from `admin.seo_audit.code_<code>`. */
    private const LABEL_KEYS = [
        // WHY a new key: the old text ("seen in the 404 log") no longer says what is checked,
        // and a new key reaches existing sites through the language seeder without a migration.
        'redirect_to_missing' => 'admin.seo_audit.code_redirect_to_recent_404',
    ];

    /**
     * Language key of the title of a finding.
     *
     * @param string $code One of CODES.
     * @return string
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-audit
     */
    public static function labelKey(string $code): string
    {
        return self::LABEL_KEYS[$code] ?? 'admin.seo_audit.code_' . $code;
    }

    /**
     * Run every check for a store and keep the result.
     *
     * @param int|string $storeId
     * @return array{store_id: string, checked_at: string, complete: bool, findings: array<string, array>}
     */
    public static function run($storeId): array
    {
        $store = (string) $storeId;
        $started = microtime(true);
        $complete = true;
        $findings = [];
        $lang = self::defaultLanguage($store);

        foreach (array_keys(self::CODES) as $code) {
            if (microtime(true) - $started > self::TIME_BUDGET) {
                $complete = false;
                break;
            }
            [$count, $examples] = self::check($code, $store, $lang);
            if ($count > 0) {
                $findings[$code] = [
                    'code' => $code,
                    'level' => self::CODES[$code],
                    'count' => $count,
                    'examples' => array_slice($examples, 0, self::MAX_EXAMPLES),
                ];
            }
        }

        $result = ['store_id' => $store, 'checked_at' => now()->toIso8601String(), 'complete' => $complete, 'findings' => $findings];
        Cache::put(self::CACHE_PREFIX . $store, $result, now()->addDays(self::KEEP_DAYS));

        return $result;
    }

    /**
     * Last kept result of a store, null when never checked (or expired).
     *
     * @param int|string $storeId
     * @return array|null
     */
    public static function latest($storeId): ?array
    {
        $result = Cache::get(self::CACHE_PREFIX . (string) $storeId);

        return is_array($result) ? $result : null;
    }

    /**
     * Delete SEO rows whose content no longer exists (every store, known content types).
     * Global on purpose: such a row belongs to no live content of any store, so removing
     * it loses nothing a store owner can see.
     *
     * @return int Rows removed.
     */
    public static function cleanOrphans(): int
    {
        if (!SeoContentMeta::tableReady()) {
            return 0;
        }
        $removed = 0;
        foreach (self::orphanQueries() as $query) {
            // WHY chunks: a site that deleted a large catalogue can leave many orphans; one
            // huge IN() list hits placeholder limits and holds locks for long.
            foreach ($query->pluck('m.id')->chunk(self::DELETE_CHUNK) as $ids) {
                $ids = $ids->values();
                DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_DESCRIPTION)->whereIn('seo_meta_id', $ids)->delete();
                $removed += DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_META)->whereIn('id', $ids)->delete();
            }
        }

        return $removed;
    }

    /**
     * @param string $code
     * @param string $store
     * @param string $lang
     * @return array{0: int, 1: array<int, array>}
     */
    private static function check(string $code, string $store, string $lang): array
    {
        return match ($code) {
            'robots_blocks_site' => self::flag(self::robotsBlocksSite($store)),
            'content_noindex' => self::contentNoindex($store, $lang),
            'redirect_loop' => self::redirectLoops($store),
            'redirect_to_missing' => self::redirectToMissing($store),
            'canonical_offsite' => self::canonicalOffsite($store, $lang),
            'missing_description' => self::missingDescription($store, $lang),
            'duplicate_title' => self::duplicateTitles($store, $lang),
            'redirect_chain' => self::redirectChains($store),
            'sitemap_all_off' => self::flag(class_exists(\GP247\Shop\Models\ShopProduct::class)
                && (string) gp247_config('seo.sitemap_include_products', $store, '1') === '0'
                && (string) gp247_config('seo.sitemap_include_categories', $store, '1') === '0'),
            'store_missing_description' => self::flag(trim((string) gp247_store_info('description', '', $store)) === ''),
            'store_missing_og_image' => self::flag(trim((string) gp247_store_info('og_image', '', $store)) === ''),
            'no_search_verification' => self::flag((SeoMeta::verificationCodes($store)['google'] ?? '') === ''),
            'orphan_seo_rows' => self::orphanRows(),
            default => [0, []],
        };
    }

    /**
     * @param bool $problem
     * @return array{0: int, 1: array}
     */
    private static function flag(bool $problem): array
    {
        return [$problem ? 1 : 0, []];
    }

    /**
     * Content sources of the registered SEO types — declared by the package or plugin
     * owning each type (US-SEO-content-type-registry), so a plugin's content is checked
     * like the built-in types.
     *
     * @return array<string, array{table: string, desc: ?string, fk: ?string, name: string, text: array, edit_route: ?string, class: string}>
     */
    private static function sources(): array
    {
        return SeoContentMeta::sources();
    }

    /**
     * Active content of a type in a store with its label (default language) and own SEO row.
     *
     * @param string $type
     * @param array  $src
     * @param string $store
     * @param string $lang
     * @return \Illuminate\Database\Query\Builder
     */
    private static function contentQuery(string $type, array $src, string $store, string $lang)
    {
        $p = GP247_DB_PREFIX;
        $q = DB::connection(GP247_DB_CONNECTION)->table($p . $src['table'] . ' as c')
            ->where('c.store_id', $store)
            ->where('c.status', 1)
            ->leftJoin($p . SeoMetaInstaller::TABLE_META . ' as m', function ($j) use ($type) {
                $j->on('m.content_id', '=', 'c.id')->where('m.type', '=', $type);
            })
            ->leftJoin($p . SeoMetaInstaller::TABLE_DESCRIPTION . ' as sd', function ($j) use ($lang) {
                $j->on('sd.seo_meta_id', '=', 'm.id')->where('sd.lang', '=', $lang);
            });
        if ($src['desc'] !== null) {
            $q->leftJoin($p . $src['desc'] . ' as d', function ($j) use ($src, $lang) {
                $j->on('d.' . $src['fk'], '=', 'c.id')->where('d.lang', '=', $lang);
            });
        }

        return $q;
    }

    /**
     * SQL of the label shown for a content row (own SEO title, else its name). The name
     * column comes from the type's declared source: already checked against a strict
     * identifier pattern and the table's real columns (SeoContentMeta::sources()), and
     * quoted here by the connection's grammar as a second guard.
     *
     * @param array $src
     * @return string
     */
    private static function titleSql(array $src): string
    {
        $column = DB::connection(GP247_DB_CONNECTION)->getQueryGrammar()->wrap($src['name']);
        $name = ($src['desc'] !== null ? 'd.' : 'c.') . $column;

        return "COALESCE(NULLIF(TRIM(sd.title), ''), " . $name . ')';
    }

    /**
     * Count + examples of a filtered content query, per type.
     *
     * @param string   $store
     * @param string   $lang
     * @param callable $filter fn($query, string $type, array $src): void
     * @return array{0: int, 1: array}
     */
    private static function contentFinding(string $store, string $lang, callable $filter): array
    {
        if (!SeoContentMeta::tableReady()) {
            return [0, []];
        }
        $count = 0;
        $examples = [];
        foreach (self::sources() as $type => $src) {
            $q = self::contentQuery($type, $src, $store, $lang);
            $filter($q, $type, $src);
            $count += (clone $q)->count();
            if (count($examples) < self::MAX_EXAMPLES) {
                $rows = (clone $q)->selectRaw('c.id, ' . self::titleSql($src) . ' as label')
                    ->orderByDesc('c.updated_at')->limit(self::MAX_EXAMPLES - count($examples))->get();
                foreach ($rows as $row) {
                    $examples[] = self::example($type, (string) $row->id, (string) $row->label, $src['edit_route']);
                }
            }
        }

        return [$count, $examples];
    }

    /**
     * @return array{0: int, 1: array}
     */
    private static function contentNoindex(string $store, string $lang): array
    {
        return self::contentFinding($store, $lang, static function ($q) {
            $q->where('m.noindex', 1);
        });
    }

    /**
     * @return array{0: int, 1: array}
     */
    private static function missingDescription(string $store, string $lang): array
    {
        return self::contentFinding($store, $lang, static function ($q, $type, $src) {
            $q->where(function ($w) {
                $w->whereNull('sd.description')->orWhereRaw("TRIM(sd.description) = ''");
            });
            if ($src['desc'] !== null) {
                $q->where(function ($w) {
                    $w->whereNull('d.description')->orWhereRaw("TRIM(d.description) = ''");
                });
            }
        });
    }

    /**
     * @return array{0: int, 1: array}
     */
    private static function canonicalOffsite(string $store, string $lang): array
    {
        $host = self::bareHost(IndexNow::baseUrl($store));
        if ($host === '' || !SeoContentMeta::tableReady()) {
            return [0, []];
        }
        $count = 0;
        $examples = [];
        foreach (self::sources() as $type => $src) {
            $rows = self::contentQuery($type, $src, $store, $lang)
                ->whereNotNull('sd.canonical')
                ->selectRaw('c.id, sd.canonical, ' . self::titleSql($src) . ' as label')
                ->limit(5000)->get();
            foreach ($rows as $row) {
                $target = self::bareHost((string) $row->canonical);
                if ($target !== '' && $target !== $host) {
                    $count++;
                    if (count($examples) < self::MAX_EXAMPLES) {
                        $examples[] = self::example($type, (string) $row->id, (string) $row->label, $src['edit_route']);
                    }
                }
            }
        }

        return [$count, $examples];
    }

    /**
     * Host of a URL, lower-case, without a leading "www." (www.shop.com and shop.com are
     * the same site for this check).
     *
     * @param string $url
     * @return string '' when the URL has no host.
     */
    private static function bareHost(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    /**
     * Content rows of one type that share their shown title (own SEO title, else name).
     *
     * @param string $store
     * @param string $lang
     * @return array{0: int, 1: array}
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-audit
     */
    private static function duplicateTitles(string $store, string $lang): array
    {
        $count = 0;
        $examples = [];
        foreach (self::sources() as $type => $src) {
            $title = self::titleSql($src);
            // WHY MIN(): MariaDB's ONLY_FULL_GROUP_BY checks columns, not expressions, so a bare
            // title expression in the select list is refused even though it equals the GROUP BY
            // expression. Inside one group the expression has a single value, so MIN() is it.
            $groups = self::contentQuery($type, $src, $store, $lang)
                ->selectRaw('MIN(' . $title . ') as t, COUNT(*) as n')
                ->groupByRaw($title)
                ->havingRaw('COUNT(*) > 1')
                ->limit(1000)->get();
            $dups = [];
            foreach ($groups as $g) {
                if (trim((string) $g->t) !== '') {
                    $count += (int) $g->n;
                    $dups[] = (string) $g->t;
                }
            }
            if ($dups !== [] && count($examples) < self::MAX_EXAMPLES) {
                $rows = self::contentQuery($type, $src, $store, $lang)
                    ->whereIn(DB::raw($title), array_slice($dups, 0, self::MAX_EXAMPLES))
                    ->selectRaw('c.id, ' . $title . ' as label')
                    ->orderByRaw($title)->limit(self::MAX_EXAMPLES - count($examples))->get();
                foreach ($rows as $row) {
                    $examples[] = self::example($type, (string) $row->id, (string) $row->label, $src['edit_route']);
                }
            }
        }

        return [$count, $examples];
    }

    /**
     * Enabled redirects of a store whose target is the source of another enabled redirect.
     *
     * @return array{0: int, 1: array}
     */
    private static function redirectChains(string $store): array
    {
        $t = GP247_DB_PREFIX . 'front_redirects';
        $q = DB::connection(GP247_DB_CONNECTION)->table($t . ' as r1')
            ->join($t . ' as r2', function ($j) {
                $j->on('r2.from', '=', 'r1.to')->on('r2.store_id', '=', 'r1.store_id');
            })
            ->where('r1.store_id', $store)->where('r1.status', 1)->where('r2.status', 1)
            ->whereColumn('r1.id', '!=', 'r2.id');

        return [(clone $q)->count(), self::redirectExamples((clone $q)->select('r1.id', 'r1.from', 'r1.to')->limit(self::MAX_EXAMPLES)->get())];
    }

    /**
     * Cycles among the enabled redirects of a store (each cycle counted once).
     *
     * @return array{0: int, 1: array}
     */
    private static function redirectLoops(string $store): array
    {
        $rows = FrontRedirect::where('store_id', $store)->where('status', 1)->limit(5000)->get(['id', 'from', 'to']);
        $map = [];
        $byFrom = [];
        foreach ($rows as $row) {
            $map[(string) $row->from] = (string) $row->to;
            $byFrom[(string) $row->from] = $row;
        }
        $cycles = [];
        foreach (array_keys($map) as $start) {
            $path = [$start];
            $current = $map[$start];
            for ($i = 0; $i < self::MAX_HOPS; $i++) {
                if ($current === $start) {
                    // WHY sorted members: the same cycle reached from each of its sources counts once.
                    $members = $path;
                    sort($members);
                    $cycles[implode('|', $members)] = $start;
                    break;
                }
                if (!isset($map[$current]) || in_array($current, $path, true)) {
                    break;
                }
                $path[] = $current;
                $current = $map[$current];
            }
        }
        $examples = [];
        foreach (array_slice(array_values($cycles), 0, self::MAX_EXAMPLES) as $start) {
            $examples[] = self::redirectExample($byFrom[$start]);
        }

        return [count($cycles), $examples];
    }

    /**
     * Enabled redirects whose target returned 404 within RECENT_404_DAYS (only while the
     * log is on). Each example carries the time of that last 404 (`last_404`).
     *
     * @return array{0: int, 1: array}
     */
    private static function redirectToMissing(string $store): array
    {
        if (!NotFoundLog::enabled($store) || !NotFoundLog::tableReady()) {
            return [0, []];
        }
        $q = DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . 'front_redirects as r')
            ->join(GP247_DB_PREFIX . SeoUrlLifecycleInstaller::TABLE_NOT_FOUND . ' as n', function ($j) {
                $j->on('n.path', '=', 'r.to')->on('n.store_id', '=', 'r.store_id');
            })
            ->where('r.store_id', $store)->where('r.status', 1)
            ->where('n.last_seen_at', '>=', now()->subDays(self::RECENT_404_DAYS));

        $examples = [];
        foreach ((clone $q)->select('r.id', 'r.from', 'r.to', 'n.last_seen_at')->orderByDesc('n.last_seen_at')->limit(self::MAX_EXAMPLES)->get() as $row) {
            $examples[] = self::redirectExample($row) + ['last_404' => \Illuminate\Support\Carbon::parse($row->last_seen_at)->format('Y-m-d H:i')];
        }

        return [(clone $q)->count(), $examples];
    }

    /**
     * Whether robots.txt disallows the whole site for every crawler.
     *
     * @param string $store
     * @return bool
     */
    private static function robotsBlocksSite(string $store): bool
    {
        $text = (string) gp247_config('seo.robots_txt', $store, SeoController::defaultRobots());
        $forAll = false;
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim((string) preg_replace('/#.*/', '', $line));
            if (preg_match('/^user-agent\s*:\s*(.+)$/i', $line, $m)) {
                $forAll = trim($m[1]) === '*';
            } elseif ($forAll && preg_match('/^disallow\s*:\s*\/\s*$/i', $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: int, 1: array}
     */
    private static function orphanRows(): array
    {
        if (!SeoContentMeta::tableReady()) {
            return [0, []];
        }
        $count = 0;
        foreach (self::orphanQueries() as $query) {
            $count += $query->count();
        }

        return [$count, []];
    }

    /**
     * One query per known type: SEO rows whose content row is gone.
     *
     * @return array<int, \Illuminate\Database\Query\Builder>
     */
    private static function orphanQueries(): array
    {
        $queries = [];
        foreach (self::sources() as $type => $src) {
            $content = GP247_DB_PREFIX . $src['table'];
            $queries[] = DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_META . ' as m')
                ->where('m.type', $type)
                ->whereNotExists(function ($sub) use ($content) {
                    $sub->selectRaw('1')->from($content . ' as x')->whereColumn('x.id', 'm.content_id');
                });
        }

        return $queries;
    }

    /**
     * @param iterable $rows Rows with id, from, to.
     * @return array<int, array>
     */
    private static function redirectExamples(iterable $rows): array
    {
        $examples = [];
        foreach ($rows as $row) {
            $examples[] = self::redirectExample($row);
        }

        return $examples;
    }

    /**
     * @param object $row
     * @return array{type: string, id: string, label: string, url: string}
     */
    private static function redirectExample($row): array
    {
        return self::example('redirect', (string) $row->id, $row->from . ' → ' . $row->to, 'admin_seo_redirect.edit');
    }

    /**
     * @param string $type
     * @param string $id
     * @param string $label Text typed by staff — escape when printed.
     * @param string|null $route Admin edit route name (a type may declare none).
     * @return array{type: string, id: string, label: string, url: string}
     */
    private static function example(string $type, string $id, string $label, ?string $route): array
    {
        $url = $route !== null && Route::has($route) ? gp247_route_admin($route, ['id' => $id]) : '';

        return ['type' => $type, 'id' => $id, 'label' => mb_substr($label, 0, 255), 'url' => (string) $url];
    }

    /**
     * Default language of a store.
     *
     * @param string $store
     * @return string
     */
    private static function defaultLanguage(string $store): string
    {
        $lang = (string) gp247_store_info('language', null, $store);

        return $lang !== '' ? $lang : (string) config('app.locale');
    }
}
