<?php

namespace GP247\Front\Support;

use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;
use Illuminate\Support\Facades\DB;

/**
 * Duplicates a CMS page in one transaction: a new, switched-off page in the same store that
 * carries the original's image (the file is shared) and the texts of every language.
 *
 * What is copied is an allowlist (see the logical design front-admin_page-duplicate): a
 * column or table added later is not copied until it is listed here. Not copied:
 * content SEO (SEO tab: title, description, canonical, robots, OG image — the copy starts
 * with an empty SEO tab, because people duplicating rarely open it and the copy would go
 * live with the original's meta), custom-field values (the Page screen does not show them),
 * redirects of the original, creation date. Changed: status off, alias gets a `-copy` /
 * `-copy-<n>` suffix unique in the page's store, each title gets the "(copy)" label of its
 * language. Lengths follow the
 * Page form rules (alias 100, title 200) so the copy can be saved again right away.
 *
 * The caller decides which page may be read (store scope) and passes it in; the copy stays
 * in the original's store. No plugin hook: no plugin keeps data on pages
 * (ADR-front-admin-page-duplicate).
 *
 * @aidlc-unit front-admin
 * @aidlc-story US-FADM-page-duplicate
 * @aidlc-adr front-admin_page-duplicate
 */
class PageDuplicator
{
    /** Max length of an alias (the Page form rule `form.alias`). */
    public const ALIAS_MAX = 100;

    /** Max length of a title (the Page form rule `descriptions.*.name`). */
    public const TITLE_MAX = 200;

    /** Language key of the title label. */
    public const NAME_SUFFIX_KEY = 'admin.page.duplicate_name_suffix';

    /** Suffix candidates tried before giving up. */
    private const SUFFIX_ATTEMPTS = 1000;

    /** Title label used when the language row of the label is missing (site not updated yet). */
    private const NAME_SUFFIX_FALLBACK = '(copy)';

    /**
     * Duplicate a page.
     *
     * @param FrontPage $source Page to duplicate, already loaded within the admin's store scope.
     * @return FrontPage The copy.
     * @throws \RuntimeException When no free alias is found (nothing is kept).
     */
    public function duplicate(FrontPage $source): FrontPage
    {
        return DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($source): FrontPage {
            $storeId = (string) $source->store_id;
            $copy = FrontPage::create([
                'image' => $source->image,
                'store_id' => $source->store_id,
                'alias' => $this->uniqueAlias((string) $source->alias, $storeId),
                // WHY: a copy is never published before the admin has checked it.
                'status' => 0,
            ]);
            $this->copyDescriptions($source, $copy);

            return $copy;
        });
    }

    /**
     * First free value of `$base-copy`, `$base-copy-2`… among the pages of a store.
     *
     * @param string $base    Alias of the original.
     * @param string $storeId Store the alias must be unique in.
     * @return string
     * @throws \RuntimeException When every candidate is taken.
     */
    public function uniqueAlias(string $base, string $storeId): string
    {
        for ($n = 1; $n <= self::SUFFIX_ATTEMPTS; $n++) {
            $candidate = self::suffixed($base, $n, self::ALIAS_MAX);
            if (!FrontPage::where('alias', $candidate)->where('store_id', $storeId)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('No free alias for the copy of page "' . $base . '".');
    }

    /**
     * `$base-copy` (n = 1) or `$base-copy-n`, the base cut (and trailing dashes dropped) to fit $max.
     *
     * @param string $base
     * @param int    $n   Candidate number, from 1.
     * @param int    $max Max length in characters.
     * @return string
     */
    public static function suffixed(string $base, int $n, int $max): string
    {
        $suffix = $n === 1 ? '-copy' : '-copy-' . $n;
        $room = max(0, $max - mb_strlen($suffix));

        return rtrim(mb_substr($base, 0, $room), '-') . $suffix;
    }

    /**
     * Copy every language row, each title with the copy label of its own language.
     *
     * @param FrontPage $source
     * @param FrontPage $copy
     * @return void
     */
    private function copyDescriptions(FrontPage $source, FrontPage $copy): void
    {
        $rows = [];
        foreach (FrontPageDescription::where('page_id', $source->id)->get() as $row) {
            $suffix = ' ' . $this->nameSuffix((string) $row->lang);
            $rows[] = [
                'page_id' => $copy->id,
                'lang' => $row->lang,
                'name' => trim(mb_substr((string) $row->name, 0, max(0, self::TITLE_MAX - mb_strlen($suffix))) . $suffix),
                'keyword' => $row->keyword,
                'description' => $row->description,
                'content' => $row->content,
            ];
        }
        if ($rows !== []) {
            FrontPageDescription::insert($rows);
        }
    }

    /**
     * The "(copy)" label of a language.
     *
     * @param string $lang
     * @return string
     */
    private function nameSuffix(string $lang): string
    {
        $text = (string) gp247_language_render(self::NAME_SUFFIX_KEY, [], $lang);

        // WHY: before gp247:front-update seeds the label, rendering returns the key itself.
        return ($text === '' || $text === self::NAME_SUFFIX_KEY) ? self::NAME_SUFFIX_FALLBACK : $text;
    }
}
