<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Super Admin and Admin can access Filament Admin panel
        return in_array($this->role, ['Super Admin', 'Admin', 'HR Manager', 'Inventory Manager', 'CRM Manager', 'Finance Manager', 'Sales Manager', 'Procurement Manager', 'Project Manager', 'Support Manager']);
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

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'Super Admin') {
            return true;
        }

        $roleModel = Role::where('name', $this->role)->with('permissions')->first();
        if (!$roleModel) {
            return false;
        }

        return $roleModel->permissions->contains('name', $permission);
    }
}
