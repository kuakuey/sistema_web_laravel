<?php

namespace App\Models;

class Consejeria extends ModeloCda
{
    protected $table = 'consejerias';

    public const UPDATED_AT = 'actualizado_en';

    protected $casts = [
        'primera_consejeria' => 'boolean',
        'cita_fecha' => 'date',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];
}
