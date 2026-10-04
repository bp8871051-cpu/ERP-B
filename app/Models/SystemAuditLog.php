<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAuditLog extends Model
{
    use HasFactory;

    protected $table = 'system_audit_logs';

    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'action',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }

    public static function log(
        string $module,
        string $action,
        ?string $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        int $companyId = 1,
        ?int $userId = null
    ): self {
        return static::create([
            'company_id' => $companyId,
            'user_id' => $userId ?: auth()->id() ?: 1,
            'module' => $module,
            'action' => $action,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ]);
    }
}
