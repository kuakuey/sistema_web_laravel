<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritorioCategoriaVinculo extends ModeloCda
{
    protected $table = 'territorio_categoria';

    protected $casts = [
        'territorio_id' => 'integer',
        'categoria_id' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function territorio(): BelongsTo
    {
        return $this->belongsTo(Territorio::class, 'territorio_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(TerritorioCategoria::class, 'categoria_id');
    }
}
