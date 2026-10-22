<?php

namespace App\Models;

class EventoTipoEntrada extends ModeloCda
{
    protected $table = 'eventos_tipos_entrada';

    protected $casts = [
        'valor' => 'decimal:2',
        'visible_publico' => 'boolean',
        'es_gratis' => 'boolean',
        'orden' => 'integer',
        'creado_en' => 'datetime',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }
}
