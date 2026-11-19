<?php

namespace GP247\Front\Tracking\Application;

use GP247\Front\Models\FrontPage;
use GP247\Front\Models\FrontPageDescription;

/**
 * CMS pages a store may link from its cookie banner as the cookie policy (decision E3:
 * a page of the site, never a free URL).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-banner
 */
final class PolicyPages
{
    /**
     * Active pages of a store, titled in the admin's language.
     *
     * @param int|string $storeId
     * @return array<string, string> alias => title
     */
    public static function options($storeId): array
    {
        $page = (new FrontPage())->getTable();
        $description = (new FrontPageDescription())->getTable();

        return FrontPage::query()
            ->leftJoin($description, static function ($join) use ($description, $page) {
                $join->on($description . '.page_id', '=', $page . '.id')->where($description . '.lang', '=', app()->getLocale());
            })
            ->where($page . '.store_id', (string) $storeId)
            ->where($page . '.status', 1)
            ->orderBy($page . '.alias')
            ->get([$page . '.alias', $description . '.name'])
            ->mapWithKeys(static fn ($row) => [(string) $row->alias => (string) ($row->name ?: $row->alias)])
            ->all();
    }
}
