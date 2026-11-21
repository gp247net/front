<?php

namespace GP247\Front\Library;

use GP247\Core\Models\AdminLanguage;
use GP247\Front\Controllers\SeoController;
use GP247\Front\Models\FrontSeoMeta;
use GP247\Front\Models\FrontSeoMetaDescription;
use GP247\Front\Support\SeoMetaInstaller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Per-content SEO values typed by the site owner (SEO phase 1): the content-type
 * registry, the "content being displayed" a controller declares for MetaHead,
 * read / save / forget, robots merging and title templates. The only code that
 * touches the front_seo_meta tables (ADR-seo-content-meta-table).
 *
 * A piece of content without a row behaves exactly as before phase 1.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-content-meta, US-SEO-content-index-control, US-SEO-title-template, US-SEO-content-type-registry
 * @aidlc-adr ADR-seo-content-meta-table
 */
class SeoContentMeta
{
    /** Allowed content-type keys in the registry. */
    public const TYPE_PATTERN = '/^[a-z0-9_-]{1,50}$/';

    /** Prefix of the per-type title template config keys (admin_config, per store). */
    public const TITLE_TEMPLATE_KEY = 'seo.title_template.';

    /** Key of the fallback title template (pages without an own type template). */
    public const TITLE_TEMPLATE_DEFAULT = 'default';

    public const TITLE_TEMPLATE_MAX = 150;

    /** Column limits shared by validation and the panel counters. */
    public const MAX_TITLE = 255;

    public const MAX_DESCRIPTION = 500;

    public const MAX_URL = 500;

    public const MAX_IMAGE = 255;

    /** Language code accepted as a key of desc.<lang>. */
    private const LANG_PATTERN = '/^[A-Za-z_-]{1,10}$/';

    private const SUBJECT_ATTRIBUTE = 'gp247_seo_subject';

    private const CURRENT_ATTRIBUTE = 'gp247_seo_current';

    /** Cache key remembering that both tables exist (only "ready" is cached). */
    public const TABLE_READY_CACHE_KEY = 'gp247_front_seo_meta_tables_ready';

    /** Memo of "both tables exist" for this process (a site may run new code before gp247:update). */
    private static ?bool $tableReady = null;

    /** Table or column name a content source may declare. */
    private const IDENTIFIER_PATTERN = '/^[a-z0-9_]{1,64}$/';

    /** Columns every listed content table must have (read by the SEO check and listing tools). */
    private const CONTENT_COLUMNS = ['id', 'store_id', 'status', 'updated_at'];

    /** @var array<string, array<int, string>> Memo of the columns of tables named by content sources. */
    private static array $tableColumns = [];

    /** @var array<string, bool> Types whose broken source was already logged. */
    private static array $warnedSources = [];

    /**
     * Registered content types (`gp247-config.front.seo_content_types`), keys
     * that do not match TYPE_PATTERN dropped.
     *
     * @return array<string, array{label?: string}>
     */
    public static function types(): array
    {
        $types = [];
        foreach ((array) config('gp247-config.front.seo_content_types', []) as $key => $definition) {
            if (is_string($key) && preg_match(self::TYPE_PATTERN, $key)) {
                $types[$key] = is_array($definition) ? $definition : [];
            }
        }

        return $types;
    }

