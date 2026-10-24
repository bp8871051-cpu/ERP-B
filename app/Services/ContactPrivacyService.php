<?php

namespace App\Services;

use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\User;

class ContactPrivacyService
{
    /**
     * Check if a user is allowed to view unmasked contact information (phone, email) for a lead.
     * 
     * Rule 2: Only Super Admin can view every lead's full mobile number and email address.
     * Rule 3: For all other roles, reveal full mobile number and email address only when the lead
     *         is currently assigned to the logged-in user by the Super Admin.
     * Rule 4: For unassigned leads and leads assigned to other employees, hide or mask fields.
     */
    public static function canViewLeadContactInfo(?User $user, CrmLead $lead): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Assigned to logged-in user (strictly check assigned_to)
        // Rule 5: For unassigned leads (assigned_to is null) and leads assigned to others, hide contact info.
        if ($lead->assigned_to !== null && (int)$lead->assigned_to === (int)$user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user is allowed to view unmasked contact information for a contact.
     * 
     * Rule 9: Apply the same contact privacy rules to the existing Contacts directory
     * when a contact is linked to a lead. Preserve existing contact info and do not
     * accidentally expose unassigned contacts.
     */
    public static function canViewContactInfo(?User $user, CrmContact $contact): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if contact has linked leads
        // If linked to any lead assigned to this user, grant access
        $hasAssignedLead = $contact->leads()
            ->where('assigned_to', $user->id)
            ->exists();

        if ($hasAssignedLead) {
            return true;
        }

        // If contact is linked to leads, but NONE are assigned to this user (e.g. unassigned or assigned to someone else)
        if ($contact->leads()->exists()) {
            return false;
        }

        // If contact is standalone (not linked to any lead), check if contact is directly owned by user
        // Unassigned standalone contacts remain masked to prevent accidental exposure (Rule 9)
        if ($contact->owner_id && (int)$contact->owner_id === (int)$user->id) {
            return true;
        }

        return false;
    }

    /**
     * Mask an email address securely.
     * Example: "john.doe@example.com" -> "j***e@example.com" or "j***@example.com"
     */
    public static function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email ? '***@***.com' : null;
        }

        [$username, $domain] = explode('@', $email, 2);
        $len = mb_strlen($username);

        if ($len <= 2) {
            $maskedUser = mb_substr($username, 0, 1) . '***';
        } else {
            $prefix = mb_substr($username, 0, 1);
            $suffix = mb_substr($username, -1);
            $maskedUser = $prefix . str_repeat('*', min(5, max(3, $len - 2))) . $suffix;
        }

        return $maskedUser . '@' . $domain;
    }

    /**
     * Mask a phone number securely.
     * Example: "+1 (555) 019-2834" -> "+1 (555) ***-**34"
     * Example: "+91 9659919186" -> "+91 965****186"
     */
    public static function maskPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $trimmed = trim($phone);
        $len = strlen($trimmed);
        if ($len <= 4) {
            return '****';
        }

        // Preserve first 4 characters (e.g. "+91 " or "+1 (") and last 3 characters
        $visiblePrefix = min(4, max(2, (int)($len * 0.25)));
        $visibleSuffix = min(3, max(2, (int)($len * 0.2)));

        $prefix = substr($trimmed, 0, $visiblePrefix);
        $suffix = substr($trimmed, -$visibleSuffix);
        $maskCount = max(4, $len - ($visiblePrefix + $visibleSuffix));

        return $prefix . str_repeat('*', $maskCount) . $suffix;
    }

    /**
     * Apply privacy masking to a CrmLead model.
     */
    public static function maskLead(CrmLead $lead, ?User $user): CrmLead
    {
        $hasAccess = self::canViewLeadContactInfo($user, $lead);
        $lead->setAttribute('has_contact_access', $hasAccess);

        if (!$hasAccess) {
            $lead->email = self::maskEmail($lead->email);
            $lead->phone = self::maskPhone($lead->phone);

            if ($lead->relationLoaded('contact') && $lead->contact) {
                self::maskContact($lead->contact, $user);
            }
        } else {
            if ($lead->relationLoaded('contact') && $lead->contact) {
                $lead->contact->setAttribute('has_contact_access', true);
            }
        }

        return $lead;
    }

    /**
     * Apply privacy masking to a CrmContact model.
     */
    public static function maskContact(CrmContact $contact, ?User $user): CrmContact
    {
        $hasAccess = self::canViewContactInfo($user, $contact);
        $contact->setAttribute('has_contact_access', $hasAccess);

        if (!$hasAccess) {
            $contact->email = self::maskEmail($contact->email);
            $contact->secondary_email = self::maskEmail($contact->secondary_email);
            $contact->phone = self::maskPhone($contact->phone);
            $contact->whatsapp = self::maskPhone($contact->whatsapp);
        }

        return $contact;
    }
}
