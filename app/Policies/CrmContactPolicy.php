<?php

namespace App\Policies;

use App\Models\CrmContact;
use App\Models\User;
use App\Services\ContactPrivacyService;

class CrmContactPolicy
{
    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm.view') || $user->hasPermission('crm.contacts.view');
    }

    /**
     * Determine whether the user can view the contact.
     */
    public function view(User $user, CrmContact $contact): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can view unmasked contact details (phone, email).
     */
    public function viewContactDetails(User $user, CrmContact $contact): bool
    {
        return ContactPrivacyService::canViewContactInfo($user, $contact);
    }

    /**
     * Determine whether the user can create contacts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('crm.create');
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, CrmContact $contact): bool
    {
        return $user->hasPermission('crm.edit');
    }

    /**
     * Determine whether the user can delete the contact.
     */
    public function delete(User $user, CrmContact $contact): bool
    {
        return $user->hasPermission('crm.delete');
    }

    /**
     * Determine whether the user can export contacts.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('crm.export') || $user->hasPermission('crm.view');
    }
}
