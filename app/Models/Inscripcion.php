<?php

namespace App\Models;

class Inscripcion extends ModeloCda
{
    protected $table = 'inscripciones';

    protected $casts = [
        'contactado' => 'boolean',
        'estado_bautismo_bloqueado' => 'boolean',
        'fecha_bautismo' => 'date',
        'creado_en' => 'datetime',
    ];
}
