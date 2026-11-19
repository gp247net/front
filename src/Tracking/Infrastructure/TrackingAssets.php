<?php

namespace GP247\Front\Tracking\Infrastructure;

/**
 * The consent loader script: shipped in the package, copied to public/ (a browser
 * cannot read vendor/) and refreshed whenever the package ships a new build — for
 * every template, not only GP247Front (decision D3 of the TC-1 plan). When the copy
 * fails (read-only public/), the loader is missing and no tracking code runs at all.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-template-hook, US-tracking-consent-install-lifecycle
 * @aidlc-adr tracking-consent_head-injection-via-meta-head
 */
final class TrackingAssets
{
    /** Path under public/ the loader is served from. */
    public const PUBLIC_DIR = 'GP247/Front/tracking';

    public const LOADER = 'gp247-consent.js';

    /**
     * @return string Absolute path of the loader inside the package.
     */
    public static function sourceDir(): string
    {
        return dirname(__DIR__, 2) . '/Resources/tracking';
    }

    /**
     * Size and modification time of the shipped loader: changes whenever composer
     * updates the package. Two stat() calls — cheap enough for every boot.
     *
     * @return string Empty when the package file is missing.
     */
    public static function signature(): string
    {
        $file = self::sourceDir() . '/' . self::LOADER;

        return is_file($file) ? filesize($file) . '-' . filemtime($file) : '';
    }

    /**
     * Copy the loader to public/ when the copy there is missing or older.
     *
     * @param string|null $targetDir Absolute target directory; null = public/GP247/Front/tracking.
     * @return bool True when the file was (re)copied.
     */
    public static function sync(?string $targetDir = null): bool
    {
        $signature = self::signature();
        if ($signature === '') {
            return false;
        }
        $targetDir ??= public_path(self::PUBLIC_DIR);
        $stamp = $targetDir . '/.gp247-assets';
        if (is_file($stamp) && trim((string) file_get_contents($stamp)) === $signature && is_file($targetDir . '/' . self::LOADER)) {
            return false;
        }
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return false;
        }
        // WHY no exception: a read-only public/ must not stop the storefront; the
        // missing loader only means tracking code does not run.
        if (!@copy(self::sourceDir() . '/' . self::LOADER, $targetDir . '/' . self::LOADER)) {
            return false;
        }
        @file_put_contents($stamp, $signature);

        return true;
    }

    /**
     * URL of the loader, versioned so browsers fetch a new build after an update.
     *
     * @return string
     */
    public static function url(): string
    {
        return gp247_file(self::PUBLIC_DIR . '/' . self::LOADER) . '?v=' . rawurlencode(self::signature());
    }
}
