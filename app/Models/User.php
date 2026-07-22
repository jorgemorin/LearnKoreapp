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
 * @property bool   $is_active   false = cuenta desactivada por un admin
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
        'is_active',
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
            'is_active'         => 'boolean',
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

    /** Comprueba si la cuenta está activa. */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
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

    public function reports()
    {
        return $this->hasMany(UserReport::class, 'user_id');
    }

    public function srsSettings()
    {
        return $this->hasOne(UserSrsSettings::class);
    }
}
