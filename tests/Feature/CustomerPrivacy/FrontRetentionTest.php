<?php

namespace GP247\Front\Tests\Feature\CustomerPrivacy;

use Carbon\CarbonImmutable;
use GP247\Core\PersonalData\Application\PersonalDataRegistry;
use GP247\Core\PersonalData\Application\Retention\RetentionSweeper;
use GP247\Core\PersonalData\Infrastructure\PersonalDataSchema;
use GP247\Core\PersonalData\Infrastructure\Retention\RetentionStateRepository;
use GP247\Front\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;

/**
 * Contact messages are kept 2 years and cancelled newsletter sign-ups 30 days, then cleaned;
 * newer messages and active sign-ups stay.
 *
 * @aidlc-unit customer-privacy
 * @aidlc-story US-personal-data-retention-policies
 * @aidlc-adr personal-data-retention_retention-registry
 */
#[Group('personal-data-retention')]
class FrontRetentionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_old_contact_messages_and_cancelled_sign_ups_are_cleaned_but_recent_and_active_ones_stay(): void
    {
        RetentionStateRepository::forgetReady();
        $now = CarbonImmutable::now()->startOfSecond();
        app(RetentionSweeper::class)->states($now);
        $this->table(PersonalDataSchema::RETENTION)->update(['first_allowed_at' => $now->subMinute()]);
        $old = $this->contact($now->subDays(800));
        $recent = $this->contact($now->subDays(100));
        $cancelled = $this->subscription(0, $now->subDays(40));
        $justCancelled = $this->subscription(0, $now->subDays(5));
        $active = $this->subscription(1, $now->subYears(3));

        foreach (['front.contact', 'front.subscriptions'] as $key) {
            $this->assertNull(app(RetentionSweeper::class)->slice(PersonalDataRegistry::retentionItem($key), $now)['error'], $key);
        }

        $this->assertFalse($this->table('front_contact')->where('id', $old)->exists());
        $this->assertTrue($this->table('front_contact')->where('id', $recent)->exists());
        $this->assertFalse($this->table('front_subscribe')->where('id', $cancelled)->exists());
        $this->assertTrue($this->table('front_subscribe')->where('id', $justCancelled)->exists());
        $this->assertTrue($this->table('front_subscribe')->where('id', $active)->exists(), 'an active sign-up is never cleaned by age');
    }

    /**
     * @param CarbonImmutable $at
     * @return string
     */
    private function contact(CarbonImmutable $at): string
    {
        $id = (string) Str::uuid();
        $this->table('front_contact')->insert(['id' => $id, 'store_id' => GP247_STORE_ID_ROOT, 'name' => 'Le Thi Hoa', 'email' => 'hoa@example.com',
            'title' => 'Hello', 'content' => 'Question', 'status' => 0, 'created_at' => $at, 'updated_at' => $at]);

        return $id;
    }

    /**
     * @param int             $status
     * @param CarbonImmutable $at
     * @return string
     */
    private function subscription(int $status, CarbonImmutable $at): string
    {
        $id = (string) Str::uuid();
        $this->table('front_subscribe')->insert(['id' => $id, 'email' => Str::random(8) . '@example.com', 'status' => $status, 'store_id' => GP247_STORE_ID_ROOT,
            'created_at' => $at, 'updated_at' => $at]);

        return $id;
    }

    /**
     * @param string $logical
     * @return \Illuminate\Database\Query\Builder
     */
    private function table(string $logical)
    {
        return DB::connection(GP247_DB_CONNECTION)->table(GP247_DB_PREFIX . $logical);
    }
}
