<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiramideMiembro extends ModeloCda
{
    protected $table = 'piramide_equipo_miembros';

    protected $casts = [
        'equipo_id' => 'integer',
        'lider_id' => 'integer',
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(PiramideNodo::class, 'equipo_id');
    }

    public function miembro(): BelongsTo
    {
        return $this->belongsTo(Lider::class, 'lider_id');
    }
}