    /**
     * @param string $type Content-type key.
     * @return bool
     */
    public static function isRegistered(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    /**
     * Where the content of each registered type lives, for code that lists content
     * (the SEO check, plugins). Built from the optional `source` key of a type:
     * `table`, `class` (Eloquent model owning that table), `name` (label column, default
     * `name`), `desc` + `fk` (per-language table holding the label), `text` (long text
     * columns) and `edit_route` (admin edit route taking `id`). A type without a valid
     * source keeps its own SEO values; it is only not listed.
     *
     * @return array<string, array{table: string, desc: ?string, fk: ?string, name: string, text: array<int, string>, edit_route: ?string, class: string}>
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-content-type-registry
     * @aidlc-adr ADR-seo-content-meta-table
     */
    public static function sources(): array
    {
        $sources = [];
        foreach (self::types() as $type => $definition) {
            if (!array_key_exists('source', $definition)) {
                continue;
            }
            $source = self::validSource($definition['source']);
            if ($source === null) {
                self::warnInvalidSource($type);
                continue;
            }
            $sources[$type] = $source;
        }

        return $sources;
    }

    /**
     * A declared source normalised, or null when any part is unsafe or does not exist.
     * Table and column names end up as SQL identifiers, so they must match a strict
     * pattern — bindings cannot protect identifiers.
     *
     * @param mixed $source Value of the `source` key.
     * @return array|null
     */
    private static function validSource($source): ?array
    {
        if (!is_array($source)) {
            return null;
        }
        $table = $source['table'] ?? null;
        $class = $source['class'] ?? null;
        $desc = $source['desc'] ?? null;
        $fk = $desc !== null ? ($source['fk'] ?? null) : null;
        $name = $source['name'] ?? 'name';
        $text = $source['text'] ?? [];
        $editRoute = $source['edit_route'] ?? null;

        $identifiers = array_merge([$table, $name], $desc !== null ? [$desc, $fk] : [], is_array($text) ? $text : [null]);
        foreach ($identifiers as $identifier) {
            if (!is_string($identifier) || !preg_match(self::IDENTIFIER_PATTERN, $identifier)) {
                return null;
            }
        }
        if (($editRoute !== null && !is_string($editRoute))
            || !is_string($class) || !class_exists($class) || !is_subclass_of($class, Model::class)) {
            return null;
        }
        // WHY: the SEO check deletes SEO rows whose content is missing from this table; a
        // table the model does not own would make every row of the type look orphaned.
        if ((new $class())->getTable() !== GP247_DB_PREFIX . $table) {
            return null;
        }
        $labelTable = $desc ?? $table;
        $needed = [$table => self::CONTENT_COLUMNS];
        $needed[$labelTable] = array_merge($needed[$labelTable] ?? [], [$name], $text, $desc !== null ? [$fk, 'lang'] : []);
        foreach ($needed as $neededTable => $columns) {
            if (!self::hasColumns($neededTable, $columns)) {
                return null;
            }
        }

        return [
            'table' => $table,
            'desc' => $desc,
            'fk' => $fk,
            'name' => $name,
            'text' => array_values($text),
            'edit_route' => $editRoute,
            'class' => $class,
        ];
    }

    /**
     * Whether a table exists with all the given columns (column list read once per process).
     *
     * @param string             $table   Table name without the GP247 prefix.
     * @param array<int, string> $columns
     * @return bool
     */
    private static function hasColumns(string $table, array $columns): bool
    {
        if (!array_key_exists($table, self::$tableColumns)) {
            self::$tableColumns[$table] = Schema::connection(GP247_DB_CONNECTION)->getColumnListing(GP247_DB_PREFIX . $table);
        }

        return self::$tableColumns[$table] !== [] && array_diff($columns, self::$tableColumns[$table]) === [];
    }

    /**
     * Log a broken `source` declaration once per process, so a misconfigured plugin is
     * visible without flooding the log on every page.
     *
     * @param string $type
     * @return void
     */
    private static function warnInvalidSource(string $type): void
    {
        if (isset(self::$warnedSources[$type])) {
            return;
        }
        self::$warnedSources[$type] = true;
        Log::warning('SEO content type has an invalid source and is not listed', ['type' => $type]);
    }

    /**
     * Declare the content a controller is about to render, so MetaHead can read
     * its own SEO values. Request-scoped.
     *
     * @param string     $type Registered content-type key.
     * @param int|string $id   Content id.
     * @return void
     */
    public static function subject(string $type, $id): void
    {
        request()->attributes->set(self::SUBJECT_ATTRIBUTE, ['type' => $type, 'id' => (string) $id]);
        request()->attributes->remove(self::CURRENT_ATTRIBUTE);
    }

    /**
     * @return array{type: string, id: string}|null The declared content, or null.
     */
    public static function currentSubject(): ?array
    {
        $subject = request()->attributes->get(self::SUBJECT_ATTRIBUTE);

        return is_array($subject) ? $subject : null;
    }

    /**
     * SEO values of the declared content in the current locale; read once per request.
     * No declared content ⇒ null without a query.
     *
     * @return array<string, mixed>|null
     */
    public static function current(): ?array
    {
        $subject = self::currentSubject();
        if ($subject === null) {
            return null;
        }
        $attributes = request()->attributes;
        if (!$attributes->has(self::CURRENT_ATTRIBUTE)) {
            $attributes->set(self::CURRENT_ATTRIBUTE, self::for($subject['type'], $subject['id']));
        }

        return $attributes->get(self::CURRENT_ATTRIBUTE);
    }

    /**
     * SEO values of one piece of content in one language (one query).
     *
     * @param string      $type Content-type key.
     * @param int|string  $id   Content id.
     * @param string|null $lang Language code; null = current locale.
     * @return array{noindex: bool, nofollow: bool, og_image: ?string, title: ?string, description: ?string, canonical: ?string}|null
     *         Null when the content has no own SEO values.
     */
    public static function for(string $type, $id, ?string $lang = null): ?array
    {
        if (!self::isRegistered($type) || !self::tableReady()) {
            return null;
        }
        $lang = $lang ?? gp247_get_locale();
        $row = DB::connection(GP247_DB_CONNECTION)
            ->table(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_META . ' as m')
            ->leftJoin(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_DESCRIPTION . ' as d', function ($join) use ($lang) {
                $join->on('d.seo_meta_id', '=', 'm.id')->where('d.lang', '=', $lang);
            })
            ->where('m.type', $type)
            ->where('m.content_id', (string) $id)
            ->first(['m.noindex', 'm.nofollow', 'm.og_image', 'd.title', 'd.description', 'd.canonical']);

        if ($row === null) {
            return null;
        }

        return [
            'noindex' => (bool) $row->noindex,
            'nofollow' => (bool) $row->nofollow,
            'og_image' => self::blankToNull($row->og_image),
            'title' => self::blankToNull($row->title),
            'description' => self::blankToNull($row->description),
            'canonical' => self::blankToNull($row->canonical),
        ];
    }

    /**
     * Panel state for the admin form: flags, image and one entry per active language.
     *
     * @param string          $type Content-type key.
     * @param int|string|null $id   Content id; null = new content (empty state).
     * @return array{noindex: bool, nofollow: bool, og_image: string, desc: array<string, array{title: string, description: string, canonical: string}>}
     */
    public static function formState(string $type, $id): array
    {
        $state = ['noindex' => false, 'nofollow' => false, 'og_image' => '', 'desc' => []];
        foreach (array_keys(AdminLanguage::getListActive()->all()) as $code) {
            $state['desc'][$code] = ['title' => '', 'description' => '', 'canonical' => ''];
        }
        $meta = ($id === null || $id === '' || !self::isRegistered($type) || !self::tableReady())
            ? null
            : FrontSeoMeta::where('type', $type)->where('content_id', (string) $id)->first();
        if ($meta === null) {
            return $state;
        }
        $state['noindex'] = (bool) $meta->noindex;
        $state['nofollow'] = (bool) $meta->nofollow;
        $state['og_image'] = (string) $meta->og_image;
        foreach ($meta->descriptions as $row) {
            if (isset($state['desc'][$row->lang])) {
                $state['desc'][$row->lang] = [
                    'title' => (string) $row->title,
                    'description' => (string) $row->description,
                    'canonical' => (string) $row->canonical,
                ];
            }
        }

        return $state;
    }

    /**
     * Validation rules of the SEO values (panel or direct save).
     *
     * @param string $prefix Key prefix (`seo` for the panel state); '' for a bare array.
     * @return array<string, array<int, string>>
     */
    public static function rules(string $prefix = 'seo'): array
    {
        $p = $prefix === '' ? '' : $prefix . '.';

        return [
            $p . 'noindex' => ['nullable', 'boolean'],
            $p . 'nofollow' => ['nullable', 'boolean'],
            $p . 'og_image' => ['nullable', 'string', 'max:' . self::MAX_IMAGE],
            $p . 'desc' => ['nullable', 'array'],
            $p . 'desc.*.title' => ['nullable', 'string', 'max:' . self::MAX_TITLE],
            $p . 'desc.*.description' => ['nullable', 'string', 'max:' . self::MAX_DESCRIPTION],
            $p . 'desc.*.canonical' => ['nullable', 'string', 'max:' . self::MAX_URL, 'url:http,https'],
        ];
    }

    /**
     * Validate and store the SEO values of one piece of content. Blank values are
     * stored as "none"; when nothing is left the content has no row at all.
     *
     * @param string          $type    Registered content-type key (unregistered ⇒ ignored + warning).
     * @param int|string      $id      Content id.
     * @param array           $data    {noindex?, nofollow?, og_image?, desc?: {<lang>: {title?, description?, canonical?}}}.
     * @param int|string|null $storeId Store whose sitemap cache is refreshed; null = current store.
     * @return void
     * @throws ValidationException When a value breaks the rules (e.g. a non-http canonical).
     */
    public static function save(string $type, $id, array $data, $storeId = null): void
    {
        if (!self::isRegistered($type)) {
            Log::warning('SEO content type is not registered; values not saved', ['type' => $type]);

            return;
        }
        if (!self::tableReady()) {
            Log::warning('SEO tables are missing (run php artisan gp247:update); values not saved', ['type' => $type]);

            return;
        }
        Validator::make($data, self::rules(''))->validate();
        $languages = array_keys(AdminLanguage::getListActive()->all());

        DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($type, $id, $data, $languages) {
            $meta = FrontSeoMeta::firstOrNew(['type' => $type, 'content_id' => (string) $id]);
            $meta->noindex = !empty($data['noindex']);
            $meta->nofollow = !empty($data['nofollow']);
            $meta->og_image = self::clean($data['og_image'] ?? null);
            $meta->save();

            foreach ((array) ($data['desc'] ?? []) as $lang => $values) {
                // WHY active languages only: the panel state comes from the browser.
                if (!is_string($lang) || !preg_match(self::LANG_PATTERN, $lang) || !in_array($lang, $languages, true)) {
                    continue;
                }
                self::writeLanguage((int) $meta->id, $lang, (array) $values);
            }

            if (!$meta->noindex && !$meta->nofollow && $meta->og_image === null && !$meta->descriptions()->exists()) {
                $meta->delete();
            }
        });

        self::afterChange($storeId);
    }

