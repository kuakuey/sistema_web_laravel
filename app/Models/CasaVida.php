<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasaVida extends ModeloCda
{
    protected $table = 'casas_vida';

    protected $casts = [
        'territorio_id' => 'integer',
        'lider_id' => 'integer',
        'colaborador_id' => 'integer',
        'anfitrion_id' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function territorio(): BelongsTo
    {
        return $this->belongsTo(Territorio::class, 'territorio_id');
    }

    public function lider(): BelongsTo
    {
        return $this->belongsTo(Lider::class, 'lider_id');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Lider::class, 'colaborador_id');
    }

    public function anfitrion(): BelongsTo
    {
        return $this->belongsTo(Lider::class, 'anfitrion_id');
    }
}
