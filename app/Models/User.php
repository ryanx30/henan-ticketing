<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CS = 'cs';
    public const ROLE_IT = 'it';
    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_CS,
        self::ROLE_IT,
        self::ROLE_SUPERVISOR,
    ];

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function isCS(): bool { return $this->role === self::ROLE_CS; }
    public function isIT(): bool { return $this->role === self::ROLE_IT; }
    public function isSupervisor(): bool { return $this->role === self::ROLE_SUPERVISOR; }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
