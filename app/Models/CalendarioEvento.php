<?php

namespace App\Models;

class CalendarioEvento extends ModeloCda
{
    protected $table = 'calendario_eventos';

    protected $casts = [
        'fecha' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
        'creado_en' => 'datetime',
    ];
}
