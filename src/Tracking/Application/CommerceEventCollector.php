<?php

namespace GP247\Front\Tracking\Application;

/**
 * Keeps the storefront's e-commerce events of the current request for TrackingHead to
 * print; an event of a redirecting request goes to the session flash and is printed on the
 * next page. Whatever the source (shop, plugin, template), only the allowed fields survive
 * (NFR-SEC-tracking-no-personal-data). One instance per request (scoped binding).
 *
 * Listens to the shop's event by name, so gp247/front works with or without gp247/shop
 * (NFR-MAINT-tracking-soft-coupling).
 *
 * @aidlc-unit tracking-consent
 * @aidlc-story US-tracking-consent-commerce-events
 * @aidlc-adr tracking-consent_commerce-event-contract
 */
final class CommerceEventCollector
{
    /** Shop event this collector listens to. */
    public const SHOP_EVENT = 'GP247\\Shop\\Events\\CommerceEventOccurred';

    public const SESSION_KEY = 'gp247_commerce_events';

    public const TYPES = ['view_item', 'add_to_cart', 'begin_checkout', 'purchase'];

    /** Events printed on one page at most (a page reports one or two). */
    public const MAX_EVENTS = 20;

    private const PAYLOAD_KEYS = ['transaction_id', 'currency', 'value', 'tax', 'shipping', 'items'];

    private const ITEM_KEYS = ['item_id', 'item_name', 'item_category', 'item_brand', 'price', 'quantity'];

    private const MAX_ITEMS = 100;

    /** @var array<int, array{type: string, payload: array<string, mixed>}> */
    private array $events = [];

    /**
     * Listener of the shop's CommerceEventOccurred (or any object with type / payload / nextPage).
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        $row = self::clean((string) ($event->type ?? ''), is_array($event->payload ?? null) ? $event->payload : []);
        if ($row === null) {
            return;
        }
        if (!empty($event->nextPage)) {
            if (!app()->bound('session')) {
                return;
            }
            $queued = session()->get(self::SESSION_KEY, []);
            $queued = is_array($queued) ? $queued : [];
            $queued[] = $row;
            session()->flash(self::SESSION_KEY, array_slice($queued, -self::MAX_EVENTS));

            return;
        }
        if (count($this->events) < self::MAX_EVENTS) {
            $this->events[] = $row;
        }
    }

    /**
     * Events to print on this page: those flashed by the previous request, then this one's.
     *
     * @return array<int, array{type: string, payload: array<string, mixed>}>
     */
    public function pending(): array
    {
        $flashed = app()->bound('session') && request()->hasSession() ? session()->get(self::SESSION_KEY, []) : [];
        $rows = [];
        foreach (is_array($flashed) ? $flashed : [] as $row) {
            $clean = is_array($row) ? self::clean((string) ($row['type'] ?? ''), is_array($row['payload'] ?? null) ? $row['payload'] : []) : null;
            if ($clean !== null) {
                $rows[] = $clean;
            }
        }

        return array_slice(array_merge($rows, $this->events), 0, self::MAX_EVENTS);
    }

    /**
     * Keep only the allowed fields, as text or numbers.
     *
     * @param string               $type
     * @param array<string, mixed> $payload
     * @return array{type: string, payload: array<string, mixed>}|null Null for an unknown type.
     */
    public static function clean(string $type, array $payload): ?array
    {
        if (!in_array($type, self::TYPES, true)) {
            return null;
        }
        $clean = [];
        foreach (self::PAYLOAD_KEYS as $key) {
            if ($key === 'items' || !array_key_exists($key, $payload) || !is_scalar($payload[$key])) {
                continue;
            }
            $clean[$key] = in_array($key, ['value', 'tax', 'shipping'], true) ? (float) $payload[$key] : (string) $payload[$key];
        }
        $items = [];
        foreach (array_slice(is_array($payload['items'] ?? null) ? $payload['items'] : [], 0, self::MAX_ITEMS) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = [];
            foreach (self::ITEM_KEYS as $key) {
                if (array_key_exists($key, $item) && is_scalar($item[$key])) {
                    $row[$key] = in_array($key, ['price', 'quantity'], true) ? (float) $item[$key] : (string) $item[$key];
                }
            }
            $items[] = $row;
        }
        $clean['items'] = $items;

        return ['type' => $type, 'payload' => $clean];
    }
}
