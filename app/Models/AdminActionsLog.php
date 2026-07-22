<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo AdminActionsLog — auditoría de todas las acciones realizadas por admins.
 *
 * No tiene UPDATED_AT (es un log de solo-insert, inmutable).
 *
 * @property int         $id
 * @property int         $admin_id
 * @property string      $action_type   ej. 'user.deactivate', 'tag.merge', 'report.resolve'
 * @property string|null $target_type   'user'|'compound'|'tag'|'report'
 * @property int|null    $target_id
 * @property array|null  $payload
 */
class AdminActionsLog extends Model
{
    protected $table      = 'admin_actions_log';
    const UPDATED_AT      = null; // log inmutable

    protected $fillable = [
        'admin_id', 'action_type', 'target_type', 'target_id', 'payload',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // ── Helper estático para registrar acciones ───────────────────────────────

    /**
     * Registra una acción admin en el log.
     *
     * @param  int         $adminId
     * @param  string      $actionType   ej. 'report.resolve', 'user.deactivate'
     * @param  string|null $targetType   ej. 'user', 'compound', 'report', 'tag'
     * @param  int|null    $targetId
     * @param  array       $payload      Datos adicionales (antes/después, motivo, etc.)
     */
    public static function record(
        int     $adminId,
        string  $actionType,
        ?string $targetType = null,
        ?int    $targetId   = null,
        array   $payload    = []
    ): static {
        return static::create([
            'admin_id'    => $adminId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'payload'     => empty($payload) ? null : $payload,
        ]);
    }
}
