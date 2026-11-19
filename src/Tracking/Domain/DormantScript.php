<?php

namespace GP247\Front\Tracking\Domain;

/**
 * Prints code so the browser does not run it: a `text/plain` script whose body is the
 * code as a JSON string. The consent loader parses it and inserts real elements once
 * the code's cookie group may run (ADR tracking-consent_deferred-script-activation).
 *
 * WHY a JSON string (decision D1 of the TC-1 plan): with `<` written as < the body
 * can never contain `</script` or `</template`, so pasted code cannot end the dormant
 * tag early and run before consent, and it reads back byte for byte — unlike rewriting
 * `</script` to `<\/script`, which changes code that already uses that idiom.
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-custom-code, US-tracking-consent-preset-services
 * @aidlc-adr tracking-consent_deferred-script-activation
 */
final class DormantScript
{
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

    /**
     * @param string          $html     Code to hold back, verbatim.
     * @param ConsentCategory $category Cookie group it waits for.
     * @param SnippetRegion   $region   Where the loader puts it.
     * @param string|null     $service  ga4|gtm|pixel for preset code, null for custom code.
     * @return string
     */
    public static function wrap(string $html, ConsentCategory $category, SnippetRegion $region, ?string $service = null): string
    {
        $attributes = ' data-gp247-consent="' . $category->value . '" data-gp247-region="' . $region->value . '"';
        if ($service !== null) {
            $attributes .= ' data-gp247-service="' . htmlspecialchars($service, ENT_QUOTES) . '"';
        }

        return '<script type="text/plain"' . $attributes . '>' . json_encode($html, self::JSON_FLAGS) . '</script>';
    }

    /**
     * JSON for inline data the loader reads, safe inside a <script> element.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public static function json(array $data): string
    {
        return json_encode($data, self::JSON_FLAGS);
    }
}
