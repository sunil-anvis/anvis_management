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
        'name',
        'email',
        'password',
        'role_id',
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

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tokens()
    {
        return $this->hasMany(UserToken::class);
    }

    /**
     * Check if the user has a specific permission.
     * Super admins automatically return true.
     */
    public function hasPermission($permissionName)
    {
        if (!$this->role) {
            return false;
        }

        if ($this->role->name === 'super-admin') {
            return true;
        }

        return $this->role->permissions->contains('name', $permissionName);
    }
}
