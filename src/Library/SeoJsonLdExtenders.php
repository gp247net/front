<?php

namespace GP247\Front\Library;

/**
 * Extra schema.org fields that packages and plugins add to the one Product, Offer
 * or Organization block SeoMeta prints (`gp247-config.front.seo_jsonld_extenders`),
 * instead of printing a second block of the same type that search engines would
 * read as a conflicting entity.
 *
 * Fields already present are never overwritten (the base and earlier extenders
 * win), values must be JSON scalars or arrays of them, and a failing extender is
 * reported and skipped so a broken plugin cannot take a storefront page down.
 *
 * @aidlc-unit seo
 * @aidlc-story US-SEO-jsonld-extenders
 * @aidlc-adr ADR-014
 */
class SeoJsonLdExtenders
{
    public const PRODUCT = 'Product';

    public const OFFER = 'Offer';

    public const ORGANIZATION = 'Organization';

    /**
     * Merge the fields of every extender registered for a schema type.
     *
     * @param string               $schemaType One of the class constants.
     * @param array<string, mixed> $fields     Fields already in the block.
     * @param array<string, mixed> $context    Passed to each extender.
     * @return array<string, mixed> $fields plus new keys.
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-jsonld-extenders
     */
    public static function extend(string $schemaType, array $fields, array $context): array
    {
        foreach (self::extenders($schemaType) as $extender) {
            try {
                $extra = call_user_func($extender, $context);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
            if (!is_array($extra)) {
                continue;
            }
            foreach ($extra as $key => $value) {
                if (is_string($key) && $key !== '' && !array_key_exists($key, $fields) && self::isJsonValue($value)) {
                    $fields[$key] = $value;
                }
            }
        }

        return $fields;
    }

    /**
     * Context of the product being displayed, or null when the page shows no product
     * (Product and Offer extenders are then not asked).
     *
     * @param string $price
     * @param string $currency
     * @param string $url
     * @return array<string, string>|null
     *
     * @aidlc-unit seo
     * @aidlc-story US-SEO-jsonld-extenders
     */
    public static function productContext(string $price, string $currency, string $url): ?array
    {
        $subject = SeoContentMeta::currentSubject();
        if (($subject['type'] ?? null) !== 'product' || ($subject['id'] ?? '') === '') {
            return null;
        }

        return [
            'product_id' => (string) $subject['id'],
            'store_id' => (string) config('app.storeId'),
            'price' => $price,
            'currency' => $currency,
            'url' => $url,
        ];
    }

    /**
     * @param string $schemaType
     * @return array<int, callable>
     */
    private static function extenders(string $schemaType): array
    {
        $registered = (array) config('gp247-config.front.seo_jsonld_extenders.' . $schemaType, []);

        return array_values(array_filter($registered, 'is_callable'));
    }

    /**
     * Whether a value is a JSON scalar, null, or an array made only of those.
     *
     * @param mixed $value
     * @return bool
     */
    private static function isJsonValue($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!self::isJsonValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return $value === null || is_scalar($value);
    }
}
