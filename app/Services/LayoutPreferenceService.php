<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserLayoutPreference;
use Illuminate\Http\Request;

class LayoutPreferenceService
{
    /**
     * Get layout preferences for a user, creating defaults if not yet established.
     */
    public function getPreferences(User $user): UserLayoutPreference
    {
        return UserLayoutPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'sidebar_mode' => SystemSetting::get('layout.default_sidebar_mode', 'default'),
                'menu_behavior' => SystemSetting::get('layout.default_menu_behavior', 'click'),
                'content_width' => SystemSetting::get('layout.default_content_width', 'default'),
                'direction' => SystemSetting::get('layout.default_direction', 'ltr'),
                'sidebar_visibility' => 'visible',
                'sidebar_state' => 'expanded',
            ]
        );
    }

    /**
     * Update user layout preferences with validation and audit logging.
     */
    public function updatePreferences(User $user, array $data, ?Request $request = null): UserLayoutPreference
    {
        $preference = $this->getPreferences($user);
        $oldValues = $preference->only([
            'sidebar_mode',
            'menu_behavior',
            'content_width',
            'direction',
            'sidebar_visibility',
            'sidebar_state',
        ]);

        $updatable = array_intersect_key($data, array_flip([
            'sidebar_mode',
            'menu_behavior',
            'content_width',
            'direction',
            'sidebar_visibility',
            'sidebar_state',
        ]));

        $preference->update($updatable);
        $newValues = $preference->only(array_keys($updatable));

        // Create Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'layout.updated',
            'module' => 'layouts',
            'description' => "User {$user->name} modified layout configuration: " . json_encode($updatable),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);

        return $preference->fresh();
    }

    /**
     * Reset user layout preferences to the corporate system defaults.
     */
    public function resetPreferences(User $user, ?Request $request = null): UserLayoutPreference
    {
        $preference = $this->getPreferences($user);
        $oldValues = $preference->toArray();

        $defaults = [
            'sidebar_mode' => SystemSetting::get('layout.default_sidebar_mode', 'default'),
            'menu_behavior' => SystemSetting::get('layout.default_menu_behavior', 'click'),
            'content_width' => SystemSetting::get('layout.default_content_width', 'default'),
            'direction' => SystemSetting::get('layout.default_direction', 'ltr'),
            'sidebar_visibility' => 'visible',
            'sidebar_state' => 'expanded',
        ];

        $preference->update($defaults);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'layout.reset',
            'module' => 'layouts',
            'description' => "User {$user->name} reset layout configuration to defaults",
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $defaults,
        ]);

        return $preference->fresh();
    }
}
