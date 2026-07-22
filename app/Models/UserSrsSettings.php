<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo UserSrsSettings — configuración SRS personalizada por usuario.
 *
 * @property int    $user_id
 * @property string $learning_steps          ej. "1 10" (minutos)
 * @property string $relearning_steps        ej. "10" (minutos)
 * @property int    $graduating_interval     días para graduarse de Learning
 * @property int    $easy_interval           días al pulsar Fácil en carta New
 * @property float  $easy_bonus              multiplicador extra para Fácil en Review
 * @property float  $interval_modifier       multiplicador global
 * @property int    $max_interval            intervalo máximo en días
 * @property int    $new_cards_per_day
 * @property int    $review_cards_per_day
 */
class UserSrsSettings extends Model
{
    protected $table      = 'user_srs_settings';
    protected $primaryKey = 'user_id';
    public    $incrementing = false;

    // Solo updated_at (sin created_at, la PK es user_id no id)
    const CREATED_AT = null;

    protected $attributes = [
        'learning_steps'       => '1 10',
        'relearning_steps'     => '10',
        'graduating_interval'  => 1,
        'easy_interval'        => 4,
        'easy_bonus'           => 1.3,
        'interval_modifier'    => 1.0,
        'max_interval'         => 36500,
        'new_cards_per_day'    => 20,
        'review_cards_per_day' => 200,
    ];

    protected $fillable = [
        'user_id', 'learning_steps', 'relearning_steps',
        'graduating_interval', 'easy_interval', 'easy_bonus',
        'interval_modifier', 'max_interval', 'new_cards_per_day', 'review_cards_per_day',
    ];

    protected $casts = [
        'graduating_interval'  => 'integer',
        'easy_interval'        => 'integer',
        'easy_bonus'           => 'float',
        'interval_modifier'    => 'float',
        'max_interval'         => 'integer',
        'new_cards_per_day'    => 'integer',
        'review_cards_per_day' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Devuelve los pasos de learning como array de enteros (minutos).
     * "1 10" → [1, 10]
     */
    public function getLearningStepsArray(): array
    {
        return array_map('intval', explode(' ', trim($this->learning_steps)));
    }

    /**
     * Devuelve los pasos de relearning como array de enteros (minutos).
     */
    public function getRelearningStepsArray(): array
    {
        return array_map('intval', explode(' ', trim($this->relearning_steps)));
    }
}