    /**
     * Remove the SEO values of a piece of content (called when the content is deleted).
     *
     * @param string     $type Content-type key.
     * @param int|string $id   Content id.
     * @return void
     */
    public static function forget(string $type, $id): void
    {
        if (!self::tableReady()) {
            return;
        }
        $ids = FrontSeoMeta::where('type', $type)->where('content_id', (string) $id)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        FrontSeoMetaDescription::whereIn('seo_meta_id', $ids)->delete();
        FrontSeoMeta::whereIn('id', $ids)->delete();
    }

    /**
     * Robots directive once the content's own flags are applied over the route /
     * query result: content can only add restrictions, never lift a noindex.
     *
     * @param array|null  $content  Result of current() / for().
     * @param string|null $fallback Directive from SeoMeta::robotsFor().
     * @return string|null
     */
    public static function robotsDirective(?array $content, ?string $fallback): ?string
    {
        if ($content === null || (empty($content['noindex']) && empty($content['nofollow']))) {
            return $fallback;
        }
        $fallback = strtolower((string) $fallback);
        $noindex = !empty($content['noindex']) || str_contains($fallback, 'noindex');
        $nofollow = !empty($content['nofollow']) || str_contains($fallback, 'nofollow');

        return ($noindex ? 'noindex' : 'index') . ', ' . ($nofollow ? 'nofollow' : 'follow');
    }

