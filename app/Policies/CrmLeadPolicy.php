<?php

namespace App\Policies;

use App\Models\CrmLead;
use App\Models\User;
use App\Services\ContactPrivacyService;

class CrmLeadPolicy
{
    /**
     * Determine whether the user can view any leads.
     * Rule 1: All authenticated users with CRM list permission can view the complete leads list, regardless of assignment.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('crm.view') || $user->hasPermission('crm.leads.view');
    }

    /**
     * Determine whether the user can view the lead details.
     */
    public function view(User $user, CrmLead $lead): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can view unmasked contact details (phone, email).
     * Rule 2 & 3: Super Admin or assigned employee only.
     */
    public function viewContactDetails(User $user, CrmLead $lead): bool
    {
        return ContactPrivacyService::canViewLeadContactInfo($user, $lead);
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('crm.create');
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, CrmLead $lead): bool
    {
        return $user->hasPermission('crm.edit');
    }

    /**
     * Determine whether the user can assign or reassign the lead.
     * Rule 6: Super Admin-only assignment control.
     */
    public function assign(User $user, CrmLead $lead): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, CrmLead $lead): bool
    {
        return $user->hasPermission('crm.delete');
    }

    /**
     * Determine whether the user can export leads.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('crm.export') || $user->hasPermission('crm.view');
    }
}
