<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Models\Traits\FormatsDates;

class User extends Authenticatable
{
    use HasFactory, Notifiable, FormatsDates;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'branch_id',
        'department_id',
        'primary_lead_id',
        'secondary_lead_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get full name accessor.
     */
    public function getNameAttribute(): string
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");
        return $fullName !== '' ? $fullName : ($this->username ?? '');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function primaryLead()
    {
        return $this->belongsTo(User::class, 'primary_lead_id');
    }

    public function secondaryLead()
    {
        return $this->belongsTo(User::class, 'secondary_lead_id');
    }

    public function primarySubordinates()
    {
        return $this->hasMany(User::class, 'primary_lead_id');
    }

    public function secondarySubordinates()
    {
        return $this->hasMany(User::class, 'secondary_lead_id');
    }

    public function tokens()
    {
        return $this->hasMany(UserToken::class);
    }

    public function roles()
    {
        return $this->role();
    }

    public function departments()
    {
        return $this->department();
    }

    public function getRolesAttribute()
    {
        return $this->role;
    }

    public function getRolesIdAttribute()
    {
        return $this->role_id;
    }

    public function getDepartmentsAttribute()
    {
        return $this->department;
    }

    public function getDepartmentsIdAttribute()
    {
        return $this->department_id;
    }

    /**
     * Check if the user has a specific permission.
     * Super admins and admins automatically return true.
     */
    public function hasPermission($permissionName)
    {
        if (!$this->role) {
            return false;
        }

        if ($this->role->name === 'superadmin' || $this->role->name === 'admin') {
            return true;
        }

        return $this->role->permissions->contains('name', $permissionName);
    }
}