    /**
     * Title template of a type, '' when not set.
     *
     * @param string          $type    Content-type key or TITLE_TEMPLATE_DEFAULT.
     * @param int|string|null $storeId Null = current store.
     * @return string
     */
    public static function titleTemplate(string $type, $storeId = null): string
    {
        return trim((string) gp247_config(self::TITLE_TEMPLATE_KEY . $type, $storeId ?? config('app.storeId'), ''));
    }

    /**
     * @param string $template Candidate template ('' clears it).
     * @return bool True when empty, or ≤ TITLE_TEMPLATE_MAX chars and containing `{title}`.
     */
    public static function isValidTitleTemplate(string $template): bool
    {
        return $template === ''
            || (mb_strlen($template) <= self::TITLE_TEMPLATE_MAX && str_contains($template, '{title}'));
    }

    /**
     * Wrap a generated title in the template of its type (else the default template).
     * The store name alone is never wrapped (it would repeat itself).
     *
     * @param string          $title   Generated title (content name or layout title).
     * @param string|null     $type    Declared content type; null = page without content.
     * @param int|string|null $storeId Null = current store.
     * @return string
     */
    public static function applyTitleTemplate(string $title, ?string $type = null, $storeId = null): string
    {
        $site = (string) gp247_store_info('name');
        if ($title === $site) {
            return $title;
        }
        $template = $type !== null ? self::titleTemplate($type, $storeId) : '';
        if ($template === '') {
            $template = self::titleTemplate(self::TITLE_TEMPLATE_DEFAULT, $storeId);
        }

        return $template === '' ? $title : strtr($template, ['{title}' => $title, '{site}' => $site]);
    }

