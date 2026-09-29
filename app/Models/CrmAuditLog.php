<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmAuditLog extends Model
{
    use HasFactory;

    protected $table = 'crm_audit_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'action',
        'record_type',
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(string $action, string $recordType, int $recordId, ?array $oldValues = null, ?array $newValues = null, ?int $companyId = null, ?int $userId = null): self
    {
        return self::create([
            'company_id' => $companyId ?? (auth()->user()?->company_id ?? 1),
            'user_id' => $userId ?? auth()->id(),
            'module' => 'CRM',
            'action' => $action,
            'record_type' => $recordType,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
