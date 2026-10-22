<?php

namespace App\Models;

class PresentacionNino extends ModeloCda
{
    protected $table = 'presentaciones_ninos';

    public const UPDATED_AT = 'actualizado_en';

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_presentacion' => 'date',
        'estado_bloqueado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];
}