    /**
     * Whether both tables exist (memoized per process).
     *
     * @return bool
     */
    public static function tableReady(): bool
    {
        if (self::$tableReady === true) {
            return true;
        }
        // WHY cache only "ready": schema lookups would otherwise run on every storefront
        // request, while a missing table must be noticed as soon as gp247:update created it.
        if (Cache::get(self::TABLE_READY_CACHE_KEY) === true) {
            return self::$tableReady = true;
        }
        $schema = Schema::connection(GP247_DB_CONNECTION);
        $ready = $schema->hasTable(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_META)
            && $schema->hasTable(GP247_DB_PREFIX . SeoMetaInstaller::TABLE_DESCRIPTION);
        if ($ready) {
            Cache::forever(self::TABLE_READY_CACHE_KEY, true);
            self::$tableReady = true;
        }

        return $ready;
    }

    /**
     * Forget the readiness memo and cache (tables dropped by a fresh front reinstall).
     *
     * @return void
     */
    public static function resetTableReady(): void
    {
        self::$tableReady = null;
        Cache::forget(self::TABLE_READY_CACHE_KEY);
    }

    /**
     * Upsert or delete one language row.
     *
     * @param int    $metaId
     * @param string $lang
     * @param array  $values {title?, description?, canonical?}
     * @return void
     */
    private static function writeLanguage(int $metaId, string $lang, array $values): void
    {
        $row = [
            'title' => self::clean($values['title'] ?? null),
            'description' => self::clean($values['description'] ?? null),
            'canonical' => self::clean($values['canonical'] ?? null),
        ];
        $query = FrontSeoMetaDescription::where('seo_meta_id', $metaId)->where('lang', $lang);
        if ($row['title'] === null && $row['description'] === null && $row['canonical'] === null) {
            $query->delete();

            return;
        }
        if ($query->exists()) {
            $query->update($row);

            return;
        }
        FrontSeoMetaDescription::insert(array_merge(['seo_meta_id' => $metaId, 'lang' => $lang], $row));
    }

    /**
     * Refresh the sitemap cache (noindex may have changed) and the request memo.
     *
     * @param int|string|null $storeId
     * @return void
     */
    private static function afterChange($storeId): void
    {
        request()->attributes->remove(self::CURRENT_ATTRIBUTE);
        $storeId = ($storeId === null || $storeId === '') ? config('app.storeId') : $storeId;
        if ($storeId !== null && $storeId !== '') {
            // WHY after commit: a sitemap built meanwhile must not cache pre-save data under the new version.
            DB::connection(GP247_DB_CONNECTION)->afterCommit(static fn () => SeoController::bumpCacheVersion($storeId));
        }
    }

    /**
     * Stored form of a typed value: text kept as typed (control characters and
     * surrounding blanks removed), blank ⇒ null. Escaping happens on output.
     *
     * @param mixed $value
     * @return string|null
     */
    private static function clean($value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }

        return self::blankToNull(gp247_text_plain((string) $value));
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function blankToNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;

        return trim($value) === '' ? null : $value;
    }
}
