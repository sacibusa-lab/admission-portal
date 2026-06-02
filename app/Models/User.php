<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a role or list of roles.
     */
    public function hasRole($role): bool
    {
        if (!$this->role) {
            return false;
        }
        if (is_string($role)) {
            return $this->role->name === $role;
        }
        if (is_array($role)) {
            return in_array($this->role->name, $role);
        }
        return false;
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }
        if (!$this->role) {
            return false;
        }
        return $this->role->permissions()->where('name', $permission)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
