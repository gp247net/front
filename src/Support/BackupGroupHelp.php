<?php

namespace GP247\Front\Support;

/**
 * What the backup groups gp247/front adds tables to hold, in business words (en / vi / ja). The provider
 * passes these keys to the core backup registry; the language seeder and the upgrade migration
 * install the texts from here (one source). No table names: the backup screen is read by site owners.
 *
 * @aidlc-unit backup-engine
 * @aidlc-story US-backup-engine-data-groups
 * @aidlc-adr backup-engine_data-group-registry
 */
final class BackupGroupHelp
{
    /** @var array<string, array{0: string, 1: string, 2: string}> Language key => [en, vi, ja]. */
    private const TEXTS = [
        'admin.backup.group_help.front.content' => ['Pages, banners, layout blocks, links and link groups, URL redirects, the country list', 'Trang, banner, khối giao diện, liên kết và nhóm liên kết, chuyển hướng URL, danh sách quốc gia', 'ページ、バナー、レイアウトブロック、リンクとリンクグループ、URL リダイレクト、国の一覧'],
        'admin.backup.group_help.front.customers' => ['Contact messages and newsletter sign-ups', 'Tin nhắn liên hệ và email đăng ký nhận tin', 'お問い合わせメッセージとニュースレター登録'],
    ];

    /**
     * @param string $group Backup group key (content, customers).
     * @return string Language key of the description of gp247/front's part of that group.
     */
    public static function key(string $group): string
    {
        return 'admin.backup.group_help.front.'.$group;
    }

    /**
     * Rows for the `languages` table.
     *
     * @return list<array{code: string, text: string, position: string, location: string}>
     */
    public static function languageRows(): array
    {
        $rows = [];
        foreach (self::TEXTS as $code => [$en, $vi, $ja]) {
            foreach (['en' => $en, 'vi' => $vi, 'ja' => $ja] as $location => $text) {
                $rows[] = ['code' => $code, 'text' => $text, 'position' => 'admin.backup.group_help.front', 'location' => $location];
            }
        }

        return $rows;
    }
}
