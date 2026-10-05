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
