<?php

namespace GP247\Front\Admin\Livewire;

use GP247\Core\AdminShell\Infrastructure\DataTableComponent;
use GP247\Core\AdminShell\Infrastructure\HasStoreScopeUi;
use GP247\Front\Models\FrontContact;
use GP247\Front\Support\ContactStatus;

/**
 * Contact messages (front-contact): list of the store's messages, newest first,
 * filter by status, search; open one to read it (marks it read), mark handled /
 * back to new, delete. No create or edit — the visitor's text is never changed.
 * Store-scoped like the other front lists: every action re-reads the record
 * inside the admin's store, so a crafted id of another store does nothing.
 * Gated by URI gp247_admin/contact (permission front.contact).
 *
 * @aidlc-unit front-contact
 * @aidlc-story US-front-contact-inbox
 * @aidlc-adr front-contact_contact-form-in-front
 */
class ContactManager extends DataTableComponent
{
    use HasStoreScopeUi;

    protected ?string $permission = 'admin_contact';

    protected ?string $titleKey = 'admin.contact.title';

    /** @var string Status filter: '' = all, else a ContactStatus value. */
    public string $statusFilter = '';

    /** @var string|null Id of the message open in the reading panel. */
    public ?string $viewingId = null;

    /**
     * @return bool Opt into store scoping.
     */
    protected function storeScopeOptIn(): bool
    {
        return true;
    }

    /**
     * @return FrontContact
     */
    protected function query()
    {
        return new FrontContact();
    }

    /**
     * @return array<int, string> Newest first.
     */
    protected function defaultSort(): array
    {
        return ['created_at', 'desc'];
    }

    /**
     * Store scope + status filter (also applied by the base delete).
     *
     * @param mixed $query
     * @return void
     */
    protected function constrain($query): void
    {
        $this->constrainStore($query);

        $status = ContactStatus::fromFilter($this->statusFilter);
        if ($status !== null) {
            $query->where('status', $status->value);
        }
    }

    /**
     * Root admin with multi-store sees every store; anyone else only their own.
     *
     * @param mixed $query
     * @return void
     */
    private function constrainStore($query): void
    {
        if (!($this->storeScopeActive() && $this->isRootScope())) {
            $query->where('store_id', (string) $this->storeContext());
        }
    }

    /**
     * @return array<string, string>
     */
    protected function columns(): array
    {
        return [
            'created_at' => gp247_language_render('admin.contact.col_date'),
            'name' => gp247_language_render('admin.contact.col_sender'),
            'title' => gp247_language_render('contact.subject'),
            'status' => gp247_language_render('admin.contact.col_status'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['name', 'email', 'title'];
    }

    /**
     * @return string
     */
    protected function listView(): string
    {
        return 'gp247-front-admin::contact-manager';
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [
            'viewing' => $this->viewingId !== null ? $this->findInStore($this->viewingId) : null,
            'statuses' => ContactStatus::cases(),
        ];
    }

    /**
     * Livewire hook: a new filter starts from page 1.
     *
     * @return void
     */
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Open a message for reading; a new message becomes read.
     *
     * @param string $id
     * @return void
     */
    public function open(string $id): void
    {
        $this->authorizeAction('update');
        $contact = $this->findInStore($id);
        if ($contact === null) {
            $this->viewingId = null;

            return;
        }
        $contact->markRead();
        $this->viewingId = $contact->id;
    }

    /**
     * @return void
     */
    public function closeView(): void
    {
        $this->viewingId = null;
    }

    /**
     * @param string $id
     * @return void
     */
    public function markHandled(string $id): void
    {
        $this->authorizeAction('update');
        $this->findInStore($id)?->markHandled();
    }

    /**
     * @param string $id
     * @return void
     */
    public function markNew(string $id): void
    {
        $this->authorizeAction('update');
        $this->findInStore($id)?->markNew();
    }

    /**
     * @param string $id
     * @return FrontContact|null The message when it belongs to the admin's store scope.
     */
    private function findInStore(string $id): ?FrontContact
    {
        $query = FrontContact::query()->where('id', $id);
        $this->constrainStore($query);

        return $query->first();
    }
}
