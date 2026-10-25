<?php

namespace GP247\Front\Models;

use GP247\Front\Support\ContactStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * A contact message a visitor sent to a store (table front_contact).
 *
 * Invariants (domain model front-contact_contact-message): the store is set from the
 * request, never from input; what the visitor wrote never changes after creation —
 * the admin only moves the status or deletes the message.
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-submit, US-front-contact-inbox
 * @aidlc-adr front-contact_contact-form-in-front
 */
class FrontContact extends Model
{
    use \GP247\Core\Models\ModelTrait;
    use \GP247\Core\Models\UuidTrait;

    public const STATUS_NEW = 0;
    public const STATUS_READ = 1;
    public const STATUS_HANDLED = 2;

    /** Columns the visitor wrote; frozen once stored. */
    private const VISITOR_FIELDS = ['name', 'email', 'phone', 'title', 'content', 'store_id'];

    public $table = GP247_DB_PREFIX.'front_contact';
    protected $connection = GP247_DB_CONNECTION;
    protected $fillable = ['name', 'email', 'phone', 'title', 'content'];
    protected $casts = ['status' => 'integer'];

    /**
     * Uuid on create; refuse edits of what the visitor wrote.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty(self::VISITOR_FIELDS)) {
                throw new \LogicException('A contact message cannot be edited, only its status changes.');
            }
        });
    }

    /**
     * Store a message received from the public form.
     *
     * @param array<string, mixed> $data    Validated name/email/phone/title/content.
     * @param int|string           $storeId Store of the domain the visitor used.
     * @return self
     */
    public static function receive(array $data, $storeId): self
    {
        $message = new self();
        $message->fill(array_intersect_key($data, array_flip($message->getFillable())));
        $message->phone = ($data['phone'] ?? '') === '' ? null : $data['phone'];
        $message->store_id = (string) $storeId;
        $message->status = self::STATUS_NEW;
        $message->save();

        return $message;
    }

    /**
     * Mark a new message as read (opening it); other statuses are left alone.
     *
     * @return void
     */
    public function markRead(): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->moveTo(ContactStatus::Read);
        }
    }

    /**
     * @return void
     */
    public function markHandled(): void
    {
        $this->moveTo(ContactStatus::Handled);
    }

    /**
     * @return void
     */
    public function markNew(): void
    {
        $this->moveTo(ContactStatus::New);
    }

    /**
     * @return ContactStatus
     */
    public function statusEnum(): ContactStatus
    {
        return ContactStatus::tryFrom((int) $this->status) ?? ContactStatus::New;
    }

    /**
     * @param ContactStatus $status
     * @return void
     */
    private function moveTo(ContactStatus $status): void
    {
        $this->status = $status->value;
        $this->save();
    }
}
