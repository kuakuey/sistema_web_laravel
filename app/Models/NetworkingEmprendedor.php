<?php

namespace App\Models;

class NetworkingEmprendedor extends ModeloCda
{
    protected $table = 'networking_emprendedores';

    public const UPDATED_AT = 'actualizado_en';

    protected $casts = [
        'autoriza_directorio' => 'boolean',
        'visible' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];
}
