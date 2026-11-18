<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'is_super_admin',
        'is_manager',
        'is_sales_manager',
        'can_import_export',
        'can_view_unassigned',
        'department_name',
    ];

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->isSuperAdmin();
    }

    public function getIsManagerAttribute(): bool
    {
        return $this->isManager();
    }

    public function getIsSalesManagerAttribute(): bool
    {
        return $this->isSalesManager();
    }

    public function getCanImportExportAttribute(): bool
    {
        return $this->canImportExport();
    }

    public function getCanViewUnassignedAttribute(): bool
    {
        return $this->canViewUnassignedLeads();
    }

    public function getDepartmentNameAttribute(): string
    {
        if ($this->relationLoaded('department') && $this->department) {
            return $this->department->name;
        }
        if ($this->department_id && $this->department) {
            return $this->department->name;
        }
        if ($this->relationLoaded('employee') && $this->employee && $this->employee->department_id) {
            return $this->employee->department?->name ?? 'Staff';
        }
        if ($this->role) {
            $lower = strtolower($this->role);
            if (str_contains($lower, 'admin')) return 'Management';
            if (str_contains($lower, 'sale')) return 'Sales & Marketing';
            if (str_contains($lower, 'hr')) return 'Human Resources';
            if (str_contains($lower, 'financ') || str_contains($lower, 'account')) return 'Finance & Accounts';
            if (str_contains($lower, 'invent') || str_contains($lower, 'ware')) return 'Inventory & Warehouse';
            if (str_contains($lower, 'tech') || str_contains($lower, 'engin') || str_contains($lower, 'dev')) return 'Engineering & IT';
            if (str_contains($lower, 'support') || str_contains($lower, 'service')) return 'Customer Support';
            if (str_contains($lower, 'purchas') || str_contains($lower, 'procure')) return 'Procurement';
            return $this->role;
        }
        return 'General Staff';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function layoutPreference()
    {
        return $this->hasOne(UserLayoutPreference::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'Super Admin';
    }

    public function isManager(): bool
    {
        if ($this->isSuperAdmin() || $this->role === 'Admin') {
            return true;
        }
        $r = strtolower($this->role ?? '');
        return str_contains($r, 'manager') || str_contains($r, 'admin') || str_contains($r, 'lead');
    }

    public function isSalesManager(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $r = strtolower($this->role ?? '');
        $d = strtolower($this->department_name ?? '');

        // Must be a manager AND associated with Sales
        $isMgr = str_contains($r, 'manager') || str_contains($r, 'head') || str_contains($r, 'lead');
        $isSales = str_contains($r, 'sale') || str_contains($d, 'sale');

        return $isMgr && $isSales;
    }

    public function canImportExport(): bool
    {
        return $this->isSuperAdmin() || $this->isSalesManager();
    }

    public function canViewUnassignedLeads(): bool
    {
        if ($this->isSuperAdmin() || $this->role === 'Admin') {
            return true;
        }

        $r = strtolower($this->role ?? '');
        $d = strtolower($this->department_name ?? '');

        // 1. Sales (Sales Manager, Sales Executive, Sales Rep, Sales Department)
        if (str_contains($r, 'sale') || str_contains($d, 'sale')) {
            return true;
        }

        // 2. Field (Field Manager, Field Executive, Field Officer, Field Sales)
        if (str_contains($r, 'field') || str_contains($d, 'field')) {
            return true;
        }

        // 3. Tally Manager / Accounts / Finance Manager
        if (str_contains($r, 'tally') || str_contains($d, 'tally') || 
           ($this->isManager() && (str_contains($r, 'account') || str_contains($r, 'financ') || str_contains($d, 'financ') || str_contains($d, 'account')))) {
            return true;
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'Super Admin' || $this->role === 'Admin') {
            return true;
        }

        $roleModel = Role::where('name', $this->role)->with('permissions')->first();
        if (!$roleModel) {
            return false;
        }

        return $roleModel->permissions->contains('name', $permission);
    }

    public function getAllPermissions(): array
    {
        if ($this->role === 'Super Admin' || $this->role === 'Admin') {
            return Permission::pluck('name')->toArray();
        }

        $roleModel = Role::where('name', $this->role)->with('permissions')->first();
        if (!$roleModel) {
            return [];
        }

        return $roleModel->permissions->pluck('name')->toArray();
    }
}
