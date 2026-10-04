<?php

namespace App\Services;

use App\Models\SystemAuditLog;
use App\Models\SystemSetting;
use Exception;

class SettingsService
{
    /**
     * Get all settings grouped by category (general, security, notifications)
     */
    public function getAllSettings(): array
    {
        $settings = SystemSetting::all();
        $grouped = [];

        foreach ($settings as $s) {
            $val = match ($s->type) {
                'boolean' => filter_var($s->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $s->value,
                'json' => json_decode($s->value, true),
                default => $s->value,
            };

            $grouped[$s->group][$s->key] = $val;
            $grouped['all'][$s->key] = $val;
        }

        return $grouped;
    }

    /**
     * Save settings for a specific group (general, security, notifications)
     */
    public function updateGroupSettings(string $group, array $values, int $companyId = 1, ?int $userId = null): array
    {
        $oldValues = [];
        $newValues = [];

        foreach ($values as $key => $val) {
            $existing = SystemSetting::where('key', $key)->first();
            $oldValues[$key] = $existing ? $existing->value : null;

            $type = 'string';
            if (is_bool($val)) {
                $type = 'boolean';
                $storedVal = $val ? 'true' : 'false';
            } elseif (is_int($val) || (is_numeric($val) && strpos((string)$val, '.') === false)) {
                $type = 'integer';
                $storedVal = (string) $val;
            } elseif (is_array($val)) {
                $type = 'json';
                $storedVal = json_encode($val);
            } else {
                $storedVal = (string) $val;
            }

            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $storedVal, 'type' => $type, 'group' => $group]
            );

            $newValues[$key] = $storedVal;
        }

        SystemAuditLog::log('settings', "update_{$group}_settings", null, $oldValues, $newValues, $companyId, $userId);

        return $this->getAllSettings()[$group] ?? [];
    }

    /**
     * Test SMTP configuration connection
     */
    public function testSmtpConnection(array $config): array
    {
        $host = $config['host'] ?? 'smtp.mailgun.org';
        $port = (int) ($config['port'] ?? 587);
        $timeout = 5;

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return [
                'success' => false,
                'message' => "Could not connect to {$host}:{$port} - Error: {$errstr} ({$errno})",
            ];
        }

        fclose($fp);
        return [
            'success' => true,
            'message' => "Successfully connected to SMTP server at {$host}:{$port}!",
        ];
    }
}
