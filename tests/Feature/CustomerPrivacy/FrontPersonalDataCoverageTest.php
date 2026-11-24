<?php

namespace GP247\Front\Tests\Feature\CustomerPrivacy;

use GP247\Core\PersonalData\Application\PersonalDataRegistry;
use GP247\Front\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;

/**
 * Guard: contact messages and newsletter sign-ups are deleted with the customer, and every
 * gp247/front table with a column that looks like personal data is declared or explained.
 *
 * @aidlc-unit customer-privacy
 * @aidlc-story US-customer-privacy-declare-shop-data
 * @aidlc-adr personal-data-registry_source-declaration
 */
#[Group('customer-privacy')]
class FrontPersonalDataCoverageTest extends TestCase
{
    private const PATTERN = '/(^|_)(email|phone|first_name|last_name|name|address\d*|postcode|ip|ip_address|user_agent|customer_id|identity)$/';


    public function test_contact_messages_and_newsletter_sign_ups_are_deleted_by_email(): void
    {
        foreach (['front_contact', 'front_subscribe'] as $table) {
            $source = PersonalDataRegistry::source($table);
            $this->assertNotNull($source, $table);
            $this->assertSame('delete', $source->rowRule()->value);
            $this->assertSame('email', $source->links()[0]->kind);
            $this->assertSame('gp247/front', $source->owner());
        }
    }

    public function test_every_front_table_with_personal_looking_columns_is_declared_or_explained(): void
    {
        $declared = array_map(fn ($s) => $s->table, PersonalDataRegistry::sources());
        $db = DB::connection(GP247_DB_CONNECTION);
        $schema = $db->getSchemaBuilder();
        $missing = [];
        foreach ($schema->getTables() as $table) {
            // WHY: on a shared server getTables() lists every database; only this one counts.
            if (isset($table['schema']) && $table['schema'] !== $db->getDatabaseName()) {
                continue;
            }
            $logical = substr((string) $table['name'], strlen(GP247_DB_PREFIX));
            if (!str_starts_with((string) $table['name'], GP247_DB_PREFIX . 'front_') || in_array($logical, $declared, true) || isset(\GP247\Core\PersonalData\Application\PersonalDataRegistry::notCustomerTables()[$logical])) {
                continue;
            }
            $matches = preg_grep(self::PATTERN, array_column($schema->getColumns((string) $table['name']), 'name'));
            if ($matches !== []) {
                $missing[] = $logical . ' (' . implode(', ', $matches) . ')';
            }
        }

        $this->assertSame([], $missing, 'Declare these tables in FrontPersonalData, or explain here why they hold no customer data.');
    }
}
