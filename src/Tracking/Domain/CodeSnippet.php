<?php

namespace GP247\Front\Tracking\Domain;

/**
 * One block of custom code a site owner pasted (Google Ads, TikTok, Clarity…). The
 * content is kept exactly as typed — arbitrary code is the point of the feature (Q7);
 * what keeps it safe is who may write it and that it only runs through the loader.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code
 * @aidlc-adr tracking-consent_config-storage-and-authority, tracking-consent_deferred-script-activation
 */
final class CodeSnippet
{
    /** Characters per block: a block lands in the <head> of every storefront page. */
    public const MAX_CONTENT = 100000;

    public const MAX_LABEL = 100;

    public const MAX_PER_STORE = 30;

    /**
     * Characters of all blocks of a store together.
     * WHY: the screen keeps every block in its Livewire state, and Livewire refuses
     * requests over 1 MB (the JSON is escaped twice) — past this the screen, including
     * its emergency switch, would stop answering.
     */
    public const MAX_TOTAL = 200000;

    /**
     * An HTML element start or end tag (`<script`, `</div`, `<img` …); a comment or a bare
     * "<" in JavaScript does not count.
     */
    private const TAG_PATTERN = '/<\/?[a-z][a-z0-9-]*(?=[\s>\/])/i';

    /**
     * @param SnippetRegion   $region   Where it goes once allowed to run.
     * @param ConsentCategory $category Cookie group it waits for (analytics|marketing).
     * @param string          $label    Owner's name for the block.
     * @param string          $content  Code, verbatim.
     * @param int             $sort     Order inside its region.
     * @param bool            $active   Printed on the storefront.
     */
    public function __construct(
        public readonly SnippetRegion $region,
        public readonly ConsentCategory $category,
        public readonly string $label,
        public readonly string $content,
        public readonly int $sort,
        public readonly bool $active,
    ) {
    }

    /**
     * Build one block from screen input or a stored row.
     *
     * @param array<string, mixed> $row   region, category, label, content, sort, status.
     * @param string               $field Error key prefix (e.g. "snippets.2").
     * @return self
     * @throws InvalidTrackingSetting When a field breaks a rule.
     */
    public static function fromInput(array $row, string $field = 'snippets.0'): self
    {
        // WHY: rows come from a browser-side Livewire state; a non-text value is treated as empty.
        $row = array_map(static fn ($value) => is_scalar($value) || $value === null ? $value : '', $row);
        $errors = [];
        $region = SnippetRegion::tryFrom((string) ($row['region'] ?? ''));
        if ($region === null) {
            $errors[$field . '.region'] = 'admin.tracking.error_region';
        }
        $categoryValue = (string) ($row['category'] ?? '');
        $category = $categoryValue === '' ? ConsentCategory::MARKETING : ConsentCategory::assignableFrom($categoryValue);
        if ($category === null) {
            $errors[$field . '.category'] = 'admin.tracking.error_category';
        }
        $label = trim((string) ($row['label'] ?? ''));
        if (mb_strlen($label) > self::MAX_LABEL) {
            $errors[$field . '.label'] = 'admin.tracking.error_label_long';
        }
        $content = (string) ($row['content'] ?? '');
        if (trim($content) === '') {
            $errors[$field . '.content'] = 'admin.tracking.error_content_required';
        } elseif (mb_strlen($content) > self::MAX_CONTENT) {
            $errors[$field . '.content'] = 'admin.tracking.error_content_long';
        }
        if ($errors !== []) {
            throw new InvalidTrackingSetting($errors);
        }

        return new self($region, $category, $label, $content, (int) ($row['sort'] ?? 0), (bool) ($row['status'] ?? true));
    }

    /**
     * Build every block of a store, collecting the errors of all of them.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, self>
     * @throws InvalidTrackingSetting When the list or any block breaks a rule.
     */
    public static function listFromInput(array $rows): array
    {
        if (count($rows) > self::MAX_PER_STORE) {
            throw new InvalidTrackingSetting(['snippets' => 'admin.tracking.error_too_many']);
        }
        $snippets = [];
        $errors = [];
        foreach (array_values($rows) as $i => $row) {
            try {
                $snippet = self::fromInput(is_array($row) ? $row : [], 'snippets.' . $i);
            } catch (InvalidTrackingSetting $e) {
                $errors += $e->errors();
                continue;
            }
            // WHY only for what the owner saves, not in fromInput(): a block stored before
            // this rule must still load on the screen so the owner can see and fix it.
            if (!self::hasTag($snippet->content)) {
                $errors['snippets.' . $i . '.content'] = 'admin.tracking.error_content_no_tag';
                continue;
            }
            $snippets[] = $snippet;
        }
        if ($errors !== []) {
            throw new InvalidTrackingSetting($errors);
        }
        if (array_sum(array_map(static fn (self $s) => mb_strlen($s->content), $snippets)) > self::MAX_TOTAL) {
            throw new InvalidTrackingSetting(['snippets' => 'admin.tracking.error_total_long']);
        }

        return $snippets;
    }

    /**
     * Whether pasted code holds at least one HTML element. The loader inserts a block as
     * HTML, so code without tags (JavaScript pasted without its <script> wrapper, plain
     * text) would show as text on every storefront page.
     *
     * @param string $content
     * @return bool
     */
    public static function hasTag(string $content): bool
    {
        $withoutComments = (string) preg_replace('/<!--.*?(-->|$)/s', '', $content);

        return preg_match(self::TAG_PATTERN, $withoutComments) === 1;
    }

    /**
     * @return array{region: string, category: string, label: string, content: string, sort: int, status: bool}
     */
    public function toArray(): array
    {
        return [
            'region' => $this->region->value,
            'category' => $this->category->value,
            'label' => $this->label,
            'content' => $this->content,
            'sort' => $this->sort,
            'status' => $this->active,
        ];
    }
}
