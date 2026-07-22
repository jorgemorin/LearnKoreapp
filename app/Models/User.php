<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo User con soporte de roles RBAC.
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $role        'user' | 'admin'
 * @property string $password
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Constantes de rol para evitar strings mágicos en el código
    const ROLE_USER  = 'user';
    const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ---------------------------------------------------------------------------
    // Helpers de rol
    // ---------------------------------------------------------------------------

    /** Comprueba si el usuario tiene rol administrador. */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Comprueba si el usuario tiene rol estándar. */
    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    // ---------------------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------------------

    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function studyLogs()
    {
        return $this->hasMany(StudyLog::class);
    }
}
