<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritorioAsignacion extends ModeloCda
{
    protected $table = 'territorio_asignaciones';

    protected $casts = [
        'creado_en' => 'datetime',
    ];

    public function territorio(): BelongsTo
    {
        return $this->belongsTo(Territorio::class, 'territorio_id');
    }

    public function miembro(): BelongsTo
    {
        return $this->belongsTo(Lider::class, 'miembro_id');
    }
}
