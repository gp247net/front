<?php

namespace GP247\Front\Tracking\Application;

use GP247\Front\Models\FrontTrackingRevision;
use GP247\Front\Models\FrontTrackingSnippet;
use GP247\Front\Tracking\Domain\CodeSnippet;
use GP247\Front\Tracking\Domain\InvalidTrackingSetting;
use GP247\Front\Tracking\Domain\TrackingSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Custom code blocks of a store: replace them all in one transaction with a before /
 * after revision (newest 30 kept, trimmed on write — no cron), and read the active ones
 * for the storefront from a per-store cache (one hour) dropped on every save
 * (ADR tracking-consent_config-storage-and-authority §2–§5).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority
 */
final class SnippetService
{
    public const KEEP_REVISIONS = 30;

    private const CACHE_PREFIX = 'gp247_tracking_snippets_';

    /**
     * Seconds the storefront keeps a store's blocks in cache.
     * WHY not forever: a database restore or a front reinstall changes the table without
     * a save, and nothing else would ever drop the old code from the cache.
     */
    public const CACHE_TTL = 3600;

    /**
     * Every block a store owns (also switched-off ones), in display order — for the screen.
     *
     * @param int|string $storeId
     * @return array<int, CodeSnippet>
     */
    public static function ownSnippets($storeId): array
    {
        $snippets = [];
        $rows = FrontTrackingSnippet::where('store_id', (string) $storeId)->orderBy('sort')->orderBy('id')->get();
        foreach ($rows as $row) {
            try {
                $snippets[] = CodeSnippet::fromInput($row->toArray());
            } catch (InvalidTrackingSetting $e) {
                // WHY skip, not fail: a row edited outside the screen (or restored from an
                // older version) must not take every other code off the storefront, nor
                // stop the screen from opening so the owner can fix it.
                if (function_exists('gp247_report')) {
                    gp247_report('[tracking-consent] code block #' . $row->id . ' skipped: ' . implode(', ', $e->errors()));
                }
            }
        }

        return $snippets;
    }

    /**
     * The store whose blocks run on a store's pages: its own when it opted in, else ROOT's.
     *
     * @param int|string       $storeId
     * @param TrackingSettings $settings Effective settings of that store.
     * @return string
     */
    public static function ownerOf($storeId, TrackingSettings $settings): string
    {
        $root = TrackingSettingsService::rootId();

        return ((string) $storeId !== $root && $settings->snippetOwn) ? (string) $storeId : $root;
    }

    /**
     * Active blocks that run on a store's pages, from cache (no query once warm).
     *
     * @param int|string       $storeId
     * @param TrackingSettings $settings Effective settings of that store.
     * @return array<int, CodeSnippet>
     */
    public static function activeFor($storeId, TrackingSettings $settings): array
    {
        $owner = self::ownerOf($storeId, $settings);
        $rows = Cache::remember(self::CACHE_PREFIX . $owner, self::CACHE_TTL, static function () use ($owner): array {
            return array_map(
                static fn (CodeSnippet $snippet) => $snippet->toArray(),
                array_values(array_filter(self::ownSnippets($owner), static fn (CodeSnippet $s) => $s->active)),
            );
        });

        return array_map(static fn (array $row) => CodeSnippet::fromInput($row), $rows);
    }

    /**
     * Replace a store's blocks, record what changed and drop the cache once committed.
     *
     * @param int|string              $storeId
     * @param array<int, CodeSnippet> $snippets New list, in display order.
     * @return bool Whether anything changed (no revision is written otherwise).
     */
    public static function save($storeId, array $snippets): bool
    {
        $storeId = (string) $storeId;
        // WHY renumber: the position on the screen is the order; stored sort follows it.
        $snippets = array_map(
            static fn (CodeSnippet $s, int $i) => new CodeSnippet($s->region, $s->category, $s->label, $s->content, $i, $s->active),
            array_values($snippets),
            array_keys(array_values($snippets)),
        );
        $after = array_map(static fn (CodeSnippet $s) => $s->toArray(), $snippets);

        $changed = DB::connection(GP247_DB_CONNECTION)->transaction(static function () use ($storeId, $snippets, $after): bool {
            $before = array_map(static fn (CodeSnippet $s) => $s->toArray(), self::ownSnippets($storeId));
            if ($before === $after) {
                return false;
            }
            // WHY replace the whole set: the screen edits the list as one document and the
            // revision keeps the previous list, so nothing is lost by deleting old rows.
            FrontTrackingSnippet::where('store_id', $storeId)->delete();
            foreach ($snippets as $snippet) {
                FrontTrackingSnippet::create(array_merge($snippet->toArray(), [
                    'store_id' => $storeId,
                    'status' => $snippet->active ? 1 : 0,
                ]));
            }
            self::recordRevision($storeId, $before, $after);

            return true;
        });

        // WHY also when nothing changed: saving is how an owner refreshes what the
        // storefront runs after the table changed behind the cache (restore, reinstall).
        self::forgetCache($storeId);

        return $changed;
    }

    /**
     * Newest revisions of a store, for the screen.
     *
     * @param int|string $storeId
     * @return array<int, FrontTrackingRevision>
     */
    public static function revisions($storeId): array
    {
        return FrontTrackingRevision::where('store_id', (string) $storeId)
            ->orderByDesc('id')
            ->limit(self::KEEP_REVISIONS)
            ->get()
            ->all();
    }

    /**
     * Drop the cached blocks of a store.
     *
     * @param int|string $storeId
     * @return void
     */
    public static function forgetCache($storeId): void
    {
        Cache::forget(self::CACHE_PREFIX . $storeId);
    }

    /**
     * Write one revision and trim the store's history to KEEP_REVISIONS.
     *
     * @param string                           $storeId
     * @param array<int, array<string, mixed>> $before
     * @param array<int, array<string, mixed>> $after
     * @return void
     */
    private static function recordRevision(string $storeId, array $before, array $after): void
    {
        $user = function_exists('admin') ? admin()->user() : null;
        FrontTrackingRevision::create([
            'store_id' => $storeId,
            'admin_user_id' => $user ? (string) $user->id : null,
            'admin_user_name' => $user ? mb_substr((string) $user->username, 0, 100) : null,
            'before' => json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            'after' => json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            'created_at' => now(),
        ]);

        $keepFrom = FrontTrackingRevision::where('store_id', $storeId)
            ->orderByDesc('id')
            ->skip(self::KEEP_REVISIONS)
            ->value('id');
        if ($keepFrom !== null) {
            FrontTrackingRevision::where('store_id', $storeId)->where('id', '<=', $keepFrom)->delete();
        }
    }
}
