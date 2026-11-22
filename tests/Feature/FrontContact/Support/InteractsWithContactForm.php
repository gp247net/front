<?php

namespace GP247\Front\Tests\Feature\FrontContact\Support;

use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminStore;
use GP247\Front\Models\FrontContact;

/**
 * Shared set-up for the contact-form feature tests: write the contact / mail
 * config rows of the root store inside the test transaction, reset the static
 * config + store caches so the next request reads them, and build the form URL.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-toggle
 */
trait InteractsWithContactForm
{
    /**
     * @return string Root store id.
     */
    private function rootStoreId(): string
    {
        return (string) (defined('GP247_STORE_ID_ROOT') ? GP247_STORE_ID_ROOT : 1);
    }

    /**
     * Upsert contact-form config rows (front_contact group) for a store.
     *
     * @param array<string, int|string> $values Key => value.
     * @param string|null               $storeId Target store (root by default).
     * @return void
     */
    private function setContactConfig(array $values, ?string $storeId = null): void
    {
        foreach ($values as $key => $value) {
            AdminConfig::updateOrCreate(
                ['key' => $key, 'store_id' => $storeId ?? $this->rootStoreId()],
                ['group' => 'front_contact', 'code' => 'contact_config', 'value' => (string) $value, 'sort' => 0, 'detail' => 'admin.contact.config.' . $key],
            );
        }
        $this->resetConfigCaches();
    }

    /**
     * Set the value of a root-store config row owned by another screen (email,
     * captcha…), creating it only when missing so its group/code stay untouched.
     *
     * @param string     $key
     * @param int|string $value
     * @return void
     */
    private function setCoreConfig(string $key, $value): void
    {
        $row = AdminConfig::where('key', $key)->where('store_id', $this->rootStoreId())->first();
        if ($row) {
            $row->value = (string) $value;
            $row->save();
        } else {
            AdminConfig::create(['key' => $key, 'store_id' => $this->rootStoreId(), 'group' => '', 'code' => 'zz_test', 'value' => (string) $value, 'sort' => 0, 'detail' => $key]);
        }
        $this->resetConfigCaches();
    }

    /**
     * Turn system mail on (synchronous, no queue) and give the root store an email.
     *
     * @return void
     */
    private function enableSystemMail(): void
    {
        $this->setCoreConfig('email_action_mode', 1);
        $this->setCoreConfig('email_action_queue', 0);
        AdminStore::where('id', $this->rootStoreId())->update(['email' => 'shop-owner@example.test']);
        $this->resetConfigCaches();
    }

    /**
     * Make sure the root store is active so CheckActive does not answer maintenance.
     *
     * @return void
     */
    private function activateRootStore(): void
    {
        AdminStore::where('id', $this->rootStoreId())->update(['active' => 1]);
        $this->resetConfigCaches();
    }

    /**
     * Drop the in-process memoised config + store lookups. WHY: DatabaseTransactions
     * rolls rows back but these statics outlive a single request in one test.
     *
     * @return void
     */
    private function resetConfigCaches(): void
    {
        foreach (['getAllGlobal' => null, 'getAllConfigOfStore' => []] as $prop => $empty) {
            $ref = new \ReflectionProperty(AdminConfig::class, $prop);
            $ref->setValue(null, $empty);
        }
        foreach (['getAll', 'getStoreActive', 'getCodeActive', 'getDomainPartner', 'getDomainStore',
            'getListAllActive', 'arrayStoreId', 'listStoreId', 'listStoreCode', 'getStoreDomainByCode'] as $prop) {
            if (property_exists(AdminStore::class, $prop)) {
                $ref = new \ReflectionProperty(AdminStore::class, $prop);
                $ref->setValue(null, null);
            }
        }
    }

    /**
     * @return string Public URL of the contact form.
     */
    private function contactUrl(): string
    {
        return gp247_route_front('front.contact');
    }

    /**
     * A valid submission payload.
     *
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function validMessage(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Visitor',
            'email' => 'ana.visitor@example.test',
            'phone' => '0900000000',
            'title' => 'ZZ contact test ' . uniqid(),
            'content' => 'Do you ship to Hanoi?',
        ], $overrides);
    }

    /**
     * Messages created by this test (matched on the unique title prefix).
     *
     * @return \Illuminate\Support\Collection<int, FrontContact>
     */
    private function testMessages()
    {
        return FrontContact::where('title', 'like', 'ZZ contact test%')->get();
    }
}
